<?php
/**
* @file
* Contains \Drupal\weather_block\Plugin\Block\WeatherBlockCurrentLocalWeatherBlock.
*/

namespace Drupal\weather_block\Plugin\Block;
use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\weather_block\WeatherImportYahoo;
use Drupal\weather_block\WeatherImportWwo;
use Drupal\weather_block\Form\WeatherBlockLocalWeatherSelectForm;

/**
* Provides a block for the current local weather.
*
* @Block(
*   id = "weatherblock_block_current_local_weather",
*   admin_label = @Translation("Weather Block - Current Local Weather")
* )
*/
class WeatherBlockCurrentLocalWeatherBlock extends BlockBase {
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */

  public function build() {

    if (isset($_SESSION['weather_city_local'])) {
      $city = $_SESSION['weather_city_local'];
    }
    else {
      $city = $this->configuration['default_local_city'];
    }

    $this->configuration['label'] = $this->t($this->configuration['block_label']);

    if ($this->configuration['provider'] == 'yahoo') {

      $query = db_query("select * from taxonomy_term__field_yahoo_id where entity_id = '" . $city . "'");

      $result = $query->fetchAll();

      $weather_local = new WeatherImportYahoo($result[0]->field_yahoo_id_value, $this->configuration['units']);
    }
    else {

      $query = db_query("select * from taxonomy_term__field_wwo_id where entity_id = '" . $city . "'");

      $result = $query->fetchAll();

      $weather_local = new WeatherImportWwo($result[0]->field_wwo_id_value, $this->configuration['units'], $this->configuration['regkey']);
    }

    if (isset($_COOKIE['weather_latitude']) && isset($_COOKIE['weather_longitude'])) {

//      $curlocation = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng=" . $_COOKIE['weather_latitude'] . "," . $_COOKIE['weather_longitude']. "&sensor=false");

      $curlocation = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng=62,23&sensor=false");

      $city1 = json_decode($curlocation);

      foreach ($city1->results as $key => $val) {

        if (in_array('administrative_area_level_3', $val->types) || in_array('administrative_area_level_2', $val->types) || in_array('administrative_area_level_1', $val->types)) {

          $location = $val->address_components[0]->long_name;

          break;
        }
      }

      $query = db_query("select * from taxonomy_term_data where name = '" . $location . "'");

      $result = $query->fetchAll();

      if (count($result) == 0) {

        $query = db_query("select * from taxonomy_term_data where tid = '" . $city . "'");

        $result = $query->fetchAll();
      }
      else {

        $city = $result[0]->tid;
      }
    }
    else {

      $query = db_query("select * from taxonomy_term_data where tid = '" . $city . "'");

      $result = $query->fetchAll();
    }

    if ($this->configuration['units'] == 'c') {
      $wind = $weather_local->result['current']['wind']['speed'] . "m/s";
    }
    else {
      $wind = $weather_local->result['current']['wind']['speed'] . "mph";
    }

    if ($this->configuration['display_form'] == 1 || $this->configuration['display_form'] != 0) {
      $form = drupal_get_form(new WeatherBlockLocalWeatherSelectForm($city));
    }
    else {
       $form = '';
    }

    return array(
      '#theme' => 'weatherblock-block-current-weather',
      '#title' => $this->t('Current weather - ') . " " . $result[0]->name,
      '#current_conditions_icon' => drupal_get_path('module', 'weather_block') . "/weather-icons/" . $weather_local->result['current']['condition']['code'] . ".png",
      '#current_conditions_text' => $this->t($weather_local->result['current']['condition']['text']),
      '#current_conditions_wind_label' => $this->t("Wind"),
      '#current_conditions_wind_icon' => drupal_get_path('module', 'weather_block') . "/weather-icons/wind/" . $weather_local->result['current']['wind']['direction'] . ".png",
      '#current_conditions_wind_speed' => $wind,
      '#temperature_label' => $this->t('Temperature'),
      '#current_conditions_temp' => $weather_local->result['current']['temp'] . "&deg;" . ucfirst($this->configuration['units']),
      '#current_conditions_humidity' => $weather_local->result['current']['humidity'] . "%",
      '#current_conditions_humidity_label' => $this->t("Humidity"),
      '#form' => $form,
      '#attached' => array(
        'css' => array(drupal_get_path('module', 'weather_block') . '/css/weather_block.module.css'),
//        'js' => array(drupal_get_path('module', 'weather_block') . '/js/weather_block.js'),
      ),
    );
  }

  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Set the default label to '' so the views internal title is used.
    $form['label']['#default_value'] = '';
    $form['label']['#access'] = FALSE;

