(function (exports) {
'use strict';

/**
 * @file
 * Global scripts, loaded on every page.
 */

(function ($) {

	var hamburger = $('#hamburger');

	hamburger.on('click', function () {
		hamburger.toggleClass('is-active');
	});

	// Updates parallax header height with px value instead of vh, so it won't be jumping on Android
	// @todo - remove this from here
	var $header = $('.paragraph--type--header.paragraph--view-mode--default');
	if ($header.length) {
		$header.height($header.height());
	}

})(jQuery);

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjpudWxsLCJzb3VyY2VzIjpbIkQ6L2RldmRlc2t0b3AvdGNzLmxvYy93ZWIvdGhlbWVzL3RpZXRvX2FkbWluL3NyYy9zY3JpcHRzL2dsb2JhbC5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKipcbiAqIEBmaWxlXG4gKiBHbG9iYWwgc2NyaXB0cywgbG9hZGVkIG9uIGV2ZXJ5IHBhZ2UuXG4gKi9cblxuKCQgPT4ge1xuXG5cdGNvbnN0IGhhbWJ1cmdlciA9ICQoJyNoYW1idXJnZXInKVxuXG5cdGhhbWJ1cmdlci5vbignY2xpY2snLCAoKSA9PiB7XG5cdFx0aGFtYnVyZ2VyLnRvZ2dsZUNsYXNzKCdpcy1hY3RpdmUnKVxuXHR9KVxuXG5cdC8vIFVwZGF0ZXMgcGFyYWxsYXggaGVhZGVyIGhlaWdodCB3aXRoIHB4IHZhbHVlIGluc3RlYWQgb2YgdmgsIHNvIGl0IHdvbid0IGJlIGp1bXBpbmcgb24gQW5kcm9pZFxuXHQvLyBAdG9kbyAtIHJlbW92ZSB0aGlzIGZyb20gaGVyZVxuXHRjb25zdCAkaGVhZGVyID0gJCgnLnBhcmFncmFwaC0tdHlwZS0taGVhZGVyLnBhcmFncmFwaC0tdmlldy1tb2RlLS1kZWZhdWx0Jyk7XG5cdGlmICgkaGVhZGVyLmxlbmd0aCkge1xuXHRcdCRoZWFkZXIuaGVpZ2h0KCRoZWFkZXIuaGVpZ2h0KCkpO1xuXHR9XG5cbn0pKGpRdWVyeSlcbiJdLCJuYW1lcyI6WyJjb25zdCJdLCJtYXBwaW5ncyI6Ijs7O0FBQUE7Ozs7O0FBS0EsQ0FBQyxVQUFBLENBQUMsRUFBQzs7Q0FFRkEsSUFBTSxTQUFTLEdBQUcsQ0FBQyxDQUFDLFlBQVksQ0FBQyxDQUFBOztDQUVqQyxTQUFTLENBQUMsRUFBRSxDQUFDLE9BQU8sRUFBRSxZQUFHO0VBQ3hCLFNBQVMsQ0FBQyxXQUFXLENBQUMsV0FBVyxDQUFDLENBQUE7RUFDbEMsQ0FBQyxDQUFBOzs7O0NBSUZBLElBQU0sT0FBTyxHQUFHLENBQUMsQ0FBQyx3REFBd0QsQ0FBQyxDQUFDO0NBQzVFLElBQUksT0FBTyxDQUFDLE1BQU0sRUFBRTtFQUNuQixPQUFPLENBQUMsTUFBTSxDQUFDLE9BQU8sQ0FBQyxNQUFNLEVBQUUsQ0FBQyxDQUFDO0VBQ2pDOztDQUVELENBQUMsQ0FBQyxNQUFNLENBQUMsQ0FBQTs7In0=