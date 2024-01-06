<?php

namespace Magrathea2;

trait Magrathea_Enum {
	public static function values(): array {
		return array_column(self::cases(), 'value');
	}
	public static function keys(): array {
		return array_column(self::cases(), 'name');
	}
	public static function array(): array {
		return array_combine(self::keys(), self::values());
	}

	public static function fromKey(string $key): self|null {
		if(!defined("self::{$key}")) return null;
		return constant("self::{$key}");
	}
}