    // Unset the machine_name provided by BlockFormController.
    unset($form['id']['#machine_name']['source']);
    // Prevent users from changing the auto-generated block machine_name.
    $form['id']['#access'] = FALSE;

    // Allow to override the label on the actual page.
    $form['block_label_checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override title'),
      '#default_value' => !empty($this->configuration['block_label_checkbox']) ? $this->configuration['block_label_checkbox'] : 0,
    );

    $form['block_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => !empty($this->configuration['block_label']) ? $this->configuration['block_label'] : '',
      '#description' => $this->t('Changing the title here means it cannot be dynamically altered anymore.'),
    );

    $form['provider'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Provider'),
      '#options' => array('wwo' => $this->t('World Weather Online'), 'yahoo' => $this->t('Yahoo Weather'),
      ),
      '#default_value' => !empty($this->configuration['provider']) ? $this->configuration['provider'] : 'wwo',
      '#description' => $this->t('Select your preferred weather provider.'),
    );

    $form['regkey'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('World Weather Online registration key'),
      '#default_value' => !empty($this->configuration['regkey']) ? $this->configuration['regkey'] : '',
      '#description' => $this->t('To obtain a key, please visit http://worldweatheronline.com and register for an API key.'),
    );

    $options = array();

    $query = \Drupal::entityQuery('taxonomy_term');

    $result = $query->condition('field_local_city', 1)
                    ->execute();

    foreach ($result as $tid) {

      $query = db_query("select * from taxonomy_term_data where tid = '" . $tid . "'");

      $result = $query->fetchAll();

      $options[$result[0]->tid] = $result[0]->name;
    }

    $default_location_name = 'Helsinki';

    $default_location_tid = taxonomy_term_load_multiple_by_name($default_location_name, 'weather_block_cities');

    $form['default_local_city'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default local city'),
      '#options' => $options,
      '#default_value' => !empty($this->configuration['default_local_city']) ? $this->configuration['default_local_city'] : key($default_location_tid),
    );

    if (isset($this->configuration['display_form'])) {
      if ($this->configuration['display_form'] == 0 || $this->configuration['display_form'] == 1) {
        $display_form = $this->configuration['display_form'];
      }
      else {
        $display_form = 1;
      }
    }
    else {
      $display_form = 1;
    }

    $form['display_form'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display location selection form'),
      '#default_value' => $display_form,
    );

    $form['units'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Units'),
      '#options' => array('f' => $this->t('Fahrenheit (&deg;F), wind speed mph'), 'c' => $this->t('Celcius (&deg;C), wind speed m/s'),
      ),
      '#default_value' => !empty($this->configuration['units']) ? $this->configuration['units'] : 'c',
      '#description' => $this->t('Select the measurement units you wish to show the results with.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, &$form_state) {
//    if ($this->displaySet) {
//      $this->view->display_handler->blockValidate($this, $form, $form_state);
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {

    if (!empty($form_state['values']['block_label_checkbox'])) {
      $this->configuration['block_label_checkbox'] = $form_state['values']['block_label_checkbox'];
    }
    else {
      $this->configuration['block_label_checkbox'] = '';
    }

    if (!empty($form_state['values']['display_form'])) {
      $this->configuration['display_form'] = $form_state['values']['display_form'];
    }
    else {
      $this->configuration['display_form'] = 0;
    }

    $this->configuration['block_label'] = $form_state['values']['block_label'];

    $this->configuration['provider'] = $form_state['values']['provider'];

    $this->configuration['units'] = $form_state['values']['units'];

    $this->configuration['default_local_city'] = $form_state['values']['default_local_city'];

    $this->configuration['regkey'] = $form_state['values']['regkey'];
  }

  /**
   * Implements \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access content');
  }
}
