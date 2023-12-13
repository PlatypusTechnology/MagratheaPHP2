<div class="form-check <?=($switch ? "form-switch" : "")?> <?=@$outerClass?>">
  <input type="checkbox" value="<?=$value?>" name="<?=$id?>" id="<?=$id?>" class="form-check-input <?=$class?>" <?=$atts?> <?=($checked ? "checked" : "")?>>
  <label class="form-check-label" for="<?=$id?>">
    <?=$name?>
  </label>
</div>
