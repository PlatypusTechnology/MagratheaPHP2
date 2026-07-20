<?php

namespace Magrathea2\Admin\Features\OpenApi;

use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\iAdminFeature;

class OpenApiAdmin extends AdminFeature implements iAdminFeature {
	public string $featureName = "Open API";
	public string $featureId = "AdminOpenApi";
	protected string $fileUrl;

	public function __construct(string $fileUrl = "swagger.yaml") {
		parent::__construct();
		$this->fileUrl = $fileUrl;
		$this->AddJs(__DIR__."/scripts.js");
		$this->AddCSS(__DIR__."/styles.css");
	}

	public function Index() {
		$swaggerFile = $this->fileUrl;
		include(__DIR__."/index.php");
	}
}
