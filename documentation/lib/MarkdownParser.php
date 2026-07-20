<?php

namespace AiDocs;

/**
 * Tiny self-contained Markdown -> HTML converter (no composer dependency).
 * Covers what mds/*.md actually uses: headings, fenced code, tables,
 * lists, bold/italic/inline-code, links, blockquotes, hr. Not a general-purpose
 * CommonMark implementation - see documentation/CLAUDE.md before extending.
 */
class MarkdownParser {

	public static function ToHtml(string $md): string {
		$md = str_replace("\r\n", "\n", $md);
		$lines = explode("\n", $md);
		$html = [];
		$i = 0;
		$n = count($lines);
		$inList = null; // 'ul' | 'ol'
		$paragraph = [];

		$flushParagraph = function () use (&$paragraph, &$html) {
			if (!empty($paragraph)) {
				$html[] = "<p>" . self::Inline(implode(" ", $paragraph)) . "</p>";
				$paragraph = [];
			}
		};
		$closeList = function () use (&$inList, &$html) {
			if ($inList) { $html[] = "</$inList>"; $inList = null; }
		};

		while ($i < $n) {
			$line = $lines[$i];

			// fenced code block
			if (preg_match('/^```(\w*)\s*$/', $line, $m)) {
				$flushParagraph(); $closeList();
				$lang = $m[1] ?: "text";
				$code = [];
				$i++;
				while ($i < $n && !preg_match('/^```\s*$/', $lines[$i])) { $code[] = $lines[$i]; $i++; }
				$html[] = '<pre class="code-block" data-lang="' . htmlspecialchars($lang) . '"><code>' . self::HighlightPhp(implode("\n", $code), $lang) . '</code></pre>';
				$i++;
				continue;
			}

			// heading
			if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
				$flushParagraph(); $closeList();
				$level = strlen($m[1]);
				$text = trim($m[2]);
				$id = self::Slugify($text);
				$html[] = "<h{$level} id=\"{$id}\">" . self::Inline($text) . "</h{$level}>";
				$i++;
				continue;
			}

			// hr
			if (preg_match('/^---+\s*$/', $line)) {
				$flushParagraph(); $closeList();
				$html[] = "<hr>";
				$i++;
				continue;
			}

			// blockquote
			if (preg_match('/^>\s?(.*)$/', $line, $m)) {
				$flushParagraph(); $closeList();
				$q = [];
				while ($i < $n && preg_match('/^>\s?(.*)$/', $lines[$i], $mm)) { $q[] = $mm[1]; $i++; }
				$html[] = "<blockquote>" . self::Inline(implode("<br>", $q)) . "</blockquote>";
				continue;
			}

			// table
			if (strpos($line, "|") !== false && $i + 1 < $n && preg_match('/^\s*\|?[\s:|-]+\|?\s*$/', $lines[$i + 1])) {
				$flushParagraph(); $closeList();
				$header = self::TableRow($line);
				$i += 2;
				$rows = [];
				while ($i < $n && strpos($lines[$i], "|") !== false && trim($lines[$i]) !== "") {
					$rows[] = self::TableRow($lines[$i]);
					$i++;
				}
				$out = '<div class="table-wrap"><table><thead><tr>';
				foreach ($header as $h) $out .= "<th>" . self::Inline($h) . "</th>";
				$out .= "</tr></thead><tbody>";
				foreach ($rows as $r) {
					$out .= "<tr>";
					foreach ($r as $c) $out .= "<td>" . self::Inline($c) . "</td>";
					$out .= "</tr>";
				}
				$out .= "</tbody></table></div>";
				$html[] = $out;
				continue;
			}

			// list item
			if (preg_match('/^(\s*)([-*]|\d+\.)\s+(.*)$/', $line, $m)) {
				$flushParagraph();
				$type = $m[2] === "-" || $m[2] === "*" ? "ul" : "ol";
				if ($inList !== $type) { $closeList(); $html[] = "<$type>"; $inList = $type; }
				$html[] = "<li>" . self::Inline($m[3]) . "</li>";
				$i++;
				continue;
			}

			// blank line
			if (trim($line) === "") {
				$flushParagraph(); $closeList();
				$i++;
				continue;
			}

			$paragraph[] = $line;
			$i++;
		}
		$flushParagraph();
		$closeList();

		return implode("\n", $html);
	}

	private static function TableRow(string $line): array {
		$line = trim($line);
		$line = trim($line, "|");
		// split on unescaped pipes only, then restore literal \| as |
		$cells = preg_split('/(?<!\\\\)\|/', $line);
		return array_map(fn($c) => str_replace('\|', '|', trim($c)), $cells);
	}

	private static function Inline(string $text): string {
		$text = htmlspecialchars($text, ENT_QUOTES, "UTF-8", false);
		// restore already-escaped entities we may re-introduce below is fine since we escape first
		$text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
		$text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
		$text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text);
		$text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
		return $text;
	}

	private static function Slugify(string $text): string {
		$text = strtolower($text);
		$text = preg_replace('/`/', '', $text);
		$text = preg_replace('/[^a-z0-9]+/', '-', $text);
		return trim($text, "-");
	}

	/**
	 * Real PHP tokenizer (token_get_all) driven highlighter - takes RAW
	 * (unescaped) code and returns safe HTML. A prior regex-chain version
	 * broke because inserted class names like "tok-string" themselves
	 * contain keyword substrings ("string") that a later keyword pass would
	 * re-match; tokenizing first sidesteps that class of bug entirely.
	 */
	public static function HighlightPhp(string $code, string $lang): string {
		if ($lang !== "php" && $lang !== "") return htmlspecialchars($code);

		$src = str_starts_with(ltrim($code), "<?php") ? $code : "<?php\n" . $code;
		$tokens = @token_get_all($src);
		$out = "";
		$skippedOpenTag = false;

		foreach ($tokens as $t) {
			if (is_string($t)) {
				$out .= htmlspecialchars($t);
				continue;
			}
			[$id, $text] = $t;
			if ($id === T_OPEN_TAG && !$skippedOpenTag && !str_starts_with(ltrim($code), "<?php")) {
				$skippedOpenTag = true;
				continue;
			}
			$escaped = htmlspecialchars($text);
			$class = match (true) {
				$id === T_COMMENT || $id === T_DOC_COMMENT => "tok-comment",
				$id === T_CONSTANT_ENCAPSED_STRING || $id === T_ENCAPSED_AND_WHITESPACE => "tok-string",
				$id === T_VARIABLE => "tok-var",
				in_array($id, [T_CLASS, T_EXTENDS, T_IMPLEMENTS, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FUNCTION, T_RETURN, T_NEW, T_USE, T_NAMESPACE, T_IF, T_ELSE, T_ELSEIF, T_FOREACH, T_AS, T_WHILE, T_TRY, T_CATCH, T_THROW, T_ABSTRACT, T_INTERFACE, T_TRAIT, T_CONST, T_INSTANCEOF, T_ARRAY, T_ECHO, T_FOR, T_SWITCH, T_CASE, T_BREAK, T_CONTINUE, T_DEFAULT, T_MATCH]) => "tok-keyword",
				$id === T_STRING && in_array(strtolower($text), ["null", "true", "false", "self", "parent", "string", "int", "float", "bool", "void", "mixed", "object", "callable"]) => "tok-keyword",
				default => null,
			};
			$out .= $class ? '<span class="' . $class . '">' . $escaped . '</span>' : $escaped;
		}
		return $out;
	}
}
