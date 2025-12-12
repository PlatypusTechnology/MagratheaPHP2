<?php

namespace Magrathea2;

trait Magrathea_Enum {
	public static function presentValue(string $str): string {
		$str = strtolower($str);
		return ucfirst($str);
	}

	public static function values(): array {
		return array_column(self::cases(), 'value');
	}
	public static function keys(): array {
		return array_column(self::cases(), 'name');
	}
	public static function array(): array {
		$arr = count(self::values()) == 0 ? 
			self::keys() :
			array_combine(self::keys(), self::values());
		return array_map(function($t){ return self::presentValue($t); }, $arr);
	}

	public static function fromKey(string $key): self|null {
		if(!defined("self::{$key}")) return null;
		return constant("self::{$key}");
	}

	public function toString(): string {
		return $this->presentValue($this->name);
	}

	
}
