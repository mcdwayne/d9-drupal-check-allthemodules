<?php

/**
 * @file
 * Contains nameday.page.inc.
 */


/**
 * Prepares variables for Name day block template.
 *
 * Default template: nameday.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - date
 *   - name
 *   - holiday
 */
function template_preprocess_nameday(array &$variables) {
  $config = \Drupal::config('nameday.settings');

  if ((bool) $config->get('show_date') === TRUE) {
    $timestamp = \Drupal::time()->getRequestTime();
    $type = $config->get('date_format');
    $format = $config->get('date_format_custom');

    $variables['date'] = \Drupal::service('date.formatter')
      ->format($timestamp, $type, $format);
  }
  else {
    $variables['date'] = FALSE;
  }

  if ((bool) $config->get('show_holiday') === FALSE || empty($variables['holiday'])) {
    $variables['holiday'] = FALSE;
  }

}
