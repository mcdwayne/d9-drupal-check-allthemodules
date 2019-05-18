<?php

namespace Drupal\cg_payment\Form;

use Drupal\cg_payment\Manager\RequestManager;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Credit Guard configuration settings for this site.
 */
class CreditGuard extends ConfigFormBase {

  /**
   * The request manager object.
   *
   * @var \Drupal\cg_payment\Manager\RequestManager
   */
  protected $cgRequestManager;

  /**
   * The messenger object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cg_payment_credit_cuard';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cg_payment.settings'];
  }

  /**
   * Constructs a CreditGuard object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\cg_payment\Manager\RequestManager $cg_request_manager
   *   The RequestManager object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestManager $cg_request_manager, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->cgRequestManager = $cg_request_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cg_payment.cg_request_manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['endpoint_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint URL'),
      '#default_value' => $this->config('cg_payment.settings')->get('endpoint_url'),
      '#description' => $this->t("Creditguard's endpoint URL, e.g. https://xxx.creditguard.co.il/xpo/services/Relay?wsdl<br>
        Note that you must include the ?wsdl suffix for that to work"),
    ];

    $form['user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#default_value' => $this->config('cg_payment.settings')->get('user_name'),
    ];

    $form['cg_test'] = [
      '#title' => $this->t('CreditGuard integration test'),
      '#type' => 'fieldset',
    ];

    $form['cg_test']['help'] = [
      '#markup' => $this->t('The test will be performed with the following terminal ID and mid, and will <u>ignore any changes</u> performed on the configuration form.'),
    ];

    $form['cg_test']['terminal_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Test terminal ID'),
      '#description' => $this->t('This is only being used for the test request, not for the entire integration'),
      '#title' => $this->t('Terminal ID'),
    ];

    $form['cg_test']['mid'] = [
      '#type' => 'number',
      '#title' => $this->t('Test mid'),
      '#description' => $this->t('This is only being used for the test request, not for the entire integration'),
      '#title' => $this->t('mid'),
    ];

    $form['cg_test']['create_test_transaction'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test integration'),
      '#description' => $this->t('Initiate test transction to CG (will only create the URL)'),
      '#validate' => ['::testTransactionValidate'],
      '#submit' => ['::createTestTransaction'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('endpoint_url')) {
      $url = $form_state->getValue('endpoint_url');
      $valid = UrlHelper::isValid($url, TRUE);
      if (!$valid) {
        $form_state->setErrorByName($url, $this->t('The URL %url is not valid.', ['%url' => $url]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('cg_payment.settings')
      ->set('endpoint_url', $form_state->getValue('endpoint_url'))
      ->set('user_name', $form_state->getValue('user_name'))
      ->save();
  }

  /**
   * Validate the test data.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function testTransactionValidate(array &$form, FormStateInterface $form_state) {
    $terminal = $form_state->getValue('terminal_id');
    $mid = $form_state->getValue('mid');

    if (empty($terminal)) {
      $form_state->setError($form['cg_test']['terminal_id'], $this->t('Terminal ID is missing'));
    }

    if (empty($mid)) {
      $form_state->setError($form['cg_test']['mid'], $this->t('mid is missing'));
    }
  }

  /**
   * Callback for the test integration functionality.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function createTestTransaction(array &$form, FormStateInterface $form_state) {
    $terminal = $form_state->getValue('terminal_id');
    $mid = $form_state->getValue('mid');

    try {
      $url = $this->cgRequestManager->requestPaymentFormUrl($terminal,
        $mid,
        '1',
        'cg_payment@drupal.org',
        'Test transction by cg_payment');
      if (!empty($url)) {
        $this->messenger->addStatus($this->t('Success'));
      }
      else {
        $this->messenger->addError($this->t('Error'));
      }

    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('Error getting CG payment URL, message: %message', [
        '%message' => $e->getMessage(),
      ]));
    }
  }

}
