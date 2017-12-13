<?php

if (!defined('BASE_PATH'))
	die();

global $kaosCall;	
$schema = kaosGetSchema($kaosCall['query']['schema']);

$kaosCall['outputNoFilter'] = true;

$soldiers = !empty($schema->soldiers) ? $schema->soldiers : array();

// update soldiers list from remote Github schema file
if ($remoteSchema = kaosGetRemoteSchema($schema->id))
	$soldiers = !empty($remoteSchema->soldiers) ? $remoteSchema->soldiers : array();

ob_start();
?>
<div>
	<?php
		if (empty($soldiers)){
			echo 'No Schema Soldiers are currently defined for this schema. Please, help this project <a href="'.kaosAnonymize('https://github.com/'.KAOS_GITHUB_REPOSITORY.'/blob/master/documentation/manuals/SOLDIERS.md#top" target="_blank">enrolling as a Soldier now</a>!';
		
		} else {
			?>
			<?= number_format(count($soldiers)) ?> Soldiers defined for bulletin "<?= $schema->name ?>":
			<table class="kaos-table">
				<?php
				foreach ($soldiers as $s){
					?><tr><td>
						<div><?= $s->name ?></div>
						<?php if (!empty($s->users)){ ?>
							<div>
								<?php foreach ($s->users as $u){ ?>
									<a href="https://github.com/<?= $u ?>" target="_blank"><i class="fa fa-github"></i> <?= $u ?></a>
								<?php } ?>
							</div>
						<?php } ?>
					</td></tr><?php
				}
			?>
			</table>
			<?php
		}
	?>
</div>
<?php

return ob_get_clean();
