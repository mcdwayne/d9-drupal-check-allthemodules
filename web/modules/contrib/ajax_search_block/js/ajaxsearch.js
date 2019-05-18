
/**
 * @file
 */
(function () {

		"use strict";

		var ajaxSearchInputAll = document.getElementsByClassName("ajax-search-input");
		var ajaxSearchInputs = [].slice.call(ajaxSearchInputAll);

		ajaxSearchInputs.forEach(function (ajaxSearchInput, idx) {
			ajaxSearchInput.onkeyup = function (e) {searchFunction(e, ajaxSearchInput)};
		});

		//document.getElementById("avaiable-pages").style.display = 'none';

		function searchFunction (e, ajaxSearchInput) {
			var e = e || window.event; //IE does not pass the event object
			var ajaxSearchBox = ajaxSearchInput.parentNode;
			var scrolableDivElement = ajaxSearchBox.querySelector('.avaiable-pages');
			var scrolableDivElementBoundry = scrolableDivElement.getBoundingClientRect();

			switch (e.keyCode) {
				case 38: // if the UP key is pressed
					var lastElement = ajaxSearchBox.querySelector('#list-last-page');
					if (typeof(ajaxSearchBox.querySelector('.focused-page')) == "undefined" || ajaxSearchBox.querySelector('.focused-page') == null) {
						lastElement.className += ' focused-page';
					}
					else if (ajaxSearchBox.querySelector('.focused-page').id == 'list-first-page') {
					//you are already at the top
					}
					else {
						var prevElement = ajaxSearchBox.querySelector('.focused-page').previousSibling;
						var prevElementBoundry = prevElement.getBoundingClientRect();
						ajaxSearchBox.querySelector('.focused-page').classList.remove("focused-page");
						prevElement.className += ' focused-page';
						if (prevElementBoundry.top < scrolableDivElementBoundry.top) {
							var prevElementStyle = prevElement.currentStyle || window.getComputedStyle(prevElement);
							prevElement.parentNode.parentNode.scrollTop -= prevElement.offsetHeight + parseInt(prevElementStyle.marginTop, 10);
						}
					}
				break;

				case 40: // if the DOWN key is pressed
					var firstElement = ajaxSearchBox.querySelector('#list-first-page');
					if (typeof(ajaxSearchBox.querySelector('.focused-page')) == "undefined" || ajaxSearchBox.querySelector('.focused-page') == null) {
						firstElement.className += ' focused-page';
					} else if (ajaxSearchBox.querySelector('.focused-page').id == 'list-last-page') {
						//you are already at the bottom
					}
					else {
						var nextElement = ajaxSearchBox.querySelector('.focused-page').nextSibling;
						var nextElementBoundry = nextElement.getBoundingClientRect();
						ajaxSearchBox.querySelector('.focused-page').classList.remove("focused-page");
						nextElement.className += ' focused-page';
						if (nextElementBoundry.bottom > scrolableDivElementBoundry.bottom) {
							var nextElementStyle = nextElement.currentStyle || window.getComputedStyle(nextElement);
							nextElement.parentNode.parentNode.scrollTop += nextElement.offsetHeight + parseInt(nextElementStyle.marginTop, 10);
						}
					}
				break;

				case 13: // if the Enter key is pressed
					var url = ajaxSearchBox.querySelector('.focused-page').getAttribute('data-href');
					redirectFunction(url);
				break;

				default:
					var text = ajaxSearchInput.value;
					var blockSettingsKey = ajaxSearchBox.parentNode.getAttribute('data-block-settings-key');
					if (text.length > 0 ) {
						ajaxSearchBox.querySelector(".ajax-search-loader").style.display = 'block';
						var xhttp = new XMLHttpRequest();
							xhttp.onreadystatechange = function () {
								if (this.readyState == 4 && this.status == 200) {
									var myres = JSON.parse(this.response);
									if (typeof myres.nodes !== 'undefined' && myres.nodes.length > 0) {
										var elements = '<div><ul id="avaiable-pages-ul">';
										for (var key in myres.nodes) {
											var classes = 'search-item search-item-' + key;
											var elementId = 'default-id';
											if (key == 0) {
												elementId = 'list-first-page';
											}
											if (key == (myres.nodes.length-1)) {
												elementId = 'list-last-page';
											}
											elements = elements + '<li id="' + elementId + '" class="' + classes + '" data-href="' + myres.nodes[key].path +'">' + myres.nodes[key].result + '</a></li>';
										}
										elements = elements + '</ul></div>';
										ajaxSearchBox.querySelector(".avaiable-pages").innerHTML = elements;
										var list = ajaxSearchBox.querySelectorAll(".search-item");
										for (var i = 0; i < list.length; i++) {
											list[i].addEventListener('click', redirectFunction, false);
										}
									}
									else {
										ajaxSearchBox.querySelector(".avaiable-pages").innerHTML = '';
									}
									ajaxSearchBox.querySelector(".ajax-search-loader").style.display = 'none';
								}
							};
							xhttp.open("GET", drupalSettings.ajax_search_block.ajax_base_url +"/api/getpages?t=" + text + "&key=" + blockSettingsKey, true);
							xhttp.send();
							ajaxSearchBox.querySelector(".avaiable-pages").style.display = 'block';
					}
					else {
						ajaxSearchBox.querySelector(".avaiable-pages").style.display = 'none';
					}
			}
		}

		var redirectFunction = function (dataHref) {
			if (!dataHref) {
				dataHref = '';
			}
			if (typeof dataHref == "string") {
				var attribute = dataHref;
			}
			else {
				var attribute = this.getAttribute("data-href");
			}
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function () {
				if (this.readyState == 4 && this.status == 200) {
					var res = JSON.parse(this.response);
					window.location = res.url;
				}
			};
			xhttp.open("GET", drupalSettings.ajax_search_block.ajax_base_url + "/api/getalias?path=" + attribute, true);
			xhttp.send();
		};

		document.onclick = function (e) {
			ajaxSearchInputs.forEach(function (ajaxSearchInput, idx) {
				if (e.target !== ajaxSearchInput.parentNode.querySelector('.avaiable-pages').children[0] &&
				e.target !== ajaxSearchInput.parentNode.querySelector('.avaiable-pages') &&
				e.target !== ajaxSearchInput.parentNode.querySelector('.ajax-search-input') &&
				e.target !== ajaxSearchInput.parentNode.querySelector('#avaiable-pages-ul')) {
					ajaxSearchInput.parentNode.querySelector('.avaiable-pages').innerHTML = '';
					ajaxSearchInput.parentNode.querySelector(".ajax-search-loader").style.display = 'none';
					// setWidth('ajax-search-input', 'minus');
				} else if (e.target == ajaxSearchInput.parentNode.querySelector('.ajax-search-input')) {
					// setWidth('ajax-search-input', 'add');
				}
			});
		};
	})();
