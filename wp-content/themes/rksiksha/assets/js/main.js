(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var burger = document.querySelector('.rks-burger');
		var nav = document.querySelector('.rks-nav');
		if (!burger || !nav) return;

		burger.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			burger.classList.toggle('is-open', open);
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	});
})();
