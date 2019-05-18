<?php

namespace Drupal\messagebird\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send a SMS message.
 *
 * @Action(
 *   id = "messagebird_send_message",
 *   label = @Translation("Send a SMS message"),
 *   type = "system"
 * )
 */
class SendMessageAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a MessageAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('token'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (empty($this->configuration['node'])) {
      $this->configuration['node'] = $entity;
    }

    $body = $this->token->replace($this->configuration['body'], $this->configuration);
    $recipients = $this->configuration['recipients'];
    $originator = $this->configuration['originator'];

    $service = $this->container->get('messagebird.service');
    $service->sendMessage($body, $recipients, $originator);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'originator' => '',
      'recipient' => '',
      'body' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['originator'] = array(
      '#type' => 'textfield',
      '#title' => t('Originator'),
      '#default_value' => $this->configuration['originator'],
    );

    $form['recipient'] = array(
      '#type' => 'textfield',
      '#title' => t('Recipient'),
      '#default_value' => $this->configuration['recipient'],
      '#required' => TRUE,
    );

    $form['body'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#default_value' => $this->configuration['body'],
      '#required' => TRUE,
      '#rows' => '8',
      '#description' => t('The message to be send. You may include placeholders like [node:title], [user:account-name], [user:display-name] and [comment:body] to represent data that will be different each time message is sent. Not all placeholders will be available in all contexts.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['originator'] = $form_state->getValue('originator');
    $this->configuration['recipient'] = $form_state->getValue('recipient');
    $this->configuration['body'] = $form_state->getValue('body');
    unset($this->configuration['node']);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
