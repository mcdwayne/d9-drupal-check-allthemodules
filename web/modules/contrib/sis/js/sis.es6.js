/**
 * @file
 * Smart Imaging Service behavior.
 */

(function(document, $, Drupal) {

  let sisEnabledNodes = [];

  /**
   * Update (sizes) attribute(s) of the SIS enabled nodes.
   */
  function updateSisEnabledNodes() {
    for (const node of sisEnabledNodes) {
      const pictureNode = node.closest('picture');
      const data = JSON.parse(node.dataset.sis);
      const parentElement = pictureNode ? pictureNode.parentElement : node.parentElement;

      // Set the new width for the sizes attribute.
      // Maximize the width to the widest image to prevent up scaling.
      const sizesWidth = `${parentElement.clientWidth < data.maxImageWidth ? parentElement.clientWidth : data.maxImageWidth}px`;

      // @todo Allow lazy loading.
      node.setAttribute('sizes', sizesWidth);

      // Stretch the low resolution image until the high resolution image
      // is loaded.
      // @todo make this a setting (and restore image ratio).
      // @todo check if we can do this server side (faster).
      if (pictureNode) {
        pictureNode.setAttribute('width', sizesWidth);
        return;
      }
      node.setAttribute('width', sizesWidth);
    }
  }

  // Listen to viewport changes triggered by the the Drupal displace lib.
  $(document).on('drupalViewportOffsetChange.sis', updateSisEnabledNodes);

  /**
   * Transforms responsive image styles into Smart Imaging Styles.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches SIS behaviour to SIS enabled responsive images.
   */
  Drupal.behaviors.sis = {
    attach(context) {
      sisEnabledNodes = [
        ...sisEnabledNodes,
        ...context.querySelectorAll('[data-sis]')
      ];

      $(document).trigger('drupalViewportOffsetChange.sis');
    },
  };
})(document, jQuery, Drupal);
