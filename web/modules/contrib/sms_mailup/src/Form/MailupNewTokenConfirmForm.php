<?php

namespace Drupal\sms_mailup\Form;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms_mailup\MailupAuthenticationInterface;

/**
 * Confirm whether to void the current token and request a new token.
 */
class MailupNewTokenConfirmForm extends ConfirmFormBase {

  /**
   * The gateway entity.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $gateway;

  /**
   * The MailUp authentication service.
   *
   * @var \Drupal\sms_mailup\MailupAuthenticationInterface
   */
  protected $mailUpAuthentication;

  /**
   * Constructs a new MailupNewTokenConfirmForm object.
   *
   * @param \Drupal\sms_mailup\MailupAuthenticationInterface $mailUpAuthentication
   *   The MailUp service.
   */
  public function __construct(MailupAuthenticationInterface $mailUpAuthentication) {
    $this->mailUpAuthentication = $mailUpAuthentication;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sms_mailup.authentication')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_mailup_oauth_confirm_request';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to request a new token?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Existing authentication tokens will be deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('sms_mailup.gateway.oauth', ['sms_gateway' => $this->gateway->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SmsGatewayInterface $sms_gateway = NULL) {
    $this->gateway = $sms_gateway;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $gateway_id = $this->gateway->id();

    try {
      $provider = $this->mailUpAuthentication
        ->createOAuthProvider($gateway_id);
      $url = $provider->getAuthorizationUrl();

      // State string is used to prevent CSRF from the response.
      $state = $provider->getState();
      $this->mailUpAuthentication->setState($gateway_id, $state);

      $response = new TrustedRedirectResponse($url);
      $form_state->setResponse($response);
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Error: %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
  }

}
