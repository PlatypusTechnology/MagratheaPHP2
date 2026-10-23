<div class="form-group">
	<?php

use function Magrathea2\isMagratheaModel;

 if($name !== false) { ?>
	<label for="<?=$id?>"><?=$name?></label>
	<?php } ?>
	<select name="<?=$id?>" id="<?=$id?>" class="form-select <?=$class?>" <?=$atts?>>
		<?php
		if($placeholder) {
			echo '<option value="" selected disabled hidden>'.$placeholder.'</option>';
		}
		foreach($options as $id => $name) {
			if(is_array($name)) {
				$id = $name["id"];
				$name = $name["name"];
			}
			if(isMagratheaModel($name)) {
				$name = $name->Ref();
			}
			$selected = ($id == $value ? "selected" : "");
			?>
			<option value="<?=htmlspecialchars((string)$id, ENT_QUOTES)?>" <?=$selected?>><?=htmlspecialchars((string)$name, ENT_QUOTES)?></option>
			<?php
		}
		?>
	</select>
</div>
