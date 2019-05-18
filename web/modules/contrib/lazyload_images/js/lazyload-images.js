/**
 * Lazy load images with the (new) IntersectionObserver.
 */
(function () {

  // Get all of the images that are marked up to lazy load
  var images = document.querySelectorAll('.js-lazyload-image');

  // Define an easy to use forEach function because queryselector.forEach does not work on every browser.
  var forEach = function (array, callback, scope) {
    for (var i = 0; i < array.length; i++) {
      callback.call(scope, i, array[i]); // passes back stuff we need
    }
  };

  /*
   * Lazy load images using an IntersectionObserver
   * See: https://developers.google.com/web/updates/2016/04/intersectionobserver#intersect_all_the_things
   * See: https://corydowdy.com/blog/lazy-loading-images-with-intersection-observer
   * See: https://deanhume.com/lazy-loading-images-using-intersection-observer/
   */
  if (('IntersectionObserver' in window)) {

    const config = {
      // If the image gets within 50px in the Y axis, start loading the image.
      rootMargin: '50px 0px',
      threshold: 0.01
    };

    // Define our image observer and observe each lazy load image.
    let observer = new IntersectionObserver(onIntersection, config);
    forEach(images, function (index, image) {
      observer.observe(image);
    });

    function onIntersection(entries) {
      // Loop through the entries
      forEach(entries, function (index, entry) {
        // Are we in viewport?
        if (entry.intersectionRatio > 0) {
          // Stop watching this element and load the image.
          observer.unobserve(entry.target);
          loadImage(entry.target);
        }
      });
    }

  }

  /*
   * Fallback: just load images if browser does not support IntersectionObserver.
   */
  if (!('IntersectionObserver' in window)) {
    forEach(images, function (index, image) {
      loadImage(image);
    });
  }

  // Load the image by setting the src based on the data-src and add loaded class.
  function loadImage(image) {

    // Set src based on data-src for normal images.
    if (image.hasAttribute('data-src')) {
      image.setAttribute('src', image.getAttribute('data-src'));
      image.classList.add( 'loaded' );
    }

    // Set source src set for picture elements.
    var parent = image.parentElement;
    if (parent.tagName.toLowerCase() == 'picture') {
      forEach(parent.querySelectorAll('source'), function (index, source) {
        if (source.hasAttribute('data-srcset')) {
          source.setAttribute('srcset', source.getAttribute('data-srcset'));
        }
      });
    }

  }

}());
