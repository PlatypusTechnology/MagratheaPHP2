<?php

use Magrathea2\Admin\Admin;
use Magrathea2\Tests\TestsHelper;

// Admin that opts in to the File Editor, as the docs tell apps to do
class FileEditorOptInAdmin extends Admin {
	public function SetFeatures() {
		parent::SetFeatures();
		$this->LoadFileEditor();
	}
}

class adminFeaturesTest extends \PHPUnit\Framework\TestCase {

	private function menuJson(Admin $admin): string {
		return json_encode($admin->BuildMenu()->GetMenu());
	}

	public function testDefaultFeaturesDontIncludeFileEditor() {
		TestsHelper::Print("Testing the File Editor is not a default admin feature");
		$admin = new Admin();
		$admin->SetFeatures();
		$features = $admin->GetFeatures();
		$this->assertArrayNotHasKey("AdminFeatureFileEditor", $features);
		$this->assertArrayHasKey("AdminFeatureAppConfig", $features);
		$this->assertArrayHasKey("AdminFeatureCache", $features);
		$this->assertArrayHasKey("AdminFeatureUser", $features);
		$this->assertArrayHasKey("AdminFeatureUserLog", $features);
	}

	public function testDefaultMenuHasNoFileEditor() {
		TestsHelper::Print("Testing the default admin menu has no File Editor");
		$admin = new Admin();
		$admin->SetFeatures();
		$this->assertStringNotContainsString("File Editor", $this->menuJson($admin));
	}

	public function testFileEditorOptIn() {
		TestsHelper::Print("Testing the File Editor opt-in through LoadFileEditor()");
		$admin = new FileEditorOptInAdmin();
		$admin->SetFeatures();
		$this->assertArrayHasKey("AdminFeatureFileEditor", $admin->GetFeatures());
		$this->assertStringContainsString("File Editor", $this->menuJson($admin));
	}

}
