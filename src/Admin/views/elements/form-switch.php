<div class="form-check">
  <input type="checkbox" value="<?=htmlspecialchars((string)$value, ENT_QUOTES)?>" id="<?=$id?>" class="form-check-input <?=$class?>" <?=$atts?>>
  <label class="form-check-label" for="<?=$id?>">
    <?=$name?>
  </label>
</div>
