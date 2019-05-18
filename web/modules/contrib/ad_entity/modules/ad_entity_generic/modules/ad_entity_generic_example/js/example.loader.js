/**
 * @file
 * Advertising Entity: Loader example for using the generic handler.
 *
 * Short description about the example: This implementation is assuming
 * to be loaded asynchronously. If it's a little late, it may check for
 * globally available queues (toLoad and toRemove), whether some tags
 * are already waiting (see [10]). It takes care of loading further ads on its
 * own, by adding itself as a loading and on-removal handler (see [8]).
 */

(function (adEntity) {

  // [1] This callback represents your own load handler.
  var loadCallback = function (ad_tags) {
    // Should not use the logger on production.
    console.log('Load handler called. Items to load: ' + ad_tags.length);
    // [2] Every ad_tag, which belongs to your handler, should be moved
    //     from the incoming ad_tags queue, e.g. via the shift() method.
    // [3] It should be noted, that there can be other load handlers,
    //     having their own callbacks, which empty the ad_tags array.
    //     If you want to push the whole ad_tags content to elsewhere,
    //     like an event listener or to another command queue,
    //     you should push a flat copy of the ad_tags array.
    var ad_tag = ad_tags.shift();
    // [4] Every ad_tag consists of the following properties:
    // - id: The DOM element ID as string.
    // - el: The DOM element as object itself.
    // - name: The internal machine name of the ad tag as string.
    // - format: The display format to use as string.
    // - targeting: Consists of common and custom / ad-specific targeting.
    //              Includes properties for numbered slots (slotNumber),
    //              whether the tag was added during page load (onPageLoad),
    //              and whether personalization is enabled (personalized).
    //   Tip: To get the current state of personalization, you can call
    //        window.adEntity.usePersonalization(), which returns true
    //        if personalized ads are allowed, and false otherwise.
    // - done(success, isEmpty): Once you finished loading the advertisement
    //   on this tag, this function should be called. The success parameter
    //   is a boolean indicating whether the ad loading was successful (true)
    //   or not (false). The isEmpty parameter is a boolean indicating whether
    //   the loading resulted in an empty slot (true) or not (false).
    // - isLoaded: Is a reserved flag indicating whether the tag was loaded or not.
    //             The flag will be automatically set by calling the done() function.
    // - isEmpty: Is a reserved flag indicating whether the loaded tag is empty or not.
    //            The flag will be automatically set by calling the done() function.
    // - data: Is a helper function to get and set data-* values.
    //         This is similar to jQuery's data callback.
    while (typeof ad_tag === 'object') {
      console.log('Loaded ad tag ' + ad_tag.name);
      // [5] Important - call this function once loading is done:
      ad_tag.done(true, false);

      ad_tag = ad_tags.shift();
    }
  };
  // [6] In case you need to do something when ads are being removed
  //     from the DOM, you can do this withing this function.
  var removeCallback = function (ad_tags) {
    console.log('On-removal handler called. Items to remove: ' + ad_tags.length);
  };

  // [7] Properly initialize the generic object.
  adEntity.generic = adEntity.generic || {toLoad: [], toRemove: [], loadHandlers: [], removeHandlers: []};

  // [8] Put your callbacks on top of the handler lists,
  //     to be an early one working on the incoming queues.
  // [9] A "queue" handler will be executed afterwards and
  //     will put all the rest of not yet pulled ad_tag
  //     items to the globally available queue toLoad and toRemove.
  adEntity.generic.loadHandlers.unshift({name: 'example_loader', callback: loadCallback});
  adEntity.generic.removeHandlers.unshift({name: 'example_on_removal', callback: removeCallback});

  // [10] Directly check on the globally available queues,
  //      whether some tags are already waiting.
  loadCallback(adEntity.generic.toLoad);
  removeCallback(adEntity.generic.toRemove);

}(window.adEntity));
