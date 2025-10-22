<?php

use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\MagratheaPHP;

MagratheaPHP::Instance()->Connect();

$control = new AdminUserControl();
$users = $control->GetAll();

foreach ($users as $u) {
	?>
	<br/>
	<div class="row">
		<div class="col-4">
			->
			<?=$u->email?>
		</div>
		<div class="col-2">
			<button class="btn btn-primary" onclick="resetUser(<?=$u->id?>);">Reset</button>
		</div>
		<div class="col-4">
			<input type="text" disabled id="pass_<?=$u->id?>" />
		</div>
	</div>
	<?php
}



