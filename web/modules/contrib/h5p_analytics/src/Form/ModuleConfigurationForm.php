<?php

namespace Drupal\h5p_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use GuzzleHttp\Exception\RequestException;

/**
 * Class ModuleConfigurationForm.
 */
class ModuleConfigurationForm extends ConfigFormBase {
  /**
   * Config settings
   * @var string
   */
  const SETTINGS = 'h5p_analytics.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'h5p_analytics_module_configuration_form';
  }

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['connection_test_messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'connection-test-messages',
      ],
    ];

    $form['lrs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('LRS'),
      '#description' => $this->t('LRS Settings'),
      '#weight' => '0',
    ];
    $form['lrs']['xapi_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('xAPI Endpoint'),
      '#description' => $this->t('xAPI Endpoint URL (no trailing slash)'),
      '#weight' => '0',
      '#size' => 64,
      '#default_value' => $config->get('xapi_endpoint'),
    ];
    $form['lrs']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('LRS Client Key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => $config->get('key'),
    ];
    $form['lrs']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#description' => $this->t('LRS Client Secret'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
      '#default_value' => $config->get('secret'),
    ];
    $form['lrs']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#description' => $this->t('Size of the statements batch to be sent to LRS.'),
      '#min' => 1,
      '#max' => 1000,
      '#step' => 1,
      '#size' => 64,
      '#weight' => '3',
      '#default_value' => $config->get('batch_size'),
    ];

    $build = parent::buildForm($form, $form_state);

    $build['actions']['test_connection'] = [
      '#type' => 'button',
      '#name' => 'test_connection',
      '#ajax' => [
        'callback' => [$this, 'testConnectionCallback'],
        'effect' => 'fade',
      ],
      '#value' => $this->t('Test LRS connection'),
      '#disabled' => ($config->get('xapi_endpoint') && $config->get('key') && $config->get('secret')) ? FALSE : TRUE,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configFactory->getEditable(static::SETTINGS)
    ->set('xapi_endpoint', $values['xapi_endpoint'])
    ->set('key', $values['key'])
    ->set('secret', $values['secret'])
    ->set('batch_size', $values['batch_size'])
    ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * AJAX callback for testing LRS connnection, displays status messages.
   *
   * @param  array              $form
   *   Form
   * @param  Drupal\Core\Form\FormStateInterface $form_state
   *   Form state
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse with command to display status mesages
   */
  public function testConnectionCallback(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
    $values = $form_state->getValues();

    try {
      $response = \Drupal::service('h5p_analytics.lrs')->makeStatementsHttpRequest($values['xapi_endpoint'], $values['key'], $values['secret'], []);
      $messenger->addMessage($this->t('Connection to LRS service is working well. Service responded with code %status and message %message.', ['%status' => $response->getStatusCode(), '%message' => $response->getReasonPhrase()]), 'status');
    } catch (RequestException $e) {
      $messenger->addMessage($this->t('Service responsed with code %code and message %message.', ['%code' => $e->getCode(), '%message' => $e->hasResponse() ? $e->getResponse()->getReasonPhrase() : '']), 'warning');
      $messenger->addMessage($e->getMessage(), 'error');
    } catch (\Exception $e) {
      $messenger->addMessage($e->getMessage(), 'error');
    }

    $response = new AjaxResponse();
    $status_messages = array('#type' => 'status_messages');
    $messages = \Drupal::service('renderer')->renderRoot($status_messages);
    if (!empty($messages)) {
      $response->addCommand(new HtmlCommand('#connection-test-messages', $messages));
    }

    return $response;
  }

}
