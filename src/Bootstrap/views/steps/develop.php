<?php

use Magrathea2\MagratheaPHP;

	$documentation = MagratheaPHP::GetDocumentationLink();

?>

<h3>Develop!</h3>

<div class="row">
  <div class="col-12">
		Documentation for MagratheaPHP can be found in: <a href="<?=$documentation?>" target="_blank"><?=$documentation?></a>
	</div>
	<div class="col-12">
		<div id="develop-rs" style="display: none;"></div>
	</div>
</div>
