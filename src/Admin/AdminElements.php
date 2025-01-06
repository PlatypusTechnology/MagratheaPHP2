<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Singleton;
use Magrathea2\MagratheaPHP;

use function Magrathea2\p_r;

#######################################################################################
####
####    MAGRATHEA PHP2 Admin elements
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for Admin Elements
 */
class AdminElements extends Singleton {

	/**
	 * Returns the content instead of printing it
	 * @return AdminElements	itself
	 */
	public function Buffer(): AdminElements {
		ob_start();
		return $this;
	}

	/**
	 * Gets content Buffered
	 * @return string		content
	 */
	public function Get(): string {
		return ob_get_clean();
	}

	/**
	 * Prints a table
	 * @param array $rows  	row result
	 * @param array	$cols		cols (can be in [ title, key ] format)
	 */
	public function Table($rows, $cols=null, $extraClass=""): void {
		if(is_array($extraClass)) $extraClass = implode(' ', $extraClass);
		if(count($rows) === 0) {
			echo "Empty Table";
			return;
		}
		$magratheaRows = array_map(function($i) {
			if(is_a($i, "\Magrathea2\MagratheaModel")) {
				return $i;
			} else if(is_string($i)) {
				return explode(';', $i);
			} else {
				return $i;
			}
		}, $rows);

		if($cols === null) {
			if(!@$magratheaRows[0]) {
				$this->ErrorCard("table should work with an array of arrays");
				return;
			}
			$magratheaKeys = array_keys($magratheaRows[0]);
			$magratheaTableTitles = $magratheaKeys;
		} else {
			$magratheaKeys = [];
			$magratheaTableTitles = [];
			foreach($cols as $k => $value) {
				if(is_int($k)) {
					if(is_array($value)) {
						array_push($magratheaKeys, $value["key"]);
						array_push($magratheaTableTitles, $value["title"]);
					} else {
						array_push($magratheaKeys, $value);
						array_push($magratheaTableTitles, $value);
					}
				} else {
					array_push($magratheaKeys, $k);
					array_push($magratheaTableTitles, $value);
				}
			}
		}

		$view = __DIR__."/views/elements/table.php";
		include($view);
	}

	/**
	 * Displays an alert
	 * @param string $alertMessage 		HTML text
	 * @param string $type						style - available types: primary, secondary, success, danger, warning, info, light, dark
	 */
	public function Alert($alertMessage, $type="primary", $showClose=true) {
		$alertClass = "alert-".$type;
		$view = __DIR__."/views/elements/alert.php";
		include($view);
	}

	/**
	 * Displays an error card
	 * @param string $message 	text of the card
	 * @param string $title			title of the card
	 */
	public function ErrorCard($message, $title="Error!") {
		$errorCardTitle = $title;
		$errorCardText = $message;
		$view = __DIR__."/views/elements/error-card.php";
		include($view);
	}

	/**
	 * Prints page header
	 * @param string $title			title
	 * @param string $error 		error message (default: null)
	 */
	public function Header($title, $error = null) {
		$pageTitle = $title;
		if($error) {
			$errorMsgHeader = $error;
		}
		$view = __DIR__."/views/elements/header.php";
		include($view);
	}

	private function GetAttributesStr($attr): string {
		if(!$attr) return "";
		$html = "";
		foreach($attr as $at => $val) {
			$html .= $at.'="'.$val.'"';
		}
		return $html;
	}

	/**
	 * Print a Select
	 * @param string 	$id	 			id
	 * @param string 	$name 		title of the select
	 * @param array 	$options	array with options (as ["value" => "option caption"] or ["id", "name"])
	 * @param string 	$value 		selected value
	 * @param string|array $class		class for the select
	 * @param string 	$placeholder 	placeholder
	 * @param	array		$attributes		any extra attributes (as ["onchange" => "alert();"])
	 * @return AdminElements	itself
	 */
	public function Select($id, $name="", $options=null, $value="", $class="", $placeholder="", $attributes=[]): AdminElements {
		if(empty($name) && $name !== false) $name = $id;
		if(is_array($class)) $class = implode(' ', $class);
		$atts = $this->GetAttributesStr($attributes);
		$view = __DIR__."/views/elements/form-select.php";
		include($view);
		return $this;
	}

