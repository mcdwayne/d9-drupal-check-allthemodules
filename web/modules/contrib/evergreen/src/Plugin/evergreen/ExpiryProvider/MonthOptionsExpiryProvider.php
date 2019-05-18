<?php

namespace Drupal\evergreen\Plugin\evergreen\ExpiryProvider;

use Drupal\evergreen\ExpiryProviderBase;

/**
 * Provides an open textfield for specifying expiry time.
 *
 * Coupled with the Drupal\evergreen\ExpiryParser to parse the time as needed.
 *
 * @ExpiryProvider(
 *   id = "month_options_expiry",
 *   label = @Translation("Month options expiry provider"),
 *   description = @Translation("Provides a set of month expiration options")
 * )
 */
class MonthOptionsExpiryProvider extends ExpiryProviderBase {

  /**
   * {@inheritDoc}
   */
  public function getFormElement($value, array $options = []) {
    $options = array_merge(['label' => 'Expiration time'], $options);
    return [
      '#type' => 'select',
      '#title' => $options['label'],
      '#options' => [
        EVERGREEN_ONE_DAY * 30 => '30 days',
        EVERGREEN_ONE_DAY * 60 => '60 days',
        EVERGREEN_ONE_DAY * 90 => '90 days',
        EVERGREEN_ONE_DAY * 182 => '6 months',
        EVERGREEN_ONE_DAY * 365 => '12 months',
        EVERGREEN_ONE_DAY * 547 => '18 months',
      ],
      '#default_value' => $value,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function processValue($value) {
  }

}
