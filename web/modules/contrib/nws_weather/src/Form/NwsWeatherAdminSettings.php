<?php

/**
 * @file
 * Contains \Drupal\nws_weather\Form\NwsWeatherAdminSettings.
 */

namespace Drupal\nws_weather\Form;

use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility;
use Drupal\Core\Cache\Cache;


class NwsWeatherAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nws_weather_admin_settings';
  }

  /**
   * Return a render array for the nws_weather block.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nws_weather.settings');
    $form = array();
    $form['default_location'] = array(
      '#type' => 'details',
      '#title' => t('Default location'),
      '#open' => TRUE,
      '#description' => t('The default location is used for the nws_weather block and is defined with a latitude and longitude. Only Lat/Lon pairs inside the U.S. will return data and display the forecast block.'),
    );
    $form['default_location']['lat'] = array(
      '#type' => 'textfield',
      '#title' => t('Latitude'),
      '#default_value' => $config->get('nws_weather_lat'),
      '#description' => t('The latitude of your default location.'),
      '#weight' => -12,
    );
    $form['default_location']['lon'] = array(
      '#type' => 'textfield',
      '#title' => t('Longitude'),
      '#default_value' => $config->get('nws_weather_lon'),
      '#description' => t('The longitude of your default location. Please use negative values for longitudes West of zero (e.g., -122.654.'),
      '#weight' => -11,
    );
    $form['default_location']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Display name'),
      '#default_value' => $config->get('nws_weather_location_name'),
      '#description' => t('The name to display for this location.'),
      '#size' => '30',
    );
    $form['display_options'] = array(
      '#type' => 'details',
      '#title' => t('Daily forecast display options'),
      '#open' => TRUE,
    );
    $form['display_options']['daily_days'] = array(
      '#type' => 'select',
      '#title' => t('Number of days to display'),
      '#default_value' => $config->get('nws_weather_daily_days'),
      '#options' => array(
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
      ),
      '#description' => t('Select number of days of forecast to display. May require clearing Drupal cache to take effect.'),
    );
    $form['display_options']['daily_display_elements'] = array(
      '#type' => 'checkboxes',
      '#default_value' => $config->get('nws_weather_daily_display_options'),
      '#title' => t('Forecast elements to display'),
      '#options' => array(
        'Daily Maximum Temperature' => 'Daily maximum temperature',
        'Daily Minimum Temperature' => 'Daily minimum temperature',
        'Weather Type, Coverage, and Intensity' => 'Weather type, coverage, and intensity',
        'Conditions Icons' => 'Conditions icons',
      ),
      '#description' => t('Turn on or off display of forecast elements.'),
    );

    // Retrieve the stored unit preferences.
    $form['units'] = array(
      '#type' => 'details',
      '#title' => t('Display units'),
      '#description' => t('You can specify which units should be used for displaying the data.'),
      '#open' => TRUE,
    );

    $form['units']['temperature'] = array(
      '#type' => 'select',
      '#title' => t('Temperature'),
      '#default_value' => $config->get('nws_weather_units'),
      '#options' => array(
        'fahrenheit' => t('Fahrenheit'),
        'celsius' => t('Celsius'),
      ),
    );

    $form['image_map'] = array(
      '#type' => 'details',
      '#title' => t('Image override'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => t('This configuration page also allows default National Weather Service icons to be overridden. Icon fields that are blank will not override default icons. Icons are limited to GIF, JPG, and jpg file formats.'),
    );
    $form['image_map']['replace'] = array(
      '#type' => 'checkbox',
      '#title' => t('Replace NWS weather images'),
      '#default_value' => $config->get('nws_weather_override_bool'),
      '#description' => t('If enabled, the nws_weather module will use the files specified below instead of the icons provided by the National Weather Service.'),
      '#weight' => -10,
    );
    $form['image_map']['directory'] = array(
      '#type' => 'textfield',
      '#title' => t('Image override directory'),
      '#default_value' => $config->get('nws_weather_override_directory'),
      '#description' => t('A file system path where override images are stored, e.g., "@modpath/images". All files specified as image overrides must reside in this directory in order to be located by the module.', array('@modpath' => drupal_get_path('module', 'nws_weather'))),
      '#weight' => -9,
    );
    $form['image_map']['file_map'] = array(
      '#type' => 'details',
      '#title' => t('Image file overrides'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => t('The default location can be defined with a latitude and longitude. Only Lat/Lon pairs inside the U.S. will return data. This configuration page also allows default National Weather Service icons to be overridden. Icon fields that are blank will not override default icons. Icons are limited to GIF, JPG, and jpg file formats.'),
    );

    $form['data_source'] = array(
      '#type' => 'details',
      '#title' => t('Data source location'),
      '#open' => FALSE,
      '#description' => t('The NWS data source may change or you may use a custom source with out needing to hack the module. If the block refuses to display, you can enable dblog and see if the source location is resolving. There is no validation and WSDL URL is expected, such as, http://graphical.weather.gov/xml/DWMLgen/wsdl/ndfdXML.wsdl'),
    );

    $form['data_source']['data_source_location'] = array(
      '#type' => 'textfield',
      '#title' => t('Location'),
      '#default_value' => $config->get('nws_weather_wsdl_url', NWS_WEATHER_WSDL_URL_DEFAULT),
      '#description' => t('The location of your desired data source.'),
      '#weight' => -8,
    );

    // Create a form field for each image defined.
    $icons = $this->config('nws_weather.icon_maps')->get();
    foreach ($icons as $original => $override) {
      $form['image_map']['file_map'][$original] = array(
        '#type' => 'textfield',
        '#title' => NWS_WEATHER_DEFAULT_ICON_URL . $original . '.*',
        '#default_value' => $icons[$original],
        '#size' => 80,
      );
    };
    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate data prior to submission.
   *
   * Checking for numeric values on Longitude and Latitude.
   *
   * @TODO should check for override directory if overrides enabled.
   * @TODO should check for override file existence if overrides enabled.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $lat = $form_state->getValue('lat');
    if (!is_numeric($lat) || $lat > 90 || $lat < -90) {
      $form_state->setErrorByName('lat', $this->t('Latitude value must be numeric and between 90 and -90.'));
    }
    $lon = $form_state->getValue('lon');
    if (!is_numeric($lon) || $lon > 180 || $lon < -180) {
      $form_state->setErrorByName('lon', $this->t('Latitude value must be numeric and between 180 and -180.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'nws_weather.settings',
      'nws_weather.icon_maps',
    );
  }

  /**
   * Form submit function for MultiDayForecast Administration Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    Cache::invalidateTags(array('nws_weather'));
    $config = $this->config('nws_weather.settings');
    $config->set('nws_weather_lat', $form_state->getValue('lat'))
      ->set('nws_weather_lon', $form_state->getValue('lon'))
      ->set('nws_weather_location_name', $form_state->getValue('name'))
      ->set('nws_weather_daily_days', $form_state->getValue('daily_days'))
      ->set('nws_weather_units', $form_state->getValue('temperature'))
      ->set('nws_weather_daily_display_options', $form_state->getValue('daily_display_elements'));
    // Image Override options.
    $image_map = $form_state->getValue('image_map');
    $config->set('nws_weather_override_bool', $image_map['replace']);
    $config->set('nws_weather_override_directory', $image_map['directory']);
    $config->save();
    $this->config('nws_weather.icon_maps')->setData($image_map['file_map'])->save();
    parent::submitForm($form, $form_state);
  }
}
