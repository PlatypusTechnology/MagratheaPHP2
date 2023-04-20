<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use function Magrathea2\p_r;

$pageTitle = "Admin Forms and Elements";
include(__DIR__."/../sections/header.php");

$formData = [
	[
		"type" => "hidden",
		"key" => "hidden-id",
		"name" => "this one is hidden",
	],
	[
		"type" => "text",
		"key" => "form-input",
		"name" => "Text Field:",
		"size" => "col-12",
	],
	[
		"type" => "email",
		"key" => "form-email",
		"name" => "E-mail:",
		"placeholder" => "E-mail field",
		"size" => "col-4",
	],
	[
		"type" => "number",
		"key" => "form-number",
		"name" => "Text Field:",
		"placeholder" => "Number field",
		"size" => "col-4",
	],
	[
		"type" => "disabled",
		"key" => "form-disabled",
		"name" => "Disabled info:",
		"size" => "col-4",
	],
	[
		"type" => "empty",
		"key" => "div-id",
		"size" => "col-4 mt-4 border",
	],
	[
		"type" => "checkbox",
		"key" => "form-checkbox",
		"name" => "Label for checkbox",
		"placeholder" => "checkbox-value",
		"size" => "col-4 mt-2",
	],
	[
		"type" => "switch",
		"key" => "form-switch",
		"name" => "What a fancy input!",
		"size" => "col-4 mt-2",
		"attributes" => [
			"onchange" => "alert('wow!');",
		]
	],
	[
		"type" => [
			"value" => "caption",
			"value-2" => "another caption",
		],
		"key" => "form-checkbox-1",
		"placeholder" => "placeholder for select",
		"name" => "Checkbox 1",
		"size" => "col-6",
	],
	[
		"type" => [
			"frodo" => "Frodo Baggins",
			"sam" => "Samwise Gamgee",
			"pippin" => "Pippin Took",
			"merry" => "Merry Brandybuck",
		],
		"key" => "form-hobbits",
		"placeholder" => "this is already selected, so no placeholder will be shown",
		"name" => "The Shire",
		"size" => "col-6",
	],
	[
		"type" => function($data) {
			echo "<hr/> this will get the values<br/>";
			print_r($data['arr']);
		},
		"size" => "col-8",
	],
	[
		"type" => "button",
		"size" => "col-4",
		"class" => "w-100 btn-primary",
		"name" => "Click me",
		"key" => "showToast('Look up here!', 'This is a toast');",
	],

];

$formValues = [
	"hidden-id" => 42,
	"form-input" => "Some data",
	"form-disabled" => "well...",
	"div-id" => "this content will be inside the div",
	"form-checkbox" => true,
	"form-hobbits" => "sam",
	"more-items" => "anything, I don't care",
	"arr" => [ "Saruman", "Gandalf" ],
];

$admin = new AdminForm();
$admin->SetName("demo-form")->Build($formData, $formValues);

$adminElements = AdminElements::Instance();

?>

<style>
.code-form-data {
	height: 800px;
	overflow-y: scroll;
}
</style>

<div class="container">

	<!-- Simple Table -->
	<div class="card">
		<div class="card-header">
			Admin Tables - Simple Tables
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12 mt-2">
					<?
					$adminElements->Button("&darr;", "toggleCol(this, '.code-simple-table')", ["btn-action", "btn-primary"]);
					?>
					<b>View Code</b>
				</div>
				<div class="col-12 code-simple-table" style="display: none;">
					<?
						$tableRows = [
							[
								"name" => "Tony Stark",
								"hero" => "Iron Man",
								"family" => "Avengers",
								"company" => "Marvel",
							],
							[
								"name" => "Steve Rogers",
								"hero" => "Captain America",
								"family" => "Avengers",
								"company" => "Marvel",
							],
							[
								"name" => "Bruce Banner",
								"hero" => "Batman",
								"family" => "Justice League",
								"company" => "DC",
							],
							[
								"name" => "Peter Quill",
								"hero" => "Star Lord",
								"family" => "Guardians of the Galaxy",
								"company" => "Marvel",
							],
							[
								"name" => "Diana Ross",
								"hero" => "Wonder Woman",
								"family" => "Justice League",
								"company" => "DC",
							],

						];
						$tableCols = [
							"hero" => "Hero",
							"name" => "Person",
							"family" => "Team",
							"company" => "Publisher",
						]
					?>
					Table Rows:
					<pre class="code code-light">$formData = <? p_r($tableRows); ?></pre>
					Table Cols:
					<pre class="code code-light">$formData = <? p_r($tableCols); ?></pre>
					Code:
					<pre class="code">
