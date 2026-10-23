<div class="form-group">
	<?php if(!$hideLabel) { ?>
	<label for="<?=$id?>"><?=$name?></label>
	<?php } ?>
	<input type="date" name="<?=$id?>" id="<?=$id?>" value="<?=htmlspecialchars((string)$value, ENT_QUOTES)?>" placeholder="<?=$placeholder?>" class="form-control <?=$class?>"/>
</div>
