<h5>Execute Query</h5>
<div class="row">
	<div class="col-12">
		Query:
		<textarea class="query-sql" id="query">SHOW VARIABLES;</textarea>
		<button class="btn btn-primary" onclick="runQuery();"> 
			Execute
		</button>
	</div>
	<div class="col-12" id="query-response"></div>
</div>