AdminElements::Instance->Table($tableRows, $tableCols, [ "some-extra-class", "border", "mt-4" ]);
					</pre>
				</div>
				<div class="col-12">
					<?
						$adminElements->Table(
							$tableRows,
							$tableCols,
							[ "border", "extra-class", "mt-4" ]
						);
					?>
				</div>
			</div>
		</div>
	</div>

	<!-- Complex Table -->
	<div class="card">
		<div class="card-header">
			Admin Tables - Complex Tables
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12 mt-2">
					<?
					$adminElements->Button("&darr;", "toggleCol(this, '.code-complex-table')", ["btn-action", "btn-primary"]);
					?>
					<b>View Code</b>
				</div>
				<div class="col-12 code-complex-table" style="display: none;">
					<?
						$countries = array(
							array(
								'name' => 'Russia',
								'land_area' => 17098242,
								'currency' => 'Russian Ruble',
								'language' => 'Russian',
							),
							array(
								'name' => 'Canada',
								'land_area' => 9984670,
								'currency' => 'Canadian Dollar',
								'language' => 'English, French',
							),
							array(
								'name' => 'China',
								'land_area' => 9596961,
								'currency' => 'Renminbi (Yuan)',
								'language' => 'Mandarin',
							),
							array(
								'name' => 'United States',
								'land_area' => 9147593,
								'currency' => 'US Dollar',
								'language' => 'English, Spanish',
							),
							array(
								'name' => 'Brazil',
								'land_area' => 8515767,
								'currency' => 'Brazilian Real',
								'language' => 'Portuguese',
							),
						);
						$countriesCols = [
							[
								"title" => "Country",
								"key" => "name",
							],
							[
								"title" => "Currency",
								"key" => "currency",
							],
							[
								"title" => "Speaks",
								"key" => "language",
							],
							[
								"title" => "Area",
								"key" => function($i) {
									return number_format($i['land_area'], 0, '.', ' ');
								},
							],
							[
								"title" => "% World Area",
								"key" => function($i) {
									$worldArea = 148940000;
									$percentage = $i['land_area'] / $worldArea * 100;
									return number_format($percentage, 2, '.', '')."%";
								},
							],
						]
					?>
					Table Rows:
					<pre class="code code-light">$formData = <? p_r($countries); ?></pre>
					Table Cols:
					<pre class="code code-light">$formData = <? p_r($countriesCols); ?></pre>
					Code:
					<pre class="code">
AdminElements::Instance->Table($countries, $countriesCols, [ "mt-2" ]);
					</pre>
				</div>
				<div class="col-12">
					<?
						$adminElements->Table($countries, $countriesCols, [ "mt-2" ]);
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			Admin Form
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-6 code-form-data">
					Form Data:
					<pre class="code code-light">$formData = <? p_r($formData); ?></pre>
					Form Values:
					<pre class="code code-light">$formValues = <? p_r($formValues); ?></pre>
					Code:
					<pre class="code">
$admin = new AdminForm();
$admin->SetName("demo-form");
$admin->Build($formData, $formValues);
$admin->Print();</pre>
				</div>
				<div class="col-6">
					<?
						$admin->Print();
					?>
				</div>
			</div>
		</div>
	</div>

</div>
