
<div class="card">
	<div class="card-header">
		Code Sample
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body config-form">
		<div class="row">
			<div class="col-12">
				<pre>
var = \Magrathea2\ConfigApp::Instance()...
	...Save($key, $value, $overwrite=true): AppConfig;
	...Get(string $key, $default=null): ?string;
	...GetBool(string $key, bool $default=false): bool;
	...GetInt(string $key, int $default=0): int;
	...GetFloat(string $key, float $default=0): float;</pre>
			</div>
		</div>
	</div>
</div>
