<?php

die('DEPRECATED!');

if (!defined('BASE_PATH'))
	die();

global $kaosCall;	
extract($vars);
$kaosCall['outputNoFilter'] = true;
ob_start();

$conv = array(
	'institution' => 'Institutions',
	'company' => 'Companies',
	'person' => 'People',
);
$kaosCall['pageTitle'] = $conv[$entityType];

?>
<div>
	<?php
		$type = null;
		$entities = array();
		foreach (getCol('SELECT DISTINCT subtype FROM entities WHERE type = %s', $entityType) as $type)
			$entities = array_merge($entities, query('
				SELECT e.type, e.country, e.subtype, e.name, e.first_name, e.slug, COUNT(s.id) AS count
			
				FROM entities AS e
				LEFT JOIN statuses AS s ON e.id = s.related_id OR e.id = s.target_id
				
				WHERE e.type = %s AND e.subtype = %s
				GROUP BY e.id
				ORDER BY e.subtype ASC, count DESC, e.first_name ASC, e.name ASC
				LIMIT 10
			', array($entityType, $type)));
		
		foreach ($entities as $e){
			
			if ($type === null || $type != $e['subtype']){
				if ($type !== null)
					echo '</ul></div>';
					
				if ($entityType == 'company'){
					if (!empty($e['subtype'])){
						$s = kaosGetCountrySchema($e['country']);
						?><h3><?= $s->vocabulary->legalEntityTypes->{$e['subtype']}->name ?> (<?= $e['subtype'] ?>)</h3><?php
					} else
						echo '<h3>Unknown type</h3>';
				}
					
				$type = !empty($e['subtype']) ? $e['subtype'] : false;
				
				echo '<div class="entities-list"><ul>';
			}
			?>
			<li><a href="<?= kaosGetEntityUrl($e) ?>"><?php 
			
				if ($entityType == 'person')
					echo empty($e['first_name']) ? '<strong>'.mb_strtoupper($e['name']).'</strong>' : '<strong>'.mb_strtoupper($e['first_name']).'</strong>'.' '.$e['name'];
				else
					echo kaosGetEntityTitle($e, true);
					
			?> (<?= $e['count'] ?>)</a></li>
			<?php
		}
		if ($type !== null)
			echo '</ul></div>';
	?>
</div>
<?php
kaosAPIReturn(ob_get_clean());

