<?php

namespace Drupal\twilio\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\twilio\Controller\TwilioController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\twilio\Services\Command;

/**
 * Form to send test SMS messages.
 */
class TwilioAdminTestForm extends FormBase {

  /**
   * Injected Twilio service Command class.
   *
   * @var Command
   */
  private $command;

  /**
   * {@inheritdoc}
   */
  public function __construct(Command $command) {
    $this->command = $command;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $command = $container->get('twilio.command');
    return new static($command);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twilio_admin_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country code'),
      '#options' => TwilioController::countryDialCodes(FALSE),
    ];
    $form['number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Phone Number'),
      '#description' => $this->t('The number to send your message to. Include all numbers except the country code'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#description' => $this->t("The body of your SMS message."),
    ];
    $form['mediaUrl'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Media URL (MMS)'),
      '#description' => $this->t('A publicly accessible URL to a media file such as a png, jpg, gif, etc. (e.g. http://something.com/photo.jpg)'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send SMS'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue(['number']);
    if (!is_numeric($value)) {
      $form_state->setErrorByName('number', $this->t('You must enter a phone number'));
    }
    if ($form_state->getValue(['mediaUrl']) && !UrlHelper::isValid($form_state->getValue(['mediaUrl']), TRUE)) {
      $form_state->setErrorByName('mediaUrl', $this->t('Media URL must be a valid, absolute URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = [];
    $message['body'] = $form_state->getValue('message') ? $form_state->getValue('message') : '';
    if ($mediaUrl = $form_state->getValue('mediaUrl')) {
      $message['mediaUrl'] = $mediaUrl;
    }
    $this->command->messageSend($form_state->getValue(['number']), $message);
    drupal_set_message($this->t('Attempted to send SMS message. Check your receiving device.'));
  }

}
