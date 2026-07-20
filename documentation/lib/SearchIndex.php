<?php

namespace AiDocs;

class SearchIndex {

	public static function Build(): array {
		$base = self::BaseUrl();
		$out = [];

		foreach (DocMap::Tree() as $catKey => $cat) {
			foreach ($cat["pages"] as $slug => $page) {
				$out[] = [
					"title" => $page["title"],
					"type" => "guide",
					"sub" => $cat["title"],
					"url" => "$base/?p=$catKey/$slug",
				];
				foreach ($page["src"] as $srcFile) {
					if (!DocMap::HasClass($srcFile)) continue;
					$fqcn = DocMap::FqcnFromSrc($srcFile);
					$data = Reflector::ReflectClass($fqcn);
					if (!$data) continue;
					$out[] = [
						"title" => $data["name"],
						"type" => "class",
						"sub" => $data["fqcn"],
						"url" => "$base/?p=$catKey/$slug#class-" . $data["name"],
					];
					foreach ($data["methods"] as $m) {
						$out[] = [
							"title" => $data["name"] . "::" . $m["name"] . "()",
							"type" => "method",
							"sub" => $m["doc"]["summary"] ?? "",
							"url" => "$base/?p=$catKey/$slug#m-" . $data["name"] . "-" . $m["name"],
						];
					}
				}
			}
		}
		return $out;
	}

	private static function BaseUrl(): string {
		$dir = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? "/documentation"), "/");
		return $dir;
	}
}
