<?php

namespace Drupal\sms_vbo\Plugin\Action;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sms\Direction;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A sending SMS action
 *
 * @Action(
 *   id = "send_sms_action",
 *   label = @Translation("Send SMS"),
 *   type = "",
 *   confirm = TRUE,
 *   confirm_form_route_name = "sms_vbo.confirm",
 *   pass_context = TRUE,
 *   pass_view = TRUE
 * )
 */
class SendSmsAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, ContainerFactoryPluginInterface {

  protected $sms_provider;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, SmsProviderInterface $sms_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sms_provider = $sms_provider;
  }


  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sms.provider')
    );
  }

  /**
   * Checks object access.
   *
   * @param mixed $object
   *   The object to execute the action on.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof EntityInterface) {
      return $object->access('update', $account, $return_as_object);
    }
  }

  /**
   * Executes the plugin.
   */
  public function execute($entity = NULL) {
    $mobile_phone_number_field_name = $this->configuration['mobile_phone_number_field_name'];
    $current_batch_count = $this->context['sandbox']['current_batch'] - 1 ;
    $phone_number = $this->view->style_plugin->getFieldValue($current_batch_count, $mobile_phone_number_field_name);
    $message = $this->context['sms_message'];

    $processed_message = $this->processMessage($current_batch_count, $message);

    $sms = new SmsMessage();
    $sms->setMessage($processed_message)
      ->addRecipient($phone_number)
      ->setDirection(Direction::OUTGOING);
    $this->sms_provider->queue($sms);

    $this->context['sandbox']['current_batch']++;
  }

  /**
   * Build preconfigure action form elements.
   *
   * @param array $element
   *   Element of the views API form where configuration resides.
   * @param array $values
   *   Current values of the plugin pre-configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface object.
   * @return array The action configuration form element.
   * The action configuration form element.
   */
  public function buildPreConfigurationForm(array $element, array $values, FormStateInterface $form_state) {
    $display_id = $form_state->get('display_id');
    /** @var \Drupal\views_ui\ViewUI $view_ui */
    $view_ui = $form_state->get('view');
    $display_list = $view_ui->get('display');

    if (isset($display_list[$display_id]['display_options']['fields'])) {
      $fields = $display_list[$display_id]['display_options']['fields'];
    } else {
      $fields = $display_list['default']['display_options']['fields'];
    }

    $fields_options = [];
    foreach ($fields as $key => $field) {
      $fields_options[$key] = $field['label'];
    }

    $element['mobile_phone_number_field_name'] = [
      '#type' => 'select',
      '#title' => t('Field used for recipient mobile phone number'),
      '#description' => t('Choose a field from the current view will be used as recipient mobile phone number.'),
      '#options' => $fields_options,
      '#default_value' => isset($values['mobile_phone_number_field_name']) ? $values['mobile_phone_number_field_name'] : '',
      '#required' => TRUE,
    ];

    return $element;
  }

  protected function processMessage($current_row, $message) {
    // Get view display's relationship info to build the token data
    $relationships = $this->view->display_handler->getHandlers('relationship');
    $result_row = $this->view->result[$current_row];

    // Add view display's base entity type into token data
    $entity_type = $this->view->getBaseEntityType()->id();
    $token_data[$entity_type] = $result_row->_entity;

    // Build token data by relationship
    foreach ($relationships as $relationship) {
      if (isset($relationship->definition['entity type'])) {
        $entity_type = $relationship->definition['entity type'];
        $relationship_id = $relationship->options['id'];
        $token_data[$entity_type] = $result_row->_relationship_entities[$relationship_id];
      }
    }

    $result = \Drupal::token()->replace($message, $token_data);

    return $result;
  }

}
