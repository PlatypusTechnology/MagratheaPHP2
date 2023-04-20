<?
if($submit) {
	?>
<button class="btn <?=$class?>" id="<?=$btnId?>" style="<?=$btnStyles?>" type="submit" name="magrathea-submit" value="<?=@$click?>">
	<?
} else {
	?>
<button class="btn <?=$class?>" id="<?=$btnId?>" style="<?=$btnStyles?>" onclick="event.preventDefault(); <?=$click?>">
	<?
}
?>
	<?=$name?>
</button>
