<div class="bg-light border-bottom page-header page-header">
	<div class="container-fluid">
		<div class="row">
			<div class="col-9 header-center">
				<span class="header-title"><?=$pageTitle?></span>
				<?php
					if(@$errorMsgHeader) {
						?>
						<span class="header-error error">Error: <?=$errorMsgHeader?></span>
						<?php
					}
					?>
			</div>
			<div class="col-3 header-right">
				<?
					\Magrathea2\Admin\AdminElements::Instance()->UserCard();
				?>
			</div>
		</div>
	</div>
</div>
