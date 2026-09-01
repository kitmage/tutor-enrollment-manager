(function () {
	'use strict';
	var cfg = window.wcteManualCourse;
	if (!cfg) return;
	var selected = !!cfg.isManual;
	var draftUrl = cfg.url || '';
	var saving = false;

	function nativeRadios() {
		return Array.prototype.slice.call(document.querySelectorAll('input[type="radio"][name="course_price_type"]'));
	}

	function courseId() {
		var input = document.querySelector('input[name="post_ID"], input[name="course_id"], input[name="id"]');
		var query = new URLSearchParams(location.search);
		return Number(cfg.courseId || (input && input.value) || query.get('post') || query.get('course_ID') || query.get('course_id') || 0);
	}

	function makeManualCard(free) {
		var label = free.closest('label');
		if (!label || !label.parentElement || !label.parentElement.parentElement) return null;
		var card = label.parentElement.parentElement.cloneNode(true);
		var cloneLabel = card.querySelector('label');
		var radio = card.querySelector('input[type="radio"]');
		if (!cloneLabel || !radio) return null;
		card.dataset.wcteManual = '1';
		radio.id = 'wcte-manual-price-model';
		radio.name = 'wcte_price_model';
		radio.value = 'manual';
		cloneLabel.htmlFor = radio.id;
		Array.prototype.slice.call(cloneLabel.childNodes).forEach(function (node) {
			if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) node.remove();
		});
		cloneLabel.appendChild(document.createTextNode(cfg.labels.manual));
		return card;
	}

	function makeUrlRow(options) {
		var row = document.createElement('div');
		row.dataset.wcteManualUrl = '1';
		row.style.marginTop = '16px';
		row.innerHTML = '<label class="tutor-form-label" for="wcte-manual-url"></label><input id="wcte-manual-url" class="tutor-form-control" type="url" required placeholder="https://example.com/training/">';
		row.querySelector('label').textContent = cfg.labels.url;
		var field = row.querySelector('input');
		field.value = draftUrl;
		field.addEventListener('input', function () { draftUrl = field.value; });
		options.insertAdjacentElement('afterend', row);
		return row;
	}

	function paint(options) {
		var card = options.querySelector('[data-wcte-manual]');
		var row = options.parentElement.querySelector(':scope > [data-wcte-manual-url]');
		if (!card || !row) return;
		card.querySelector('input').checked = selected;
		if (selected) nativeRadios().forEach(function (radio) { radio.checked = false; });
		row.hidden = !selected;
	}

	function mount() {
		var radios = nativeRadios();
		var free = radios.find(function (radio) { return radio.value === 'free'; });
		if (!free) return;
		var freeCard = free.closest('label').parentElement.parentElement;
		var options = freeCard.parentElement;
		if (!options.querySelector('[data-wcte-manual]')) {
			var card = makeManualCard(free);
			if (!card) return;
			options.appendChild(card);
			if (!options.parentElement.querySelector(':scope > [data-wcte-manual-url]')) makeUrlRow(options);
			card.querySelector('input').addEventListener('change', function () {
				free.click(); // Keep Tutor's internal/native value valid.
				selected = true;
				paint(options);
			});
		}
		radios.forEach(function (radio) {
			if (radio.dataset.wcteBound) return;
			radio.dataset.wcteBound = '1';
			radio.addEventListener('change', function () { selected = false; paint(options); });
		});
		paint(options);
	}

	function save() {
		var id = courseId();
		var field = document.querySelector('[data-wcte-manual-url] input');
		if (!id || !field || saving) return;
		if (selected && !field.checkValidity()) { field.reportValidity(); return; }
		draftUrl = field.value;
		saving = true;
		var data = new URLSearchParams({action: 'wcte_manual_course_settings', nonce: cfg.nonce, course_id: id, manual: selected ? '1' : '0', url: draftUrl});
		fetch(cfg.ajaxUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: data.toString()})
			.then(function (response) { return response.json(); }).then(function (result) { if (!result.success) throw new Error(); })
			.catch(function () { window.alert(cfg.labels.error); }).finally(function () { saving = false; });
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('button, [role="button"]');
		if (!button || !/save|publish|update/i.test(button.textContent)) return;
		[500, 1500, 4000].forEach(function (delay) { window.setTimeout(save, delay); });
	});
	new MutationObserver(mount).observe(document.documentElement, {childList: true, subtree: true});
	mount();
}());
