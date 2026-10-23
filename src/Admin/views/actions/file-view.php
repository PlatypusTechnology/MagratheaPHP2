<?php

// renders only $viewFile (and the optional $viewLines, for a tail), set by the script that includes this view:
// called directly (?magrathea_subpage=file-view), it's not found, like an unknown action
if(!isset($viewFile)) {
	http_response_code(404);
	echo "action not found";
	return;
}
$file = $viewFile;

if(!file_exists($file)) {
	$msg = "file [".htmlspecialchars($file)."] does not exist";
	\Magrathea2\Admin\AdminElements::Instance()->Alert($msg, "danger");
	return;
}

echo '<pre class="code file-pre">';
$lines = $viewLines ?? null;
if(empty($lines)) {
	$content = file_get_contents($file);
	echo htmlentities($content);
	echo '</pre>';
	return;
}

// a closure, not a function: this view can be included more than once in a request
$getLastLines = function($filename, $lines) {
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
};
// log lines carry request data (URIs, user agents, posted values)
echo htmlspecialchars($getLastLines($file, $lines));

echo '</pre>';
