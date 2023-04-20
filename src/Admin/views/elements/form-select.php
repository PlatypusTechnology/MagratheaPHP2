<div class="form-group">
	<label for="<?=$id?>"><?=($name !== false ? $name : "")?></label>
	<select name="<?=$id?>" id="<?=$id?>" class="form-select <?=$class?>" <?=$atts?>>
		<?
		if($placeholder) {
			echo '<option value="" selected disabled hidden>'.$placeholder.'</option>';
		}
		foreach($options as $id => $name) {
			$selected = ($id == $value ? "selected" : "");
			?>
			<option value="<?=$id?>" <?=$selected?>><?=$name?></option>
			<?
		}
		?>
	</select>
</div>
