<?php


class BulletinAPI {

	public function call($bits){
		global $kaosCall;
		kaosCallInit();

		if (!isset($kaosCall['raw']))
			$kaosCall['raw'] = $bits && $bits[count($bits)-1] == 'raw' && array_pop($bits);

		if (KAOS_IS_CLI && $bits && $bits[0] == 'api')
			array_shift($bits);

		// CLI API Root
		if (KAOS_IS_CLI && $bits && $bits[0] == 'schemas'){
			require_once APP_PATH.'/templates/CLIRoot.php';
			kaosAPIPrintCLIAPIRoot();
			exit();
		}

		if ($bits && preg_match('#^([a-z]{2,3})$#i', $bits[0]))
			$kaosCall['rootSchema'] = strtoupper(array_shift($bits));

		$query = array();
		$query['followLevels'] = $bits && is_numeric($bits[count($bits)-1]) ? intval(array_pop($bits)) : 2;

		if ($bits && preg_match('#^([0-9]+)%$#', $bits[count($bits)-1], $m)){
			$kaosCall['spiderConfig']['cpuRate'] = min(intval($m[1]), 95);
			array_pop($bits);
		} else
			$kaosCall['spiderConfig']['cpuRate'] = 100;

		$kaosCall['call'] = array_pop($bits);

		if ($bits && in_array($bits[count($bits)-1], array('fetch', 'redirect'))){ // redirect format detection
			$query['format'] = $kaosCall['call'];
			$kaosCall['call'] = array_pop($bits);
		}

		if ($kaosCall['call'] == 'spide'){
			$kaosCall['spiderConfig']['workersCount'] = $query['followLevels'] ? $query['followLevels'] : KAOS_SPIDE_WORKER_COUNT;
			$query['followLevels'] = 2;
		}

		$kaosCall['query'] = $query;

		if (!empty($kaosCall['rootSchema']) && !kaosGetCountrySchema($kaosCall['rootSchema']))
			kaosDie('no such schema country');

		if (empty($kaosCall['call'])){
			// API root

			if (KAOS_IS_CLI){
				// API CLI API Root
				require_once APP_PATH.'/templates/CLIRoot.php';
				kaosAPIPrintCLIAPIRoot();
				exit;
			}

			$kaosCall['filter'] = !empty($kaosCall['rootSchema']) ? $kaosCall['rootSchema'] : null;
			$kaosCall['schemas'] = kaosAPIGetSchemas($kaosCall['filter']);

			if (!empty($kaosCall['raw'])){
				$schemas = array();
				foreach ($kaosCall['schemas'] as $s)
					if ($s = kaosGetSchema($s)){
						$schemas[] = array(
							'id' => $s->id,
							'type' => $s->type,
							'name' => $s->name,
							'shortName' => !empty($s->shortName) ? $s->shortName : null,
							'providerId' => !empty($s->providerId) ? $s->providerId : null,
							'region' => !empty($s->region) ? $s->region : null,
							'country' => !empty($s->country) ? $s->country : null,
							'continent' => !empty($s->continent) ? $s->continent : null,
						);
					}
				kaosAPIReturn(array('success' => true, 'query' => array('filter' => $kaosCall['filter']), 'results' => $schemas));
			}

			$kaosCall['outputNoFilter'] = true;
			kaosAPIReturn(include(APP_PATH.'/templates/APIRoot.php'));
			exit;
		}

		$query['schema'] = array();
		if (!empty($kaosCall['rootSchema'])){
			$query['schema'][] = $kaosCall['rootSchema'];
			unset($kaosCall['rootSchema']);
		}
		while ($bits && preg_match('#^([a-z0-9_]+)$#i', $bits[0]))
			$query['schema'][] = strtoupper(array_shift($bits));

		if (!in_array($kaosCall['call'], array('schema')) && $bits && preg_match('#^\d{4}-\d{2}-\d{2}$#', $bits[0])){
			$query['date'] = array_shift($bits);
			if (date('Y-m-d', strtotime($query['date'])) != $query['date'])
				kaosDie('given date does not exist: '.$query['date'].' (format is YY-MM-DD)');
		}

		if (!in_array($kaosCall['call'], array('schema'))){
			if ($bits && !is_numeric($bits[0])){
				$query['id'] = strtoupper(array_shift($bits));
				if (!preg_match('#^([a-z0-9-_]+)$#i', $query['id']))
					kaosDie('bad id: '.htmlentities($query['id']));

			} else if (empty($query['date'])){
				if (!empty($_GET['url'])){
					if (!($query['url'] = filter_var($_GET['url'], FILTER_VALIDATE_URL)))
						kaosDie('bad url');
				} else
					$query['date'] = date('Y-m-d', strtotime('-1 day'));
			}
		}

		if (!$query['schema'])
			kaosDie('bad schema');
		$query['schema'] = strtoupper(implode('/', $query['schema']));

		if (!empty($_GET['as']) && preg_match('#^([a-z0-9_]+)$#i', $_GET['as']))
			$query['type'] = $_GET['as'];
		else if (empty($query['type']) && !empty($query['id']))
			$query['type'] = 'document';

		if (!kaosIsValidSchemaPath($query['schema']))
			kaosDie('invalid schema');

		if (!($kaosCall['schemaObj'] = kaosGetSchema($query['schema'])))
			kaosDie('no such schema '.$query['schema']);

		if (in_array($kaosCall['call'], array('parse', 'rewind', 'rewindextract', 'extract', 'spide')) && $query['followLevels'] < 1)
			$query['followLevels'] = 1;

		$query['allowProcessedCache'] = empty($_GET['noProcessedCache']) && !in_array($kaosCall['call'], array('rewind', 'rewindextract', 'spide'));
		$kaosCall['query'] = $query;

		switch ($kaosCall['call']){

			case 'schema':
				if (!empty($query['id']) || !empty($query['date']))
					kaosDie('id or date in arguments');

				if (!empty($kaosCall['schemaObj']->providerId))
					$kaosCall['schemaObj']->provider = kaosGetProviderSchema($kaosCall['schemaObj']->providerId, true, false);

				kaosAPIReturn($kaosCall['raw'] ? array(
					'success' => true,
					'query' => $query,
					'result' => $kaosCall['schemaObj']
				) : kaosGetSchema($query['schema'], true));
				exit();

			case 'fetch':
			case 'download':
			case 'lint':
			case 'redirect':
				$bulletinFetcher = new BulletinFetcher();
				$bulletin = $bulletinFetcher->fetchBulletin($query, $kaosCall['call'] == 'redirect');

				if (kaosIsError($bulletin))
					kaosDie($bulletin);

				// iframe inside API
				if (!KAOS_IS_CLI && in_array($kaosCall['call'], array('fetch', 'lint')) && !$kaosCall['raw']){
					$kaosCall['isIframe'] = true;

					kaosAPIReturn('<iframe class="kaos-api-iframe" src="'.kaosCurrentURL(true).'/raw?loadCSS=1" onload="if (kaosGetChromeVersion()) {kaosRedrawElement(this,100)}"></iframe>'); // attempt to fix Chromium bug displaying XML in iframes

				} else {
					$content = $bulletinFetcher->serveBulletin($bulletin, $kaosCall['call'], kaosGetSchemaTitle($kaosCall['schemaObj'], $query), $query);
					if ($kaosCall['raw']){

						$content = htmlentities($content);

						if (!empty($kaosCall['call']) && $kaosCall['call'] == 'lint')
							$content = preg_replace_callback('#(?!href=["\'])(https?://[^"\'\s]+)#ius', function($m){
								return '<a href="'.kaosAnonymize($m[1]).'" target="_blank">'.$m[0].'</a>';
							}, $content);

						$content = nl2br($content);
						if (empty($_GET['loadCSS']))
							echo $content;
						else {
							?>
							<html>
							<head>
								<?php head() ?>
							</head>
							<body class="kaosIframe">
								<div>
									<?= $content ?>
								</div>
							</body>
							</html>
							<?php
						}
					} else
						kaosAPIReturn($content);

				}
				exit();

			case 'parse':
			case 'extract':

				$bulletinParser = new BulletinParser();

				$lock = null;
				if ($kaosCall['call'] == 'rewind'){
					while (!empty($query['date']) && !($lock = lock('rewind-'.$query['schema'].'-'.$query['date'])))
						$query['date'] = date('Y-m-d', strtotime('-1 day', strtotime($query['date'])));
				}

				$ret = $bulletinParser->fetchAndParseBulletin($query);

				if (kaosIsError($ret)){
					unlock($lock);
					kaosDie($ret->msg);
				}

				if ($kaosCall['call'] == 'extract'){
					$extracter = new BulletinExtractor($ret);
					$ret = $extracter->extract($kaosCall['query'], isAdmin() && !empty($_GET['save']));
					$kaosCall['apiResultPreview'] = $extracter;
				}

				unlock($lock);
				kaosAPIReturn(array(
					'success' => true,
					'query' => $query,
					'result' => $ret
				));
				exit;

			case 'rewindextract':
				if (!KAOS_IS_CLI && $kaosCall['call'] == 'rewindextract')
					kaosDie();
					
			case 'rewind':
				if (KAOS_IS_CLI){
					define('KAOS_FORCE_OUTPUT', true);

					$kaosCall['spiderConfig'] = array(
						'schema' => $kaosCall['query']['schema'],
						'status' => 'manual',
						'dateBack' => $kaosCall['query']['date'],
						'extract' => $kaosCall['call'] == 'rewindextract',
					) + kaosGetDefaultSpiderConfig(false);

					define('KAOS_SPIDER_ID', 0);
					require(APP_PATH.'/spider/spider.php');
					exit();
				} 
				kaosAPIReturn(include(APP_PATH.'/templates/APIMapYear.php'));
				exit;
				
			case 'soldiers':
				kaosAPIReturn(include(APP_PATH.'/templates/Soldiers.php'));
				exit;
				
			case 'ambassadors':
				kaosAPIReturn(include(APP_PATH.'/templates/Ambassadors.php'));
				exit;

		}
		kaosDie('unknown api call');
	}
}

