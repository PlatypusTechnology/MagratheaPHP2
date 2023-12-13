<?php

use Magrathea2\Admin\AdminElements;

$elements = AdminElements::Instance();

?>
<form action="" method="post" name="<?=$formName?>" id="<?=$formName?>">
	<div class="row">	
	<?php
		foreach($formElements as $el) {
			echo '<div class="'.@$el["size"].'">';
			$key = @$el["key"];
			$name = @$el["name"];
			if($values) {
				if(!empty($key) && !is_callable($key)) {
					if(is_array($values)) {
						$value = @$values[$key];
					} else {
						if(property_exists($values, $key)) {
							$value = $values->$key;
						}
					}
					if(is_callable($value)) {
						$value = $value($el);
					}
				} else { $value = ""; }
			} else { $value = ""; }
			$type = @$el["type"];
			if(is_callable($type) && $type !== "date") {
				$type($values);
			} else if(is_array($type)) {
				$elements->Select($key, $name, $type, $value, @$el["class"], @$el["placeholder"], @$el['attributes']);
			} else {
				switch($type) {
					case "empty":
					default:
						$class = @$el['class'] ? $el['class'] : "";
						echo '<div id="'.$key.'" class="'.$class.'">'.$value.'</div>';
						break;
					case "text":
					case "email":
					case "number":
					case "date":
					case "disabled":
						$elements->Input($type, $key, $name, $value, @$el['class'], @$el['outerClass'], @$el['placeholder'], @$el['attributes']);
						break;
					case "hidden":
						echo '<input type="hidden" name="'.$key.'" id="'.$key.'" value="'.$value.'" />';
						break;
					case "textarea":
						$elements->Textarea($key, $name, $value, @$el['class'], @$el['outerClass'], @$el['placeholder'], @$el['attributes']);
						break;
					case "switch":
					case "checkbox":
						$checkVal = "1";
						if(@$el["placeholder"]) $checkVal = $el["placeholder"];
						$elements->Checkbox($key, $name, $checkVal, ($value==true), @$el['class'], ($type == "switch"), @$el['attributes'], @$el['outerClass']);
						break;
					case "delete-button":
						$name = $name ? $name : "Delete";
						$class = @$el['class'] ? $el['class'] : "btn-danger w-100";
						$outerClass = @$el['outerClass'] ? $el['outerClass'] : "";
						$elements->Button($name, "delete", $class, $outerClass, true, @$el['attributes']);
						break;
					case "save-button":
						$name = $name ? $name : "Save";
						$class = @$el['class'] ? $el['class'] : "btn-success w-100";
						$outerClass = @$el['outerClass'] ? $el['outerClass'] : "";
						$elements->Button($name, "save", $class, $outerClass, true, @$el['attributes']);
						break;
					case "submit":
						$elements->Button($name, $key, @$el['class'], @$el['outerClass'], true, @$el['attributes']);
						break;
					case "button":
						if(is_callable($key)) {
							$click = $key($values);
						} else {
							if(@$el["action"]) {
								$click = $el["action"];
							} else {
								$click = $key;
							}
						}
						$elements->Button($name, $click, @$el['class'], @$el['outerClass'], false, @$el['attributes']);
						break;
				}	
			}
			$name = @$el["name"];
			echo '</div>';
		}
	?>
	</div>
</form>
