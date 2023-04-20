<?php

if(isset($viewFile)) {
	$file = $viewFile;
} else {
	$file = @$_POST["file"];
}

if(!file_exists($file)) {
	$msg = "file [".$file."] does not exist";
	\Magrathea2\Admin\AdminElements::Instance()->Alert($msg, "danger");
	die;
}

echo '<pre class="code file-pre">';
$lines = @$_POST["lines"];
if(empty($lines)) {
	echo file_get_contents($file);
	die;
}

function getLastLines($filename, $lines) {
	$fp = fopen($filename, 'r');
	$linecount = 0;
	$pos = -1;
	$text = '';
	$buffer = '';

	while ($linecount < ($lines + 1) && fseek($fp, $pos, SEEK_END) >= 0) {
		$c = fgetc($fp);
		if ($c === "\n") {
				$linecount++;
		}
		$text = $c . $buffer;
		$buffer = $text;
		$pos--;
	}
	fclose($fp);
	return $text;
}
echo getLastLines($file, $lines);

echo '</pre>';
