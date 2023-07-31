<div class="form-group">
	<? if(!$hideLabel) { ?>
	<label for="<?=$id?>"><?=$name?></label>
	<? } ?>
	<textarea name="<?=$id?>" id="<?=$id?>" placeholder="<?=$placeholder?>" class="form-control <?=$class?>"><?=$value?></textarea>
</div>
