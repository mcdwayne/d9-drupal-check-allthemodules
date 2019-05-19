<?php

/**
 * @file
 * Contains webpay_transaction.page.inc.
 *
 * Page callback for Webpay transaction entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Webpay transaction templates.
 *
 * Default template: webpay_transaction.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_webpay_transaction(array &$variables) {
  // Fetch WebpayTransaction Entity Object.
  $webpay_transaction = $variables['elements']['#webpay_transaction'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
