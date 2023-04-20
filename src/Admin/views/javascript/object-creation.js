function getFromForm(el, input) {
	let typeInput = $(el).parents().eq(2).find(input);
	return $(typeInput);
}

function relationChange(el) {
	let rel = getFromForm(el, "#relation_type").find(":selected").val();
	let obj;
	if(rel == "belongs_to") {
		obj = getFromForm(el, "#this_object").val();
	} else {
		obj = getFromForm(el, "#relation_object").find(":selected").val();
	}
	if(!obj) return;
	callApi("Objects", "GetProperties", { object: obj })
		.then(rs => {
			if(rs.success == false) {
				let data = rs.data;
				let message = data?.message;
				showToast(message || "unknown error");
			} else {
				let fields = rs.data.fields;
				let data = fields.map((i) => {
						return { value: i.name.toLowerCase(), description: i.name };
					});
				appendToSelect(data, getFromForm(el, "#relation_property"));
				getFromForm(el, "#relation_property_description").html("Field from "+ucfirst(obj)+" to link objects:");
//				$(".relation-property-container label").html("Field from "+ucfirst(obj)+" to link objects:");
			}
		});
}

function appendToSelect(options, selectInput) {
	selectInput.find('option').remove();
	options.forEach(function(option) {
		var value = option.value;
		var text = option.description;
		var $option = $('<option>', { value, text });
		selectInput.append($option);
	});
}

function toggleAddRelation(object, show=true) {
	if(show) {
		$("#relation-"+object+"-form-button").slideUp("slow");
		$("#relation-"+object+"-form").slideDown("slow");
	} else {
		$("#relation-"+object+"-form").slideUp("slow");
		$("#relation-"+object+"-form-button").slideDown("slow");
	}
}

function addRelation(el) {
	let data = getFormDataFromElement(el);
	let object = data["this_object"];
	if(!data["relation_property"]) {
		showToast("Couldn't add relation: incorrect form");
		return;
	}
	callApi("Objects", "AddRelation", data)
		.then(rs => {
			toggleAddRelation(object, false);
			$("#relation-"+object+"-rs").html("Relation added!");
			$("#relation-"+object+"-rs").slideDown("slow");
			loadRelations(object);
			window.setTimeout(() => $("#relation-"+object+"-rs").slideUp("slow"), 5000);
		});
}

function removeRelation(el) {
	let data = getFormDataFromElement(el);
	let name = data["relation-name"];
	let object = data["object-name"];
	callApi("Objects", "DeleteRelation", { name })
		.then(rs => {
			loadRelations(object);
		});
}

function saveRelation(el) {
	let data = getFormDataFromElement(el);
	let object = data["object-name"];
	let saveData = {
		name: data["relation-name"],
		rel_property: data["relation-property"],
		rel_method: data["relation-method"].replace(/[()]/g, ''),
		rel_lazyload: (data["relation-lazy"] == 1),
		rel_autoload: (data["relation-auto"] == 1),
	}
	callApi("Objects", "UpdateRelation", saveData)
		.then(rs => {
			loadRelations(object);
		});
}

function loadRelations(object) {
	let container = "#edit-relations-for-"+object;
	callAction("object-relation-edit&object="+object)
		.then(rs => {
			showOn(container, rs);
			viewObject(object);
		});
}
