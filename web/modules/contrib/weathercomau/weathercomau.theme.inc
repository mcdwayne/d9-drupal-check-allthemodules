<?php

/**
 * @file
 * The theme functions of the Weather.com.au module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\weathercomau\Feed\WCAFeed;

/**
 * Prepares variables for Weather.com.au widget templates.
 *
 * Default template: weathercomau-widget.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_weathercomau_widget_block(&$variables) {
  $configuration = $variables['elements']['#configuration'];
  $weather = \Drupal::service('weathercomau.reader')->read($configuration['city'], $configuration['state']);

  $variables['display_current_conditions'] = $configuration['current'];
  $variables['current_conditions'] = array(
    '#theme' => 'weathercomau_current_conditions',
    '#title' => $weather['current']['title'],
    '#current_conditions' => $weather['current']['data'],
  );
  $variables['current_conditions'] = \Drupal::service('renderer')->render($variables['current_conditions']);

  $variables['display_forecast'] = ($configuration['days'] > 0);
  $variables['forecast'] = array(
    '#theme' => 'weathercomau_forecast',
    '#title' => $weather['forecast']['title'],
    '#forecasts' => array_slice($weather['forecast']['data'], 0, $configuration['days']),
  );
  $variables['forecast'] = \Drupal::service('renderer')->render($variables['forecast']);

  $variables['credits'] = \Drupal::l($weather['link']['title'], Url::fromUri($weather['link']['url'], array(
    'attributes' => array('target' => '_blank'),
  )));
}


/**
 * Prepares variables for Weather.com.au current conditions templates.
 *
 * Default template: weathercomau-current-conditions.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_weathercomau_current_conditions(&$variables) {
  $current_conditions = $variables['elements']['#current_conditions'];
  $title = $variables['elements']['#title'];

  $variables['title'] = $title;
  $variables['items'] = array();
  $variables['items_classes'] = array();

  foreach ($current_conditions as $key => $value) {
    $variables['items_classes'][$key][] = Html::getClass('weathercomau-current-conditions__item');
    $variables['items_classes'][$key][] = Html::getClass('weathercomau-current-conditions__item--' . $key);
    $variables['items_classes'][$key] = implode(' ', $variables['items_classes'][$key]);

    switch ($key) {
      case 'temperature':
      case 'dewPoint':
        $item_value = weathercomau_format_temperature($value);
        break;

      case 'humidity':
        $item_value = weathercomau_format_humidity($value);
        break;

      case 'windSpeed':
      case 'windGusts':
        $item_value = weathercomau_format_wind_speed($value);
        break;

      case 'pressure':
        $item_value = weathercomau_format_pressure($value);
        break;

      case 'rain':
        $item_value = weathercomau_format_rainfall($value);
        break;

      default:
        $item_value = $value;
    }

    // Extract a name out of the key (e.g. windSpeed becomes Wind speed).
    $item_name = Unicode::ucfirst(implode(' ', preg_split('/(?=[A-Z])/', $key)));

    $variables['items'][$key] = array(
      'name' => $item_name,
      'value' => $item_value,
    );
  }
}


/**
 * Prepares variables for Weather.com.au forecast templates.
 *
 * Default template: weathercomau-forecast.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_weathercomau_forecast(&$variables) {
  $forecasts = $variables['elements']['#forecasts'];
  $title = $variables['elements']['#title'];

  $variables['title'] = $title;
  $variables['items'] = array();
  $variables['items_classes'] = array();

  foreach ($forecasts as $key => $value) {
    $variables['items_classes'][$key][] = 'weathercomau-forecast__day';
    $variables['items_classes'][$key][] = Html::getClass('weathercomau-forecast__day--' . $value['day']);

    if (($key === 0) && ($key !== (count($forecasts) - 1))) {
      $variables['items_classes'][$key][] = 'weathercomau-forecast__day--first';
    }
    if (($key !== 0) && ($key === (count($forecasts) - 1))) {
      $variables['items_classes'][$key][] = 'weathercomau-forecast__day--last';
    }

    $variables['items_classes'][$key] = implode(' ', $variables['items_classes'][$key]);

    $item = array(
      '#theme' => 'weathercomau_forecast_day',
      '#data' => $value,
    );
    $variables['items'][$key] = \Drupal::service('renderer')->render($item);
  }
}


/**
 * Prepares variables for Weather.com.au forecast day templates.
 *
 * Default template: weathercomau-forecast-day.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_weathercomau_forecast_day(&$variables) {
  $variables['data'] = $variables['elements']['#data'];
}
