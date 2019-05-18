<?php

namespace Drupal\monster_menus\Form;

use Drupal\Core\Url;

/**
 * Provides a helper to set the destination for the form Cancel button.
 */
trait SetDestinationTrait {

  protected function setDestination(&$url) {
    // We can't use $dest = \Drupal::destination()->getAsArray() here, since
    // that defaults to the current page if destination isn't set.
    if (!empty($dest = \Drupal::request()->query->get('destination', ''))) {
      $parsed = parse_url(urldecode($dest));
      if (!empty($parsed['path'])) {
        // The contextual module prepends the base_path(), so remove it here.
        $base_path = base_path();
        $base_len = strlen($base_path);
        if (!strncmp($parsed['path'], $base_path, $base_len)) {
          $parsed['path'] = substr($parsed['path'], $base_len);
          // Set the relative path in the destination, for future use. in
          // \Drupal\Core\Form\ConfirmFormHelper::buildCancelLink(), since that
          // function doesn't correctly handle site-absolute URLs.
          \Drupal::request()->query->set('destination', substr($dest, $base_len));
        }
        $url = Url::fromUserInput('/' . $parsed['path'], [
          'query' => empty($parsed['query']) ? '' : $parsed['query'],
          'fragment' => empty($parsed['fragment']) ? '' : $parsed['fragment'],
        ]);
      }
    }
  }

}
