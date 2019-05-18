<?php

/**
 * @file
 * Hooks provided by klipfolio_field.
 */

/**
 * Preprocess Klipfolio field.
 *
 *  - #attributes: Are used by javascript to initialize the Klipfolio widget.
 *  - #attributes.id: Used by Klipfolio to identify the correct container.
 *  - #attributes.data-klipfolio-id: Used to store the Klipfolio ID.
 *  - #attributes.data-klipfolio-width: Used to store the width of the widget.
 *  - #attributes.data-klipfolio-theme: Used to store the theme of the widget.
 *  - #attributes.data-klipfolio-title: Used to store the title of the widget.
 */
function hook_preprocess_klipfolio_field(&$variables) {
  $variables['#attributes']['class'][] = 'my-extra-class';
}
