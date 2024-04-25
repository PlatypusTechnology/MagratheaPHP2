var cacheRsContainer = "#view-container";
var cachefeatureId = "AdminFeatureCache";

function reloadFiles() {
	let container = "#cache-files";
	callFeature(cachefeatureId, "List")
	.then(rs => {
		showOn(container, rs);
	});
}
function callFeatureFor(action, file) {
	let container = cacheRsContainer;
	return new Promise((resolve, reject) => {
		callFeature(cachefeatureId, action, "POST", { file })
			.then(rs => {
				addTo(container, rs);
				resolve(rs);
			});
	});
}

function viewCacheFile(file) {
	callFeatureFor("View", file);
}
function editCacheFile(file) {
	showOn(cacheRsContainer, "");
	callFeatureFor("Edit", file);
}
function deleteCache(file) {
	if(!confirm("Delete file "+file+"?")) return;
	showOn(cacheRsContainer, "");
	callFeatureFor("Remove", file)
		.then(reloadFiles);
}
function saveCache(file) {
	let id = file + "-txt";
	let content = document.getElementById(id).value;
	callFeature(cachefeatureId, "Save", "POST", { file, content })
		.then(rs => {
			addTo(cacheRsContainer, rs);
			reloadFiles();
		});
}

function switchCacheView(el) {
	let parentDiv = $(el).parent().parent();
	let card = $(parentDiv).parent().parent();
	let pre1 = $(card).find(".pretty-cache")[0];
	let pre2 = $(card).find(".raw-cache")[0];
	$(pre1).slideToggle();
	$(pre2).slideToggle();
}

function clearCache() {
	callFeature(cachefeatureId, "ClearCache")
		.then(rs => {
			showOn(cacheRsContainer, rs);
			reloadFiles();
		});

}
