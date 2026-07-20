(function () {
	"use strict";

	// ---------- theme ----------
	var themeToggle = document.getElementById("theme-toggle");
	if (themeToggle) {
		themeToggle.addEventListener("click", function () {
			var root = document.documentElement;
			var current = root.getAttribute("data-theme");
			var isDark = current ? current === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
			root.setAttribute("data-theme", isDark ? "light" : "dark");
		});
	}

	// ---------- copy-to-clipboard on code blocks ----------
	document.querySelectorAll("pre.code-block").forEach(function (pre) {
		var btn = document.createElement("button");
		btn.className = "copy-btn";
		btn.textContent = "Copy";
		btn.addEventListener("click", function () {
			var text = pre.innerText.replace(/^Copy\n?/, "");
			navigator.clipboard.writeText(text).then(function () {
				btn.textContent = "Copied!";
				setTimeout(function () { btn.textContent = "Copy"; }, 1400);
			});
		});
		pre.appendChild(btn);
	});

	// ---------- method accordion ----------
	document.querySelectorAll(".method-head").forEach(function (head) {
		head.addEventListener("click", function (e) {
			if (e.target.closest(".method-example-btn")) return;
			head.closest(".method").classList.toggle("open");
		});
	});

	// ---------- example modals (data pre-rendered server-side into <template>) ----------
	document.querySelectorAll("[data-example]").forEach(function (btn) {
		btn.addEventListener("click", function (e) {
			e.stopPropagation();
			var tpl = document.getElementById("example-tpl-" + btn.getAttribute("data-example"));
			if (!tpl) return;
			var overlay = document.getElementById("example-modal");
			overlay.querySelector("h3").textContent = tpl.dataset.title;
			overlay.querySelector(".modal-code").innerHTML = tpl.innerHTML;
			var noteEl = overlay.querySelector(".modal-note");
			noteEl.textContent = tpl.dataset.note || "";
			noteEl.style.display = tpl.dataset.note ? "block" : "none";
			overlay.querySelector(".source-tag").textContent = tpl.dataset.source === "skills.MD" ? "From skills.MD" : "Original example";
			overlay.classList.add("open");
		});
	});
	document.querySelectorAll(".modal-overlay").forEach(function (overlay) {
		overlay.addEventListener("click", function (e) {
			if (e.target === overlay) overlay.classList.remove("open");
		});
		overlay.querySelectorAll(".modal-close").forEach(function (b) {
			b.addEventListener("click", function () { overlay.classList.remove("open"); });
		});
	});

	// ---------- search ----------
	var searchModal = document.getElementById("search-modal");
	var searchInput = document.getElementById("search-input");
	var resultsBox = document.getElementById("search-results");
	var searchIndex = null;

	function openSearch() {
		searchModal.classList.add("open");
		searchInput.value = "";
		resultsBox.innerHTML = "";
		searchInput.focus();
		if (!searchIndex) {
			fetch(window.aiDocsBase + "/search-index.php").then(function (r) { return r.json(); }).then(function (data) {
				searchIndex = data;
			});
		}
	}
	document.querySelectorAll("[data-open-search]").forEach(function (el) {
		el.addEventListener("click", openSearch);
	});
	document.addEventListener("keydown", function (e) {
		if ((e.key === "k" || e.key === "K") && (e.metaKey || e.ctrlKey)) {
			e.preventDefault();
			openSearch();
		}
		if (e.key === "Escape") {
			document.querySelectorAll(".modal-overlay.open").forEach(function (m) { m.classList.remove("open"); });
		}
	});

	function render(results) {
		if (!results.length) {
			resultsBox.innerHTML = '<div class="search-result">No matches.</div>';
			return;
		}
		resultsBox.innerHTML = results.slice(0, 40).map(function (r) {
			return '<a class="search-result" href="' + r.url + '">' +
				'<span class="sr-title">' + r.title + '</span><span class="sr-type">' + r.type + '</span>' +
				(r.sub ? '<div class="sr-sub">' + r.sub + '</div>' : "") +
				"</a>";
		}).join("");
	}

	searchInput && searchInput.addEventListener("input", function () {
		var q = searchInput.value.trim().toLowerCase();
		if (!searchIndex || q.length < 2) { resultsBox.innerHTML = ""; return; }
		var out = searchIndex.filter(function (item) {
			return item.title.toLowerCase().indexOf(q) !== -1 || (item.sub || "").toLowerCase().indexOf(q) !== -1;
		});
		render(out);
	});
})();
