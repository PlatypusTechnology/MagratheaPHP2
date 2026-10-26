<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Config;

$elements = AdminElements::Instance();
$elements->Header("Swagger");

$swaggerBaseUrl = Config::Instance()->Get("app_url");

?>

<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />

<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
	var swaggerBaseUrl = <?= json_encode($swaggerBaseUrl) ?>;

	var swaggerOptions = {
		url: <?= json_encode($swaggerFile) ?>,
		dom_id: "#swagger-ui",
		presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
		layout: "BaseLayout",
	};

	if (swaggerBaseUrl) {
		var swaggerBase = new URL(swaggerBaseUrl, window.location.href);
		swaggerOptions.requestInterceptor = function (req) {
			// the spec file is served next to the admin page: never rewrite it
			if (req.loadSpec) return req;
			var target = new URL(req.url, window.location.href);
			target.protocol = swaggerBase.protocol;
			target.host = swaggerBase.host;
			req.url = target.toString();
			return req;
		};
	}

	SwaggerUIBundle(swaggerOptions);
</script>
