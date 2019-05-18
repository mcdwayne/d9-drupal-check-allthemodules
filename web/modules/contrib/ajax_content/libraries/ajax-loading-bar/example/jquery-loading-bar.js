/**
 * Created by Administrator on 14-7-27.
 */
(function ($) {
	var loadingBarContainer = $('<div id="loading-bar"><div class="bar"><div class="peg"></div></div></div>'),
		loadingBarNode = loadingBarContainer.find('div').eq(0),
		incTimeout,
		completeTimeout,
		started = false,
		status = 0,
		startSize = 0.02;

	var loadingBar = {
		create: function(){
			$("body").append(loadingBarContainer);
		},
		move:{
			_start: function() {
				clearTimeout(completeTimeout);
				if (started) {
					return;
				}
				started = true;
				loadingBar.move._set(startSize);
			},
			_set: function(n) {
				if (!started) {
					return;
				}
				var pct = (n * 100) + '%';
				loadingBarNode.css('width', pct);
				status = n;
				clearTimeout(incTimeout);
				incTimeout = setTimeout(function() {
					loadingBar.move._inc();
				}, 250);
			},
			_inc: function() {
				if (loadingBar.move._status() >= 1) {
					return;
				}
				var rnd = 0;
				var stat = loadingBar.move._status();
				if (stat >= 0 && stat < 0.25) {
					// Start out between 3 - 6% increments
					rnd = (Math.random() * (5 - 3 + 1) + 3) / 100;
				} else if (stat >= 0.25 && stat < 0.65) {
					// increment between 0 - 3%
					rnd = (Math.random() * 3) / 100;
				} else if (stat >= 0.65 && stat < 0.9) {
					// increment between 0 - 2%
					rnd = (Math.random() * 2) / 100;
				} else if (stat >= 0.9 && stat < 0.99) {
					// finally, increment it .5 %
					rnd = 0.005;
				} else {
					// after 99%, don't increment:
					rnd = 0;
				}

				var pct = loadingBar.move._status() + rnd;
				loadingBar.move._set(pct);
			},
			_status: function() {
				return status;
			},
			_complete: function() {
				loadingBar.move._set(1);
				completeTimeout = setTimeout(function() {
					status = 0;
					started = false;
					loadingBar.remove();
				}, 500);
			}
		},
		remove: function() {
			loadingBarContainer.remove();
		}
	};
	var proxy_get = jQuery.get,
		proxy_post = jQuery.post;
	jQuery.get = function(url, data, callback, type) {
		loadingBar.create();
		loadingBar.move._start();
		proxy_get(url, data, function(data) {
			loadingBar.move._complete();
			callback(data);
		}, type);
	};

	jQuery.post = function(url, data, callback, type) {
		loadingBar.create();
		loadingBar.move._start();
		proxy_post(url, data, function(data) {
			loadingBar.move._complete();
			callback(data);
		}, type);
	};

})(jQuery);