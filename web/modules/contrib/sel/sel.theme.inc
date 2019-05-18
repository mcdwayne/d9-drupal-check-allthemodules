<?php

/**
 * @file
 * Callbacks and theming for Safe External Links.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * Prepares variables for Sel Spamspan template.
 *
 * Default template: sel-spamspan.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - email: An email address.
 *   - title: An optional label for the email link.
 *   - settings: optional spamspan settings.
 */
function template_preprocess_sel_spamspan(array &$variables) {
  if (\Drupal::service('module_handler')->moduleExists('spamspan')) {
    $variables['spamspan'] = [
      '#markup' => spamspan($variables['email'], $variables['settings']),
    ];
    $variables['#attached']['library'][] = 'spamspan/obfuscate';

    if (!empty($variables['title'])) {
      $spamspan_dom = Html::load($variables['spamspan']['#markup']);
      $spans = $spamspan_dom->getElementsByTagName('span');

      foreach ($spans as $span) {
        $classes = $span->getAttribute('class');

        if (Unicode::strpos($classes, 'spamspan') !== FALSE) {
          $label = $spamspan_dom->createElement('span');
          $label->appendChild(new DOMText('(' . $variables['title'] . ')'));
          $label->setAttribute('class', 't');

          $span->appendChild(new DOMText(' '));
          $span->appendChild($label);

          break 1;
        }
      }

      $variables['spamspan']['#markup'] = Html::serialize($spamspan_dom);
    }
  }
  else {
    $variables['spamspan'] = str_replace('@', ' [at] ', $variables['email']);
  }

  unset($variables['email']);
  unset($variables['settings']);
}
