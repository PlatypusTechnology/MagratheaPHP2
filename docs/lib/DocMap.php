<?php

namespace AiDocs;

/**
 * Static map of the documentation site's navigation tree.
 * Mirrors mds/index.md by design choice - see docs/CLAUDE.md.
 */
class DocMap {

	const ROOT = __DIR__ . "/../..";

	/**
	 * category key => [ title, icon, [ slug => [ title, mdPath, [srcFiles] ] ] ]
	 * srcFiles are resolved from the doc's **File:** lines when present;
	 * entries below without a doc file are filled in manually (multi-file topics).
	 */
	public static function Tree(): array {
		return [
			"getting-started" => [
				"title" => "Getting Started",
				"icon" => "rocket",
				"pages" => [
					"getting-started" => ["title" => "Installation & Quick Start", "md" => "getting-started.md", "src" => []],
				],
			],
			"core" => [
				"title" => "Core",
				"icon" => "cpu",
				"pages" => [
					"magrathea-php" => ["title" => "MagratheaPHP", "md" => "core/magrathea-php.md", "src" => ["MagratheaPHP.php"]],
					"config" => ["title" => "Config", "md" => "core/config.md", "src" => ["Config.php", "ConfigApp.php", "ConfigFile.php"]],
					"singleton" => ["title" => "Singleton", "md" => "core/singleton.md", "src" => ["Singleton.php"]],
					"helper" => ["title" => "MagratheaHelper", "md" => "core/helper.md", "src" => ["MagratheaHelper.php"]],
					"global-functions" => ["title" => "Global Functions", "md" => "core/global-functions.md", "src" => ["_Functions.php"]],
				],
			],
			"database" => [
				"title" => "Database Layer",
				"icon" => "database",
				"pages" => [
					"database" => ["title" => "Database", "md" => "database/database.md", "src" => ["DB/Database.php"]],
					"query-builder" => ["title" => "Query Builder", "md" => "database/query-builder.md", "src" => ["DB/Query.php"]],
					"orm-model" => ["title" => "MagratheaModel", "md" => "database/orm-model.md", "src" => ["MagratheaModel.php"]],
					"orm-control" => ["title" => "MagratheaModelControl", "md" => "database/orm-control.md", "src" => ["MagratheaModelControl.php"]],
				],
			],
			"api" => [
				"title" => "API Framework",
				"icon" => "api",
				"pages" => [
					"magrathea-api" => ["title" => "MagratheaApi", "md" => "api/magrathea-api.md", "src" => ["MagratheaApi.php"]],
					"api-controller" => ["title" => "MagratheaApiControl", "md" => "api/api-controller.md", "src" => ["MagratheaApiControl.php"]],
					"authentication" => ["title" => "Authentication", "md" => "api/authentication.md", "src" => ["Authentication.php"]],
				],
			],
			"admin" => [
				"title" => "Admin Panel",
				"icon" => "admin",
				"pages" => [
					"admin" => ["title" => "Admin System Overview", "md" => "admin/admin.md", "src" => ["Admin/Admin.php", "Admin/AdminManager.php"]],
					"admin-manager" => ["title" => "AdminManager", "md" => "admin/admin-manager.md", "src" => ["Admin/AdminManager.php"]],
					"admin-features" => ["title" => "Admin Features", "md" => "admin/admin-features.md", "src" => ["Admin/AdminFeature.php"]],
				],
			],
			"utilities" => [
				"title" => "Utilities",
				"icon" => "tool",
				"pages" => [
					"cache" => ["title" => "MagratheaCache", "md" => "utilities/cache.md", "src" => ["MagratheaCache.php"]],
					"mail" => ["title" => "MagratheaMail", "md" => "utilities/mail.md", "src" => ["MagratheaMail.php", "MagratheaMailSMTP.php"]],
					"logger" => ["title" => "Logger", "md" => "utilities/logger.md", "src" => ["Logger.php"]],
					"debugger" => ["title" => "Debugger", "md" => "utilities/debugger.md", "src" => ["Debugger.php"]],
					"compressors" => ["title" => "Compressors", "md" => "utilities/compressors.md", "src" => ["Compressors/CssCompressor.php", "Compressors/JavascriptCompressor.php", "Compressors/MagratheaCompressor.php"]],
				],
			],
			"exceptions" => [
				"title" => "Error Handling",
				"icon" => "alert",
				"pages" => [
					"exceptions" => ["title" => "Exceptions", "md" => "exceptions/exceptions.md", "src" => ["Exceptions/MagratheaException.php", "Exceptions/MagratheaApiException.php", "Exceptions/MagratheaDBException.php", "Exceptions/MagratheaConfigException.php", "Exceptions/MagratheaModelException.php"]],
				],
			],
			"advanced" => [
				"title" => "Advanced",
				"icon" => "layers",
				"pages" => [
					"patterns" => ["title" => "Design Patterns", "md" => "advanced/patterns.md", "src" => []],
					"testing" => ["title" => "Testing", "md" => "advanced/testing.md", "src" => ["Tests/MagratheaTests.php"]],
				],
			],
		];
	}

	public static function FindPage(string $category, string $slug): ?array {
		$tree = self::Tree();
		return $tree[$category]["pages"][$slug] ?? null;
	}

	public static function MdPath(string $relative): string {
		return __DIR__ . "/../mds/" . $relative;
	}

	public static function SrcPath(string $relative): string {
		return self::ROOT . "/src/" . $relative;
	}

	/** FQCN from a src-relative path like "Admin/AdminManager.php" */
	public static function FqcnFromSrc(string $relative): string {
		$noExt = preg_replace('/\.php$/', '', $relative);
		return "Magrathea2\\" . str_replace("/", "\\", $noExt);
	}

	/**
	 * Whether a src file actually declares a class/interface/trait (as opposed
	 * to a purely procedural file like _Functions.php). Composer's classloader
	 * uses a bare include() for PSR-4 misses, so calling ReflectionClass on a
	 * procedural file's "class name" re-includes it and blows up with a
	 * "cannot redeclare function" fatal - never reflect those.
	 */
	public static function HasClass(string $relative): bool {
		$path = self::SrcPath($relative);
		if (!file_exists($path)) return false;
		return (bool)preg_match('/^(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+\w+/m', file_get_contents($path));
	}

	public static function GitLastCommitDate(string $absPath): ?string {
		if (!file_exists($absPath)) return null;
		$repoRoot = self::ROOT;
		$rel = ltrim(str_replace($repoRoot, "", realpath($absPath) ?: $absPath), "/");
		$cmd = "git -C " . escapeshellarg($repoRoot) . " log -1 --format=%ai -- " . escapeshellarg($rel) . " 2>/dev/null";
		$out = trim(shell_exec($cmd) ?? "");
		return $out !== "" ? $out : null;
	}

	/**
	 * Returns staleness info for a doc page: compares the doc's last commit date
	 * against the newest last-commit date among its mapped src files.
	 */
	public static function Freshness(array $page): array {
		$docDate = self::GitLastCommitDate(self::MdPath($page["md"]));
		$newestSrc = null;
		$newestSrcFile = null;
		foreach ($page["src"] as $s) {
			$d = self::GitLastCommitDate(self::SrcPath($s));
			if ($d !== null && ($newestSrc === null || $d > $newestSrc)) {
				$newestSrc = $d;
				$newestSrcFile = $s;
			}
		}
		$stale = $docDate !== null && $newestSrc !== null && $newestSrc > $docDate;
		return [
			"docDate" => $docDate,
			"newestSrcDate" => $newestSrc,
			"newestSrcFile" => $newestSrcFile,
			"stale" => $stale,
		];
	}
}
