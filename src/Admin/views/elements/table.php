<table class="table table-striped <?=$extraClass?>">
	<thead class="thead-dark">
		<tr>
		<?php
			foreach($magratheaTableTitles as $h) {
				echo '<th scope="col">'.$h.'</th>';
			}
		?>
		</tr>
	</thead>
	<tbody>
	<?php
	// values are data: escaped. Callable columns and callable row values return HTML built by the caller: raw
	$magratheaCellHtml = function($value): string {
		if($value === null) return "";
		if(is_scalar($value)) return htmlspecialchars((string)$value, ENT_QUOTES);
		return htmlspecialchars((string)json_encode($value), ENT_QUOTES);
	};
	foreach($magratheaRows as $row) {
		echo "<tr>";
		$i = 0;
		foreach($magratheaKeys as $key) {
			try {
				$value = null;
				$isHtml = false;
				if(is_callable($key) && !is_string($key)) {
					$value = $key($row);
					$isHtml = true;
				} else if (is_array($row)) {
					if(isset($row[$key])) $value = @$row[$key];
					else $value = @$row[$i];

					if(!is_string($value)) {
						if(is_callable($value)) {
							$value = $value($row);
							$isHtml = true;
						}
					}
				} else if (is_a($row, "\Magrathea2\MagratheaModel")) {
					$value = $row->$key;

				}
				echo '<td '.($i === 0 ? 'scope="row"' : '').'>';
				echo ($isHtml ? $value : $magratheaCellHtml($value)).'</td>';
				$i++;
			} catch(Exception $ex) {
				$e = new Magrathea2\Exceptions\MagratheaException("Error building table: ".$ex->getMessage(), 500, $ex);
				$e->SetData([
					"row" => $row,
					"key" => $key
				]);
				throw $e;
			}
		}
		echo "</tr>";
	}
	?>
	</tbody>
</table>
