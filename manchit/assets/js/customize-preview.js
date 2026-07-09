/**
 * Customizer live preview.
 *
 * @package Manchit
 */
(function ($) {
	'use strict';

	function darken(hex, pct) {
		hex = hex.replace('#', '');
		if (hex.length === 3) { hex = hex.replace(/(.)/g, '$1$1'); }
		var r = parseInt(hex.substr(0, 2), 16),
			g = parseInt(hex.substr(2, 2), 16),
			b = parseInt(hex.substr(4, 2), 16);
		var f = function (c) { return Math.max(0, Math.min(255, Math.round(c + (c * pct / 100)))); };
		return '#' + [f(r), f(g), f(b)].map(function (c) { return ('0' + c.toString(16)).slice(-2); }).join('');
	}

	wp.customize('manchit_options[brand_color]', function (value) {
		value.bind(function (to) {
			var root = document.documentElement;
			root.style.setProperty('--mn-brand', to);
			root.style.setProperty('--mn-brand-600', darken(to, -18));
			root.style.setProperty('--mn-brand-700', darken(to, -34));
		});
	});

	wp.customize('manchit_options[accent_color]', function (value) {
		value.bind(function (to) {
			document.documentElement.style.setProperty('--mn-accent', to);
		});
	});

	wp.customize('blogname', function (value) {
		value.bind(function (to) {
			$('.mn-site-title').text(to);
		});
	});
})(jQuery);
