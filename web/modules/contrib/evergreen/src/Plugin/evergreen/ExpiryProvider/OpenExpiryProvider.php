<?php

namespace Drupal\evergreen\Plugin\evergreen\ExpiryProvider;

use Drupal\evergreen\ExpiryProviderBase;

/**
 * Provides an open textfield for specifying expiry time.
 *
 * Coupled with the Drupal\evergreen\ExpiryParser to parse the time as needed.
 *
 * @ExpiryProvider(
 *   id = "open_expiry",
 *   label = @Translation("Open expiry provider"),
 *   description = @Translation("Provides an open textfield for entering expiry settings")
 * )
 */
class OpenExpiryProvider extends ExpiryProviderBase {

  /**
   * {@inheritDoc}
   */
  public function getFormElement($value, array $options = []) {
    $options = array_merge(['label' => 'Expiration time'], $options);
    return [
      '#type' => 'textfield',
      '#title' => $options['label'],
      '#default_value' => evergreen_get_readable_expiry($value),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function processValue($value) {
  }

}
