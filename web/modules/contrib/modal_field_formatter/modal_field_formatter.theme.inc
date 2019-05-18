<?php

use Drupal\Core\Render\Element;

/**
 * @param $variables
 */
function modal_field_formatter_preprocess_modal_link(&$variables) {
  // We add the url as a href attribute.
  /** @var \Drupal\Core\Url $url */
  $url = $variables['url'];
  $variables['attributes']['href'] = $url->toString();
}
