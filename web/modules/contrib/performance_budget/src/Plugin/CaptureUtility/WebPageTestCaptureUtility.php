<?php

namespace Drupal\performance_budget\Plugin\CaptureUtility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\web_page_archive\Plugin\ConfigurableCaptureUtilityBase;
use Drupal\web_page_archive\Plugin\CaptureResponse\UriCaptureResponse;
use WidgetsBurritos\WebPageTest\WebPageTest;

/**
 * Skeleton capture utility, useful for creating new plugins.
 *
 * @CaptureUtility(
 *   id = "pb_wpt_capture",
 *   label = @Translation("Web page test capture utility", context = "Web Page Archive"),
 *   description = @Translation("Runs url through webpagetest.org.", context = "Web Page Archive")
 * )
 */
class WebPageTestCaptureUtility extends ConfigurableCaptureUtilityBase {

  /**
   * Most recent response.
   *
   * @var string|null
   */
  private $response = NULL;

  /**
   * {@inheritdoc}
   */
  public function capture(array $data = []) {
    // Configuration data is stored in $this->configuration. For example:
    $wpt_api = $this->configuration['wpt_api'];
    $wpt = new WebPageTest($wpt_api);
    $state_key = $this->getStateKey($data);
    $test_id = $this->state()->get($state_key);
    $response_content = '';
    if (!isset($test_id)) {
      if ($response = $wpt->runTest($data['url'])) {
        if ($response->statusCode == 200) {
          $this->state()->set($state_key, $response->data->testId);
        }
      }

      $this->response = NULL;
    }
    else {
      if ($response = $wpt->getTestStatus($test_id)) {
        if ($response->statusCode == 200) {
          // Test is complete.
          if ($response = $wpt->getTestResults($test_id)) {
            $file_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
            $save_dir = "{$file_path}/performance-budget/wpt/{$data['web_page_archive']->id()}/{$data['run_uuid']}";
            $file_name = preg_replace('/[^a-z0-9]+/', '-', strtolower($data['url'])) . '.json';
            $file_location = "{$save_dir}/{$file_name}";

            if (!file_prepare_directory($save_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
              throw new \Exception("Could not write to $save_dir");
            }
            file_put_contents($file_location, json_encode($response->data));
            $this->response = new UriCaptureResponse($file_location, $data['url']);
          }
          else {
            throw new \Exception($this->t('WPT test @test_id failed - Could not retrieve test results', ['@test_id' => $test_id]));
          }
          // Cleanup old state key.
          $this->state()->delete($state_key);
        }
        elseif (in_array($response->statusCode, [100, 101, 102])) {
          // Test is still running.
          $this->response = NULL;
        }
        else {
          // Test failed.
          $strings = [
            '@test_id' => $test_id,
            '@http_code' => $response->statusCode,
          ];
          throw new \Exception($this->t('WPT test @test_id failed - HTTP status code: @http_code', $strings));
        }
      }
    }

    return $this;
  }

  /**
   * Retrieves unique state key for data array.
   */
  private function getStateKey(array $data = []) {
    return "pb_wpt_capture:{$data['run_uuid']}:{$data['url']}";
  }

  /**
   * Retrieves unique state key for data array.
   */
  private function state() {
    return \Drupal::state();
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wpt_api' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['wpt_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('webpagetest.org API Key'),
      '#description' => $this->t('Enter your webpagetest.org API Key. http://www.webpagetest.org/getkey.php'),
      '#default_value' => isset($this->configuration['wpt_api']) ? $this->configuration['wpt_api'] : $this->defaultConfiguration()['wpt_api'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['wpt_api'] = $form_state->getValue('wpt_api');
  }

}
