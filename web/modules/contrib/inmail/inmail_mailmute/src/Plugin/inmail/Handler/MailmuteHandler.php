<?php

namespace Drupal\inmail_mailmute\Plugin\inmail\Handler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\inmail_mailmute\Plugin\mailmute\SendState\CountingBounces;
use Drupal\inmail_mailmute\Plugin\mailmute\SendState\PersistentSend;
use Drupal\mailmute\SendStateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reacts to bounce messages by managing the send state of the bouncing address.
 *
 * @ingroup mailmute
 *
 * @Handler(
 *   id = "mailmute",
 *   label = @Translation("Mailmute"),
 *   description = @Translation("Reacts to bounce messages by managing the send state of the bouncing address.")
 * )
 */
class MailmuteHandler extends HandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The Mailmute send state manager.
   *
   * @var \Drupal\mailmute\SendStateManagerInterface
   */
  protected $sendstateManager;

  /**
   * Constructs a new MailmuteHandler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SendStateManagerInterface $sendstate_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sendstateManager = $sendstate_manager;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.sendstate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return array(
      '#type' => 'item',
      '#markup' => $this->t('<p>Soft bounces trigger a transition to the <em>Counting bounces</em> state. After a number of bounces, the state transitions to <em>Temporarily unreachable</em>.</p> <p>Hard bounces cause the send state to transition to <em>Invalid address</em>.</p>'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\inmail\DefaultAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult();
    $bounce_data = $result->ensureContext('bounce', 'inmail_bounce');

    // Only handle bounces.
    if (!$bounce_data->isBounce()) {
      return;
    }

    $status_code = $bounce_data->getStatusCode();
    $log_context = ['%code' => $status_code->getCode()];

    // Only handle bounces with an identifiable recipient.
    if (!$address = $bounce_data->getRecipient()) {
      // @todo Log the message body or place it in a moderation queue: https://www.drupal.org/node/2379879
      $processor_result->log('MailmuteHandler', 'Bounce with status %code received but no recipient identified.', $log_context);
      return;
    }

    $log_context += ['%address' => $address];

    // Only handle bounces with an identifiable recipient that we care about.
    if (!$this->sendstateManager->isManaged($address)) {
      $processor_result->log('MailmuteHandler', 'Bounce with status %code received but recipient %address is not managed here.', $log_context);
      return;
    }

    $state = $this->sendstateManager->getState($address);

    // Block transition if current state is "Persistent send".
    if ($state instanceof PersistentSend) {
      $processor_result->log('MailmuteHandler', 'Send state not transitioned for %address because state was %old_state', $log_context + ['%old_state' => 'persistent_send']);
      return;
    }

    $state_configuration = array(
      'code' => $bounce_data->getStatusCode(),
      'reason' => $bounce_data->getReason(),
      'date' => $message->getReceivedDate(),
    );

    // In the case of a "hard bounce", set the send state to a muting state.
    if ($status_code->isPermanentFailure()) {
      $this->sendstateManager->transition($address, 'inmail_invalid_address', $state_configuration);
      $processor_result->log('MailmuteHandler', 'Bounce with status %code triggered send state transition of %address to %new_state', $log_context + ['%new_state' => 'inmail_invalid_address']);
      return;
    }

    // Not success and not hard bounce, so status must indicate a "soft bounce".
    // If already counting bounces, add 1.
    if ($state instanceof CountingBounces) {
      $state->increment();

      // If the threshold is reached, start muting.
      if ($state->getThreshold() && $state->getUnprocessedCount() >= $state->getThreshold()) {
        $this->sendstateManager->transition($address, 'inmail_temporarily_unreachable', $state_configuration);
        $processor_result->log('MailmuteHandler', 'Bounce with status %code triggered send state transition of %address to %new_state', $log_context + ['%new_state' => 'inmail_temporarily_unreachable']);
      }
      else {
        $this->sendstateManager->save($address);
        $processor_result->log('MailmuteHandler', 'Bounce with status %code triggered soft bounce count increment for %address', $log_context);
      }
      return;
    }

    // If still sending, start counting bounces.
    if (!$state->isMute()) {
      $this->sendstateManager->transition($address, 'inmail_counting', array('count' => 1, 'threshold' => $this->configuration['soft_threshold']) + $state_configuration);
      $processor_result->log('MailmuteHandler', 'Bounce with status %code triggered send state transition of %address to %new_state', $log_context + ['%new_state' => 'inmail_counting']);
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'soft_threshold' => 5,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['soft_threshold'] = array(
      '#title' => 'Soft bounce tolerance',
      '#type' => 'number',
      '#default_value' => $this->configuration['soft_threshold'],
      '#description' => $this->t('This defines how many soft bounces may be received from an address before its state is transitioned to "Temporarily unreachable".'),
      '#description_display' => 'after',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['soft_threshold'] = $form_state->getValue('soft_threshold');
  }

}
