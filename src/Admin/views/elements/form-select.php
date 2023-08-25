<div class="form-group">
	<? if($name !== false) { ?>
	<label for="<?=$id?>"><?=$name?></label>
	<? } ?>
	<select name="<?=$id?>" id="<?=$id?>" class="form-select <?=$class?>" <?=$atts?>>
		<?
		if($placeholder) {
			echo '<option value="" selected disabled hidden>'.$placeholder.'</option>';
		}
		foreach($options as $id => $name) {
			if(is_array($name)) {
				$id = $name["id"];
				$name = $name["name"];
			}
			$selected = ($id == $value ? "selected" : "");
			?>
			<option value="<?=$id?>" <?=$selected?>><?=$name?></option>
			<?
		}
		?>
	</select>
</div>
