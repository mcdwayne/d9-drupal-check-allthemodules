<?php

/**
 * @file
 * Contains \Drupal\nws_weather\Plugin\Block\MultiDayForecast.
 */

namespace Drupal\nws_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides the MultiDayForecast block.
 *
 * @Block(
 *   id = "multidayforecast",
 *   admin_label = @Translation("Multi Day Forecast")
 * )
 */
class MultiDayForecast extends BlockBase {
  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access nws_weather');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return array('nws_weather');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('nws_weather.settings');
    $block = array();
    $forecastdata = nws_weather_NDFDgenByDay($config->get('nws_weather_lat'),
      $config->get('nws_weather_lon'),
      '',
      $config->get('nws_weather_daily_days')
    );
    $location = SafeMarkup::checkPlain($config->get('nws_weather_location_name'));
    return array(
      '#theme' => 'nws_weather_forecast',
      '#attached' => array(
        'library' => array(
          'nws_weather/nws_weather.module',
        ),
      ),
      '#title' => $location ? t('Weather Forecast for !location', array('!location' => $location)) : t('Weather Forecast'),
      '#dataForecast' => $forecastdata,
      '#required' => array(
        'Daily Maximum Temperature',
        'Daily Minimum Temperature',
        'Conditions Icons',
      ),
      '#display' => array(
        'Conditions Icons',
        'Weather Type, Coverage, and Intensity',
        'Daily Maximum Temperature',
        'Daily Minimum Temperature',
      ),
    );
  }
}
