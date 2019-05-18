/**
 * @file
 * Debug JS helper.
 */

(function () {
  'use strict';

  window.onload = function () {
    let innerElements = document.querySelectorAll('.debug-information .container > .inner');
    innerElements.forEach(function (element) {
      element.addEventListener('click', innerElementClick);
    });
  };

  /**
   * Opens/closes an inner element.
   *
   * @param {object} event
   *   Click event object.
   */
  function innerElementClick(event) {
    let children = this.parentNode.children;

    for (let child in children) {
      if (children.hasOwnProperty(child)) {
        let classes = children[child].className.split(' ');

        if (classes.indexOf('container') !== -1) {
          let displayStyle = getComputedStyle(children[child])['display'];

          if (displayStyle === 'none') {
            children[child].style.display = 'block';
          }
          else {
            children[child].style.display = 'none';
          }
        }
      }
    }
  }

})();
