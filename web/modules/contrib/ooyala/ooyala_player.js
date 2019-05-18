
/**
 * @file
 * Ooyala JavaScript API implementation.
 *
 * The Ooyala JavaScript API allows you to register a callback function that
 * Ooyala will use to notify your script(s) of video player events. Since it is
 * only possible to specify a single callback function per player and we want
 * to make sure that any number of modules have access to the API we implement
 * a simple system that allows modules to register a listener which will be
 * notified whenever an event notification is received by the callback
 * function.
 */

/**
 * Allow modules to respond to the creation of a new player.
 *
 * This callback is used on sites using the v4 player. Typically a module
 * providing an onCreate handler will register a "message bus" using the Ooyala
 * API.

 * OnCreate handlers are registered in the Drupal.ooyala.onCreateHandlers object
 * as follows:
 * @code
 *    Drupal.ooyala.onCreateHandlers.myModule = function(player) {
 *      player.mb.subscribe(OO.EVENTS.PLAYING, 'example',
 *        function(eventName) {
 *          alert("Player is now playing!!!");
 *        }
 *      );
 *    };
 * @endcode
 *
 * All listeners will receive the following arguments.
 * - player: The Ooyala player object. Events may be bound to the player.mb
 *   (message bus) property.
 */
(function($) {
	/**
	 * Intialize the Drupal.ooyala object if it hasn't already been initialized.
	 *
	 * Modules wishing to use the Drupal.ooyala API should include this line at the
	 * top of their JavaScript files as it is possible for the module's JavaScript
	 * to be included before this file.
	 */
	Drupal.ooyala = Drupal.ooyala || {
		players: [],
		onCreateHandlers: [],
		onCreate: function(player) {
			Drupal.ooyala.players[player.getElementId()] = player;
			$.each(Drupal.ooyala.onCreateHandlers, function() {
				this(player);
			});
		},
	};

})(jQuery);

