<?php

namespace Drupal\accountkit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\accountkit\AccountKitManager;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class SMSLoginForm.
 */
class SMSLoginForm extends FormBase {


  /**
   * Constructor.
   *
   * @param \Drupal\accountkit\AccountKitManager $accountkit_manager
   *
   * @internal param \Drupal\Core\Config\ConfigFactoryInterface $config_factory The factory for configuration objects.*   The factory for configuration objects.
   * @internal param \Drupal\Core\Routing\RequestContext $request_context Holds information about the current request.*   Holds information about the current request.
   */
  public function __construct(AccountKitManager $accountkit_manager) {
    $this->accountKitManager = $accountkit_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('accountkit.accountkit_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['country_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country code'),
      '#description' => $this->t('Please input your country code.'),
      '#maxlength' => 64,
      '#size' => 5,
    ];
    $form['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone number'),
      '#description' => $this->t('Please input your phone number.'),
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => ['id' => 'sms-login-submit'],
    ];

    return $form + $this->accountKitManager->getAdditionalFormDetails();
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
