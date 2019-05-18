<?php
/**
 * @file
 * Contains \Drupal\monitoring_test\Plugin\monitoring\SensorPlugin\TestSensorPlugin.
 */

namespace Drupal\monitoring_test\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;

/**
 * Test sensor to report status as provided by external arguments.
 *
 * @SensorPlugin(
 *   id = "test_sensor",
 *   label = @Translation("Test SensorPlugin"),
 *   description = @Translation("Test sensor to report status as provided by external arguments."),
 *   addable = TRUE
 * )
 *
 */
class TestSensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  protected $testSensorResultData;

  function __construct(SensorConfig $sensor_config, $sensor_id, $definition) {
    parent::__construct($sensor_config, $sensor_id, $definition);

    // Load test sensor data which will be used in the runSensor() logic.
    $this->testSensorResultData = \Drupal::state()->get('monitoring_test.sensor_result_data', array(
      'sensor_status' => NULL,
      'sensor_message'=> NULL,
      'sensor_value' => NULL,
      'sensor_expected_value' => NULL,
      'sensor_exception_message' => NULL,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    // Sleep here for a while as running this sensor may result in 0 execution
    // time.
    usleep(1);

    if (isset($this->testSensorResultData['sensor_exception_message'])) {
      throw new \RuntimeException($this->testSensorResultData['sensor_exception_message']);
    }

    if (isset($this->testSensorResultData['sensor_value'])) {
      $result->setValue($this->testSensorResultData['sensor_value']);
    }

    if (!empty($this->testSensorResultData['sensor_status'])) {
      $result->setStatus($this->testSensorResultData['sensor_status']);
    }

    if (!empty($this->testSensorResultData['sensor_message'])) {
      $result->addStatusMessage($this->testSensorResultData['sensor_message']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = [];
    $output['test'] = array(
      '#type' => 'item',
      '#title' => t('Test'),
      '#markup' => 'call debug',
    );
    return $output;
  }
}

/*

search example:

timer_start('search');



    $this->setStartTime(timer_read('search'));
    $this->response = drupal_http_request(url('search/node/search phrase', array('absolute' => TRUE)));
    $this->setEndTime(timer_read('search'));

    $htmlDom = new DOMDocument();
    @$htmlDom->loadHTML($this->response->data);
    $elements = simplexml_import_dom($htmlDom);
    $h2 = $elements->xpath('//div[@id="block-system-main"]/div/h2');

    if ($this->response->code != 200) {
      $this->setSensorStatus(self::SENSOR_STATUS_CRITICAL, 'Search is not accessible');
      return;
    }
    elseif ($h2[0] != t('Your search yielded no results')) {
      $this->setSensorStatus(self::SENSOR_STATUS_WARNING, 'Search does not yield expected results');
    }
    else {
      $this->setSensorStatus(self::SENSOR_STATUS_OK, 'Search accessible');
    }

*/
