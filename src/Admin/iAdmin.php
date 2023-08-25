<?php

namespace Magrathea2\Admin;

interface iAdmin {
	public function Initialize();
	public function Auth($user): bool;
	public function SetFeatures();
	public function BuildMenu(): AdminMenu;
}
