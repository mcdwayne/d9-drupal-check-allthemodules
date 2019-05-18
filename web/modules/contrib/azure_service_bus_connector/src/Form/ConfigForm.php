<?php

namespace Drupal\azure_service_bus_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\azure_service_bus_connector\AzureApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use WindowsAzure\Common\ServiceException;

/**
 * Azure Configuration form class.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * The Azure API.
   *
   * @var \Drupal\azure_service_bus_connector\AzureApi
   */
  protected $azureApi;

  /**
   * Constructs a \Drupal\azure_service_bus_connector ConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\azure_service_bus_connector\AzureApi $azure_api
   *   Azure API functions.
   *
   * @codeCoverageIgnore
   */
  public function __construct(ConfigFactoryInterface $config_factory, AzureApi $azure_api) {
    parent::__construct($config_factory);
    $this->azureApi = $azure_api;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('azure_service_bus_connector.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'azure_service_bus_connector_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('azure_service_bus_connector.settings');
    $config_with_overrides = $this->configFactory()->get('azure_service_bus_connector.settings');

    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection details'),
      '#collapsible' => FALSE,
    ];

    // Check for overridden values and let user know if they cannot be updated
    // via the form, since they are already stored in code.
    $access_key_name_is_overridden = $config->get('shared_access_key_name') !== $config_with_overrides->get('shared_access_key_name');
    $access_key_is_overridden = $config->get('shared_access_key') !== $config_with_overrides->get('shared_access_key');
    $endpoint_is_overridden = $config->get('endpoint') !== $config_with_overrides->get('endpoint');

    $form['api_settings']['shared_access_key_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shared Access Key Name'),
      '#default_value' => $access_key_name_is_overridden ? $config_with_overrides->get('shared_access_key_name') : $config->get('shared_access_key_name'),
      '#description' => $access_key_name_is_overridden ? $this->t('This value is set in code and cannot be overridden through this form.') : '',
      '#required' => TRUE,
      '#attributes' => [
        'disabled' => $access_key_name_is_overridden ? 'disabled' : FALSE,
      ],
    ];
    $form['api_settings']['shared_access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shared Access Key'),
      '#default_value' => $access_key_is_overridden ? $config_with_overrides->get('shared_access_key') : $config->get('shared_access_key'),
      '#description' => $access_key_is_overridden ? $this->t('This value is set in code and cannot be overridden through this form.') : '',
      '#required' => TRUE,
      '#attributes' => [
        'disabled' => $access_key_is_overridden ? 'disabled' : FALSE,
      ],
    ];
    $form['api_settings']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#default_value' => $endpoint_is_overridden ? $config_with_overrides->get('endpoint') : $config->get('endpoint'),
      '#description' => $endpoint_is_overridden ? $this->t('This value is set in code and cannot be overridden through this form.') : '',
      '#required' => TRUE,
      '#attributes' => [
        'disabled' => $endpoint_is_overridden ? 'disabled' : FALSE,
      ],
    ];

    $form['api_settings']['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#default_value' => $config->get('debug_mode') ? $config->get('debug_mode') : 0,
      '#description' => $this->t('Enabling this will turn on additional logging for this Eloqua integration. <em>Should be turned off when active debugging is not in progress.</em>'),
    ];

    $form['azure_testing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test connection details'),
      '#description' => $this->t('Allows testing the connection to Azure and provides a list of queues if successful.'),
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    ];

    $form['azure_testing']['test_connection'] = [
      '#type' => 'button',
      '#value' => 'Test connection',
      '#prefix' => '<div id="result"></div>',
      '#ajax' => [
        'callback' => [$this, 'testConnection'],
        'effect' => 'fade',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    return $form;
  }

  /**
   * Callback function for testing the Azure connection.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response to provide back to the form.
   */
  public function testConnection(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $serviceBus = $this->azureApi->getServiceBus();
    if ($serviceBus) {

      // Get a list of queues.
      try {
        $rows = [];
        $queues = $serviceBus->listQueues();
        if (!empty($queues)) {
          /** @var \WindowsAzure\ServiceBus\Models\ListQueuesResult $queue */
          foreach ($queues->getQueueInfos() as $queue) {
            /** @var \WindowsAzure\ServiceBus\Models\QueueInfo $queue */
            $rows[] = [$queue->getTitle()];
          }
        }
        $table = [
          '#type' => 'table',
          '#header' => ['Queue name'],
          '#rows' => $rows,
          '#empty' => $this->t('There are no queues yet.'),
        ];
        $message = \Drupal::service('renderer')->render($table);
      }
      catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        $message = "$code: $error_message";
      }
    }
    $ajax_response->addCommand(new HtmlCommand('#result', $message));
    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update and save the configuration settings.
    $config = $this->config('azure_service_bus_connector.settings');
    $config->set('shared_access_key_name', $form_state->getValue('shared_access_key_name'));
    $config->set('shared_access_key', $form_state->getValue('shared_access_key'));
    $config->set('endpoint', $form_state->getValue('endpoint'));
    $config->set('debug_mode', $form_state->getValue('debug_mode'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return the configuration names.
   */
  protected function getEditableConfigNames() {
    return [
      'azure_service_bus_connector.settings',
    ];
  }

}
