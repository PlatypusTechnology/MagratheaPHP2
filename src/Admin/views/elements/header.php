<div class="bg-light border-bottom page-header page-header">
	<div class="container-fluid">
		<!--button class="btn btn-primary">Toggle Menu</button-->
		<h3><?=$pageTitle?></h3>
		<div class="collapse navbar-collapse">
			<ul class="navbar-nav ms-auto mt-2 mt-lg-0">
				<?php
				if(@$errorMsgHeader) {
					?>
					<li class="nav-item">
						<span class="nav-link error">Error: <?=$errorMsgHeader?></span>
					</li>
					<?php
				}
				?>
				<!--li class="nav-item active"><a class="nav-link" href="#!">Home</a></li>
				<li class="nav-item"><a class="nav-link" href="#!">Link</a></li-->
			</ul>
		</div>
		<!--div class="navbar-nav">
		</div-->
	</div>
</div>
