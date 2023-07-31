<? use function Magrathea2\p_r; ?>
<table class="table table-striped <?=$extraClass?>">
	<thead class="thead-dark">
		<tr>
		<?
			foreach($magratheaTableTitles as $h) {
				echo '<th scope="col">'.$h.'</th>';
			}
		?>
		</tr>
	</thead>
	<tbody>
	<?
	foreach($magratheaRows as $row) {
		echo "<tr>";
		$i = 0;
		foreach($magratheaKeys as $key) {
			if(is_callable($key)) {
				$value = $key($row);
			} else if (is_array($row)) {
				if(isset($row[$key])) $value = $row[$key];
				else $value = @$row[$i];

				if(is_callable($value)) {
					$value = $value($row);
				}
			} else if (is_a($row, "\Magrathea2\MagratheaModel")) {
				$value = $row->$key;
			}
			echo '<td '.($i === 0 ? 'scope="row"' : '').'>';
			echo $value.'</td>';
			$i++;
		}
		echo "</tr>";
		?>
		<?
		/*
		?>
		<tr>
			<th scope="row"><?=$row->id?></th>
			<td><?=$row->email?></td>
			<td><?=$row->last_login?></td>
			<td>
				<a href="?page=users&user=<?=$row->id?>">Edit</a>
			</td>
		</tr>
		<?
		*/
	}
	?>
	</tbody>
</table>