	/**
	 * Print a Input
	 * @param string 	$type	 		type (as "text", "disabled", "number", "email", "date")
	 * @param string 	$id	 			id
	 * @param string 	$name 		title of the input (default: $id)
	 * @param string	$value		value for input (default: "")
	 * @param string|array $class				class for the input
	 * @param string|array $outerClass	class for the container of input
	 * @param string	$placeholder	placeholder
	 * @param	array	$attributes		any extra attributes (as ["onkeyup" => "alert();"])
	 * @return AdminElements	itself
	 */
	public function Input($type, $id, $name="", $value="", $class="", $outerClass="", $placeholder="", $attributes=[]): AdminElements {
		$hideLabel = $name === false;
		if(empty($name)) $name = $id;
		if(is_array($class)) $class = implode(' ', $class);
		if($type === "disabled") {
			$typeStr = 'type="text" readonly';
			$class .= " readonly";
		} else {
			$typeStr = 'type="'.$type.'"';
		}
		$atts = $this->GetAttributesStr($attributes);
		$view = __DIR__."/views/elements/form-input.php";
		include($view);
		return $this;
	}

	/**
	 * Print a Checkbox
	 * @param string $id	 		id
	 * @param string $name 		title of the select (default: $id)
	 * @param string $value		value for input (default: true)
	 * @param string $checked	should it be checked? (default: false)
	 * @param string|array $class		class for the select
	 * @param string $switch	should be a switch? (default: false)
	 * @param	array	$attributes		any extra attributes (as ["onchange" => "alert();"])
	 * @return AdminElements	itself
	 */
	public function Checkbox($id, $name="", $value=true, $checked=false, $class=[], $switch=false, $attributes=[], $outerClass=""): AdminElements {
		if(empty($name)) $name = $id;
		if(is_array($class)) $class = implode(' ', $class);
		$atts = $this->GetAttributesStr($attributes);
		$view = __DIR__."/views/elements/form-checkbox.php";
		include($view);
		return $this;
	}

	/**
	 * Print a Input
	 * @param string 	$id	 			id
	 * @param string 	$name 		title of the input (default: $id)
	 * @param string	$value		value for input (default: "")
	 * @param string|array $class				class for the input
	 * @param string|array $outerClass	class for the container of input
	 * @param string	$placeholder	placeholder
	 * @param	array	$attributes		any extra attributes (as ["onkeyup" => "alert();"])
	 * @return AdminElements	itself
	 */
	public function Textarea($id, $name="", $value="", $class="", $outerClass="", $placeholder="", $attributes=[]): AdminElements {
		$hideLabel = $name === false;
		if(empty($name)) $name = $id;
		if(is_array($class)) $class = implode(' ', $class);
		$atts = $this->GetAttributesStr($attributes);
		$view = __DIR__."/views/elements/form-textarea.php";
		include($view);
		return $this;
	}

	/**
	 * Print a button
	 * @param string $name 		caption of the button
	 * @param string $click		action of the button
	 * @param string|array $class				class for the button
	 * @param string|array $outerClass	class for the button
	 * @param boolean $submit	is it a type=submit button? (default:false)
	 * @param	array	$extraArgs		extra data: [ "id", "styles" ]
	 * @return AdminElements	itself
	 */
	public function Button($name, $click, $class="btn-primary", $outerClass="", $submit=false, $extraArgs=[]): AdminElements {
		if(is_array($class)) $class = implode(' ', $class);
		if(empty($click)) $click = "";
		$click = str_replace('"', "'", $click);
		$btnId = @$extraArgs["id"] ? $extraArgs["id"] : "";
		$btnStyles = @$extraArgs["styles"] ? $extraArgs["styles"] : "";
		$view = __DIR__."/views/elements/form-button.php";
		include($view);
		return $this;
	}

	public function UserCard() {
		$user = AdminManager::Instance()->GetLoggedUser();
		$view = __DIR__."/views/elements/user-card.php";
		include($view);
		return $this;
	}

}

?>