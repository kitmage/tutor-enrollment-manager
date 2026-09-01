(function () {
	'use strict';
	var cfg = window.wcteManualCourse;
	if (!cfg) return;
	var selected = !!cfg.isManual;
	var draftUrl = cfg.url || '';

	function priceRadio() {
		return document.querySelector('input[type="radio"][value="free"][name*="price"], input[type="radio"][value="free"]');
	}
	function courseId() {
		var input = document.querySelector('input[name="post_ID"], input[name="course_id"], input[name="id"]');
		return Number(cfg.courseId || (input && input.value) || new URLSearchParams(location.search).get('post') || 0);
	}
	function mount() {
		var free = priceRadio();
		if (!free) return;
		var group = free.closest('[role="radiogroup"], fieldset, .tutor-form-group, .tutor-course-builder-section') || free.parentNode.parentNode;
		if (!group || group.querySelector('[data-wcte-manual]')) return;
		var box = document.createElement('div');
		box.dataset.wcteManual = '1';
		box.className = 'wcte-manual-pricing tutor-mt-16';
		box.innerHTML = '<label class="tutor-form-check"><input class="tutor-form-check-input" type="radio" name="wcte-price-model" value="manual"><span>' + cfg.labels.manual + '</span></label>' +
			'<div class="wcte-manual-url tutor-mt-12"><label class="tutor-form-label">' + cfg.labels.url + '</label><input class="tutor-form-control" type="url" required placeholder="https://example.com/training/"></div>';
		group.appendChild(box);
		var radio = box.querySelector('input[type="radio"]');
		var field = box.querySelector('input[type="url"]');
		field.value = draftUrl;
		field.addEventListener('input', function () { draftUrl = field.value; });
		function paint() { box.querySelector('.wcte-manual-url').hidden = !selected; radio.checked = selected; }
		radio.addEventListener('change', function () { free.click(); selected = true; paint(); });
		group.addEventListener('change', function (event) { if (event.target !== radio && event.target.type === 'radio') { selected = false; paint(); } });
		paint();
	}

	function save() {
		var id = courseId();
		var field = document.querySelector('[data-wcte-manual] input[type="url"]');
		if (!id || !field) return;
		if (selected && !field.checkValidity()) { field.reportValidity(); return; }
		draftUrl = field.value;
		var data = new URLSearchParams({action: 'wcte_manual_course_settings', nonce: cfg.nonce, course_id: id, manual: selected ? '1' : '0', url: draftUrl});
		fetch(cfg.ajaxUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: data.toString()})
			.then(function (response) { return response.json(); }).then(function (result) { if (!result.success) throw new Error(); })
			.catch(function () { window.alert(cfg.labels.error); });
	}

	document.addEventListener('click', function (event) {
		if (event.target.closest('button[type="submit"], .tutor-course-builder-save, [data-cy="save-course"]')) window.setTimeout(save, 750);
	});
	new MutationObserver(mount).observe(document.documentElement, {childList: true, subtree: true});
	mount();
}());
