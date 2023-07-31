<div class="card">
	<div class="card-header">
		Log
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-4">
				<b>User #<?=$user->id?></b><br/><?=$user->email?>
			</div>
			<div class="col-4">
				<b><?=$log->action?></b><br/>
				at (<?=$log->created_at?>)
			</div>
			<div class="col-4">
				<?
				\Magrathea2\Admin\AdminElements::Instance()->
					Textarea(
						"data_log".$log->id,
						"Data for Log ".$log->id,
						$log->data,
						"log-data-area"
					);
				?>
			</div>
		</div>
	</div>
</div>
