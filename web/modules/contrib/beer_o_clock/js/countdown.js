/**
 * @file
 * The JavaScript that controls the beer o'clock countdown timer.
 */

jQuery(document).ready(function ($) {
	var countdown_Id = 0; //Initialising the countdown variable
  var now = Math.round(new Date().getTime() / 1000.0);
  var secondsToNextBeerOClock = drupalSettings.beer_o_clock.timer;

	$days = $('.countdown-days > .digit');
	$hours = $('.countdown-hours > .digit');
	$minutes = $('.countdown-minutes > .digit');
	$seconds = $('.countdown-seconds > .digit');

  var diff = secondsToNextBeerOClock - now;

	// http://stackoverflow.com/questions/1267283/how-can-i-create-a-zerofilled-value-using-javascript
	function zeroFill(number, width) {
		width -= number.toString().length;
		if (width > 0) {
			return new Array( width + (/\./.test( number ) ? 2 : 1) ).join( '0' ) + number;
		}
		return number + ""; // always return a string
	}

	function isItBeerOClock() {
		var beer_day = parseInt(drupalSettings.beer_o_clock.day);
		var beer_hour = parseInt(drupalSettings.beer_o_clock.hour);
		var beer_duration = parseInt(drupalSettings.beer_o_clock.duration);
		var now = new Date();
		var today = parseInt(now.getDay());
		var hour = parseInt(now.getHours());
		return (beer_day === today && hour >= beer_hour && hour < beer_hour + beer_duration);
	}

	countdown_Id = setInterval(function () {
		var seconds = diff % 60;
		var seconds_min = Math.floor(diff / 60);
		var minutes = seconds_min % 60;
		var hour_min = Math.floor(seconds_min / 60);
		var hours = hour_min % 24;
		var hour_day = Math.floor(hour_min / 24);
		var days = hour_day % 365;

		// When it is beer o'clock.
		if (!isItBeerOClock()) {
			$(".boc_active").hide();
			$(".boc_inactive").show();
			$(".countdown-timer").show();
			// Update the hours display
			$.each($days, function(index, value) {
				$days[index].innerHTML = zeroFill(days, 2).split('')[index];
			});

			$.each($hours, function(index, value) {
				$hours[index].innerHTML = zeroFill(hours, 2)[index];
			});

			// Update the minutes display
			$.each($minutes, function(index, value) {
				$minutes[index].innerHTML = zeroFill(minutes, 2)[index];
			});

			// Update the seconds display
			$.each($seconds, function(index, value) {
				$seconds[index].innerHTML = zeroFill(seconds, 2)[index];
			});

			diff--;
		}

		// When it isn't beer o'clock
		else {
			$(".boc_inactive").hide();
			$(".boc_active").show();
			$(".countdown-timer").hide();
			clearInterval(countdown_Id);
		}
	}, 1000);

});
