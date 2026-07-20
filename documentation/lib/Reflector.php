<?php

namespace AiDocs;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionException;

/**
 * Pulls live, always-accurate class/method signatures straight from src/ via
 * PHP Reflection, so the reference data can never drift from the code the
 * way hand-written narrative docs can. See documentation/CLAUDE.md.
 */
class Reflector {

	public static function ReflectClass(string $fqcn): ?array {
		try {
			$rc = new ReflectionClass($fqcn);
		} catch (ReflectionException $e) {
			return null;
		}

		$methods = [];
		foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
			if ($m->getDeclaringClass()->getName() !== $rc->getName()) continue;
			if (str_starts_with($m->getName(), "__") && !in_array($m->getName(), ["__construct"])) continue;
			$methods[] = self::ReflectMethod($m);
		}
		usort($methods, fn($a, $b) => $a["static"] <=> $b["static"] ?: strcmp($a["name"], $b["name"]));

		$props = [];
		foreach ($rc->getProperties() as $p) {
			if (!$p->isPublic()) continue;
			if ($p->getDeclaringClass()->getName() !== $rc->getName()) continue;
			$type = $p->getType();
			$props[] = [
				"name" => $p->getName(),
				"type" => $type ? self::TypeToString($type) : null,
				"static" => $p->isStatic(),
				"doc" => self::ParseDocblock($p->getDocComment() ?: ""),
			];
		}

		return [
			"name" => $rc->getShortName(),
			"fqcn" => $rc->getName(),
			"file" => $rc->getFileName() ? str_replace(DocMap::ROOT . "/", "", $rc->getFileName()) : null,
			"abstract" => $rc->isAbstract(),
			"interface" => $rc->isInterface(),
			"parent" => $rc->getParentClass() ? $rc->getParentClass()->getShortName() : null,
			"interfaces" => array_map(fn($i) => $i->getShortName(), $rc->getInterfaces()),
			"classDoc" => self::ParseDocblock($rc->getDocComment() ?: ""),
			"methods" => $methods,
			"properties" => $props,
		];
	}

	private static function ReflectMethod(ReflectionMethod $m): array {
		$params = [];
		foreach ($m->getParameters() as $p) {
			$type = $p->getType();
			$default = null;
			if ($p->isDefaultValueAvailable()) {
				try { $default = self::ValueToString($p->getDefaultValue()); } catch (\Throwable $e) { $default = null; }
			}
			$params[] = [
				"name" => $p->getName(),
				"type" => $type ? self::TypeToString($type) : null,
				"optional" => $p->isOptional(),
				"default" => $default,
				"variadic" => $p->isVariadic(),
			];
		}
		$returnType = $m->getReturnType();

		return [
			"name" => $m->getName(),
			"static" => $m->isStatic(),
			"params" => $params,
			"returns" => $returnType ? self::TypeToString($returnType) : null,
			"doc" => self::ParseDocblock($m->getDocComment() ?: ""),
			"signature" => self::BuildSignature($m, $params, $returnType),
		];
	}

	private static function BuildSignature(ReflectionMethod $m, array $params, $returnType): string {
		$parts = [];
		foreach ($params as $p) {
			$s = "";
			if ($p["type"]) $s .= $p["type"] . " ";
			if ($p["variadic"]) $s .= "...";
			$s .= "$" . $p["name"];
			if ($p["default"] !== null) $s .= " = " . $p["default"];
			$parts[] = $s;
		}
		$sig = ($m->isStatic() ? "static " : "") . $m->getName() . "(" . implode(", ", $parts) . ")";
		if ($returnType) $sig .= ": " . self::TypeToString($returnType);
		return $sig;
	}

	private static function TypeToString($type): string {
		if ($type instanceof ReflectionUnionType) {
			return implode("|", array_map(fn($t) => self::TypeToString($t), $type->getTypes()));
		}
		if ($type instanceof ReflectionNamedType) {
			return ($type->allowsNull() && $type->getName() !== "null" && $type->getName() !== "mixed" ? "?" : "") . $type->getName();
		}
		return (string)$type;
	}

	private static function ValueToString($v): string {
		if (is_array($v)) return empty($v) ? "[]" : "[...]";
		if (is_string($v)) return "\"" . (strlen($v) > 20 ? substr($v, 0, 20) . "..." : $v) . "\"";
		if (is_bool($v)) return $v ? "true" : "false";
		if (is_null($v)) return "null";
		return (string)$v;
	}

	/** Minimal docblock parser: summary + @param/@return/@var/@throws tags. */
	public static function ParseDocblock(string $raw): array {
		$raw = trim($raw);
		if ($raw === "") return ["summary" => "", "tags" => []];
		$lines = explode("\n", $raw);
		$clean = [];
		foreach ($lines as $l) {
			$l = trim($l);
			$l = preg_replace('#^/\*\*#', '', $l);
			$l = preg_replace('#\*/$#', '', $l);
			$l = preg_replace('#^\*\s?#', '', $l);
			$clean[] = $l;
		}
		$summary = [];
		$tags = [];
		foreach ($clean as $l) {
			if (preg_match('/^@(\w+)\s*(.*)$/', $l, $m)) {
				$tags[] = ["tag" => $m[1], "value" => trim($m[2])];
			} elseif (trim($l) !== "" || !empty($summary)) {
				if (trim($l) !== "") $summary[] = trim($l);
			}
		}
		return ["summary" => implode(" ", $summary), "tags" => $tags];
	}

	/** All classes under src/ mapped fqcn => relative file path. */
	public static function AllClasses(): array {
		$root = DocMap::ROOT . "/src";
		$out = [];
		$rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
		foreach ($rii as $file) {
			if ($file->getExtension() !== "php") continue;
			$rel = str_replace($root . "/", "", $file->getPathname());
			$src = file_get_contents($file->getPathname());
			if (!preg_match('/^namespace\s+([^;]+);/m', $src, $ns)) continue;
			if (!preg_match('/^(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+(\w+)/m', $src, $cl)) continue;
			$fqcn = trim($ns[1]) . "\\" . $cl[1];
			$out[$fqcn] = $rel;
		}
		ksort($out);
		return $out;
	}
}
