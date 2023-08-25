<div class="form-group">
	<? if(!$hideLabel) { ?>
	<label for="<?=$id?>"><?=$name?></label>
	<? } ?>
	<input type="date" name="<?=$id?>" id="<?=$id?>" value="<?=$value?>" placeholder="<?=$placeholder?>" class="form-control <?=$class?>"/>
</div>
