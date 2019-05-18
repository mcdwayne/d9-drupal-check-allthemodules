<?php

namespace Drupal\slack\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\slack\Slack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SendTestMessageForm.
 *
 * @package Drupal\slack\Form
 */
class SendTestMessageForm extends FormBase {

  /**
   * Slack service.
   *
   * @var \Drupal\slack\Slack
   */
  protected $slackService;

  /**
   * {@inheritdoc}
   */
  public function __construct(Slack $slack) {
    $this->slackService = $slack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('slack.slack_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slack_send_test_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('slack.settings');
    $form['slack_test_channel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel or username'),
      '#default_value' => $config->get('slack_channel'),
    ];
    $form['slack_test_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->config('slack.settings')->get('slack_webhook_url'))) {
      $form_state->setRedirect('slack.admin_settings');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getRedirect())) {
      $config = $this->config('slack.settings');
      $channel = $form_state->getValue('slack_test_channel');
      $message = $form_state->getValue('slack_test_message');
      $username = $config->get('slack_username');
      $response = $this->slackService->sendMessage($message, $channel, $username);
      if ($response && RedirectResponse::HTTP_OK == $response->getStatusCode()) {
        $this->messenger()->addMessage($this->t('Message was successfully sent!'));
      }
      else {
        $this->messenger()->addWarning($this->t('Please check log messages for further details'));
      }
    }
  }

}
