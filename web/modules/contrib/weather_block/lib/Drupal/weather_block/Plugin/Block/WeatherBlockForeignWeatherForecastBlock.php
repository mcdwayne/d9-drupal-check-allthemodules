<?php
/**
* @file
* Contains \Drupal\weather_block\Plugin\Block\WeatherBlockForeignWeatherForecastBlock.
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
use Drupal\weather_block\Form\WeatherBlockForeignWeatherSelectForm;

/**
* Provides a block for the foreign weather forecast.
*
* @Block(
*   id = "weatherblock_block_foreign_weather_forecast",
*   admin_label = @Translation("Weather Block - Foreign Weather Forecast")
* )
*/
class WeatherBlockForeignWeatherForecastBlock extends BlockBase {
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */

  public function build() {

    if (isset($_SESSION['weather_city_foreign'])) {
      $city = $_SESSION['weather_city_foreign'];
    }
    else {
      $city = $this->configuration['default_foreign_city'];
    }

    $this->configuration['label'] = $this->t($this->configuration['block_label']);

    if ($this->configuration['provider'] == 'yahoo') {

      $query = db_query("select * from taxonomy_term__field_yahoo_id where entity_id = '" . $city . "'");

      $result = $query->fetchAll();

      $weather_foreign = new WeatherImportYahoo($result[0]->field_yahoo_id_value, $this->configuration['units']);
    }
    else {

      $query = db_query("select * from taxonomy_term__field_wwo_id where entity_id = '" . $city . "'");

      $result = $query->fetchAll();

      $weather_foreign = new WeatherImportWwo($result[0]->field_wwo_id_value, $this->configuration['units'], $this->configuration['regkey']);
    }

    $i = 0;

    foreach ($weather_foreign->result['forecast'] as $key => $val) {

      $weather[$i]['date'] = $key;
      $weather[$i]['conditions']['image'] = drupal_get_path('module', 'weather_block') . "/weather-icons/" . $val['condition']['code'] . ".png";
      $weather[$i]['conditions']['text'] = $val['condition']['text'];
      $weather[$i]['temp_high']['label'] .= $this->t("Temperature high");
      $weather[$i]['temp_high']['value'] .= $val['temp']['high'] . "&deg;" . ucfirst($this->configuration['units']);
      $weather[$i]['temp_low']['label'] .= $this->t("Temperature low");
      $weather[$i]['temp_low']['value'] .= $val['temp']['low'] . "&deg;" . ucfirst($this->configuration['units']);

      $i++;
    }

    $query = db_query("select * from taxonomy_term_data where tid = '" . $city . "'");

    $result = $query->fetchAll();

    if ($this->configuration['display_form'] == 1 && $this->configuration['display_form'] != 0) {
      $form = drupal_get_form(new WeatherBlockForeignWeatherSelectForm($city));
    }
    else {
       $form = '';
    }

    return array(
      '#theme' => 'weatherblock-block-weather-forecast',
      '#title' => $this->t('Weather forecast - ') . $result[0]->name,
      '#forecast' => $weather,
      '#form' => $form,
      '#attached' => array(
        'css' => array(drupal_get_path('module', 'weather_block') . '/css/weather_block.module.css'),
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

    $query = \Drupal::entityQuery('taxonomy_term');

    $result = $query->condition('field_local_city', 0)
                    ->execute();

    foreach ($result as $tid) {

      $query = db_query("select * from taxonomy_term_data where tid = '" . $tid . "'");

      $result = $query->fetchAll();

      $options[$result[0]->tid] = $result[0]->name;
    }

    $default_location_name = 'London, UK';

    $default_location_tid = taxonomy_term_load_multiple_by_name($default_location_name, 'weather_block_cities');

    $form['default_foreign_city'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default Foreign city'),
      '#options' => $options,
      '#default_value' => !empty($this->configuration['default_foreign_city']) ? $this->configuration['default_foreign_city'] : key($default_location_tid),
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

    $this->configuration['default_foreign_city'] = $form_state['values']['default_foreign_city'];

    $this->configuration['regkey'] = $form_state['values']['regkey'];
  }

  /**
   * Implements \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access content');
  }
}
