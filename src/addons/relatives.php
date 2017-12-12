<?php

if (!defined('BASE_PATH'))
	die();



add_action('entity_stats_after', 'kaosEntityRelatives');
function kaosEntityRelatives($entity){
	
	if ($entity['type'] != 'person')
		return;
		
	$likes = $order = array();
	foreach (explode(' ', $entity['name']) as $name){
		$nameInner = mb_strtolower(remove_accents($name));
		
		$likes[] = 'e.name LIKE '.esc_like($nameInner.' ', 'right');
		$likes[] = 'e.name LIKE '.esc_like(' '.$nameInner, 'left');
		$likes[] = 'e.name LIKE '.esc_like(' '.$nameInner.' ', 'both');
		
		$order[] = 'e.name LIKE '.esc_like($nameInner.' ', 'right').' DESC';
		$order[] = 'e.name LIKE '.esc_like(' '.$nameInner, 'left').' DESC';
	}
	
	if (!$likes)
		return;
	$relatives = query('SELECT e.id, e.country, e.slug, e.type, e.name, e.first_name FROM entities AS e WHERE e.type = "person" AND ( '.implode(' OR ', $likes).' ) AND e.id != %s ORDER BY '.implode(', ', $order).', e.name ASC, e.first_name ASC', $entity['id']);
				
	if ($relatives){ 
		?>
		<div class="entity-sheet-detail entity-relatives">
			<div class="entity-sheet-label">Possible relatives: </div>
			<div class="entity-sheet-body">
				<div class="entity-relatives-inner">
					<ul><?php

						$i = 0;
						foreach ($relatives as $e){
							$title = kaosGetEntityTitle($e);
							foreach (explode(' ', $entity['name']) as $name)
								$title = preg_replace('#'.preg_quote($name, '#').'#ius', '<strong>'.mb_strtoupper($name).'</strong>', $title);
							echo '<li><a href="'.kaosGetEntityUrl($e).'">'.$title.'</a>';
							
							$common = array();
							foreach (kaosGetEntitiesCommonCompanies($entity, $e) as $c)
								$common[] = '<a href="'.kaosGetEntityUrl($c).'">'.kaosGetEntityTitle($c).'</a>';
							if ($common)
								echo '<div class="entity-relatives-common"><i class="fa fa-angle-right"></i> '.count($common).' linking companies: '.implode(', ', $common).'</div>';
							
							echo '</li>';
							$i++;
						}
					?></ul>
				</div>
			</div>
		</div>
		<?php
	}
}


function kaosGetEntitiesCommonCompanies($e1, $e2){
	return query('
		SELECT c.id, c.type, c.subtype, c.name, c.slug, c.country
		FROM entities AS e1
		LEFT JOIN statuses AS s1 ON e1.id = s1.target_id OR e1.id = s1.related_id
		LEFT JOIN entities AS c ON s1.related_id = c.id OR s1.target_id = c.id
		LEFT JOIN statuses AS s2 ON c.id = s2.target_id OR c.id = s2.related_id
		LEFT JOIN entities AS e2 ON s2.related_id = e2.id OR s2.target_id = e2.id
		WHERE e1.id = %s AND e2.id = %s AND c.id != e1.id AND c.id != e2.id
		GROUP BY c.id
	', array($e1['id'], $e2['id']));
}
