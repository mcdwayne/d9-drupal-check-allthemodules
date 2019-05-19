<?php

/**
 * @file
 * Contains \Drupal\uc_everyday\Plugin\Ubercart\PaymentMethod\Everyday.
 */

namespace Drupal\uc_everyday\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_store\Encryption;

/**
 * Defines the check payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "everyday",
 *   name = @Translation("Everyday"),
 *   redirect = "\Drupal\uc_everyday\Form\EverydayCheckoutForm",
 *   title = @Translation("Everyday"),
 *   checkout = TRUE,
 *   no_gateway = FALSE,
 *   settings_form = "Drupal\uc_everyday\Form\EverydaySettingsForm",
 *   weight = 2,
 * )
 */
class Everyday extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['#attached']['library'][] = 'uc_everyday/everyday.styles';
    $build['label'] = array(
      '#plain_text' => $label,
      '#suffix' => '<br />',
    );
    $build['image'] = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'uc_everyday') . '/images/everyday_logo.jpg',
      '#alt' => $this->t('Everyday'),
      '#attributes' => array('class' => array('uc-everyday-icons')),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'uc_everyday_mode' => 1,
      'uc_everyday_sid' => '',
      'uc_everyday_secret_key' => '',
      'uc_everyday_secret_key_version' => 1,
      'uc_everyday_test_sid' => 5442,
      'uc_everyday_test_secret_key' => 'NGNhODg0ZjA0NjYxNzllZmQxNWRhZA',
      'uc_everyday_test_secret_key_version' => '1',
      'uc_everyday_method_title_icons' => TRUE,
      'uc_everyday_method_payment_msg' => 'Continue with checkout. You are directed to Everyday secure server.',
      'uc_everyday_checkout_button' => 'Proceed to Everyday',
      'uc_everyday_reference_prefix' => 1000,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['uc_everyday_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#description' => $this->t('In testing mode you are able to verify Everyday Online Payment service with fake orders'),
      '#options' => array(
        '1' => $this->t('Testing'),
        '0' => $this->t('Production'),
      ),
      '#default_value' => $this->configuration['uc_everyday_mode'],
    );

    $form['prod_mode'] = array(
      '#title' => $this->t('Production mode settings'),
      '#type' => 'fieldset',
      '#description' => $this->t('This fieldset contains fields for production mode configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['prod_mode']['uc_everyday_sid'] = array(
      '#type' => 'textfield',
      '#title' => t('Everyday customer ID'),
      '#description' => t('Your customer ID in Everyday service.'),
      '#default_value' => $this->configuration['uc_everyday_sid'],
      '#size' => 20,
    );
    $form['prod_mode']['uc_everyday_secret_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Everyday secret key for order verification'),
      '#description' => $this->t('The secret key used for order vefification.'),
      '#default_value' => $this->configuration['uc_everyday_secret_key'],
      '#size' => 50,
    );
    $form['prod_mode']['uc_everyday_secret_key_version'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Everyday secret key version'),
      '#description' => $this->t('The secret key version.'),
      '#default_value' => $this->configuration['uc_everyday_secret_key_version'],
      '#size' => 3,
    );
    $form['test_mode'] = array(
      '#title' => t('Test Mode settings'),
      '#type' => 'fieldset',
      '#description' => $this->t('This fieldset contains fields for test mode configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['test_mode']['uc_everyday_test_sid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Everyday customer ID (test mode)'),
      '#description' => $this->t('Your customer ID in Everyday service while in test mode.'),
      '#default_value' => $this->configuration['uc_everyday_test_sid'],
      '#size' => 20,
    );
    $form['test_mode']['uc_everyday_test_secret_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Everyday secret key for order verification (test mode)'),
      '#description' => $this->t('The secret key used for order vefification in test mode.'),
      '#default_value' => $this->configuration['uc_everyday_test_secret_key'],
      '#size' => 50,
    );
    $form['test_mode']['uc_everyday_test_secret_key_version'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Everyday secret key version (test mode)'),
      '#description' => $this->t('The secret key version in test mode.'),
      '#default_value' => $this->configuration['uc_everyday_test_secret_key_version'],
      '#size' => 3,
    );
    $form['uc_everyday_method_title_icons'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show icon beside the payment method title.'),
      '#default_value' => $this->configuration['uc_everyday_method_title_icons'],
    );
    $form['uc_everyday_method_payment_msg'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Payment details message'),
      '#description' => $this->t('Payment details message when Everyday payment method selected'),
      '#default_value' => $this->configuration['uc_everyday_method_payment_msg'],
    );
    $form['uc_everyday_checkout_button'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order review submit button text'),
      '#description' => $this->t('Provide Everyday Online Payment specific text for the submit button on the order review page.'),
      '#default_value' => $this->configuration['uc_everyday_checkout_button'],
    );
    $form['uc_everyday_reference_prefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reference number prefix'),
      '#description' => $this->t("Reference number prefix to be used while providing order's reference number. Reference number's minimum length is 3 digits and check number. Reference number cannot start with 0."),
      '#default_value' => $this->configuration['uc_everyday_reference_prefix'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['uc_everyday_mode'] = $form_state->getValue('uc_everyday_mode');
    $this->configuration['uc_everyday_sid'] = $form_state->getValue('uc_everyday_sid');
    $this->configuration['uc_everyday_secret_key'] = $form_state->getValue('uc_everyday_secret_key');
    $this->configuration['uc_everyday_secret_key_version'] = $form_state->getValue('uc_everyday_secret_key_version');
    $this->configuration['uc_everyday_test_sid'] = $form_state->getValue('uc_everyday_test_sid');
    $this->configuration['uc_everyday_test_secret_key'] = $form_state->getValue('uc_everyday_test_secret_key');
    $this->configuration['uc_everyday_test_secret_key_version'] = $form_state->getValue('uc_everyday_test_secret_key_version');
    $this->configuration['uc_everyday_method_title_icons'] = $form_state->getValue('uc_everyday_method_title_icons');
    $this->configuration['uc_everyday_method_payment_msg'] = $form_state->getValue('uc_everyday_method_payment_msg');
    $this->configuration['uc_everyday_checkout_button'] = $form_state->getValue('uc_everyday_checkout_button');
    $this->configuration['uc_everyday_reference_prefix'] = $form_state->getValue('uc_everyday_reference_prefix');
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return array(
      '#markup' => t('Continue with checkout to complete your order.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    if (NULL != $form_state->getValue(['panes', 'payment', 'details', 'pay_method'])) {
      $_SESSION['pay_method'] = $form_state->getValue(['panes', 'payment', 'details', 'pay_method']);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {

  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $country = \Drupal::service('country_manager')->getCountry($order->getAddress('billing')->country);

    $context = array(
      'revision' => 'formatted-original',
      'type' => 'order_total',
      'subject' => array(
        'order' => $order,
      ),
    );
    $options = array(
      'sign' => FALSE,
      'dec' => ',',
      'thou' => FALSE,
    );

    // Set merchant id and secret key (online / test usage).
    if ($this->configuration['uc_everyday_mode'] == '1') {
      $everyday_sellerid = $this->configuration['uc_everyday_test_sid'];
      $everyday_secretkey = $this->configuration['uc_everyday_test_secret_key'];
      $everyday_secretkey_version = $this->configuration['uc_everyday_test_secret_key_version'];
      drupal_set_message(t('Everyday Web Payment module is in test mode'));
    }
    else {
      $everyday_sellerid = $this->configuration['uc_everyday_sid'];
      $everyday_secretkey = $this->configuration['uc_everyday_secret_key'];
      $everyday_secretkey_version = $this->configuration['uc_everyday_secret_key_version'];
    }

    // Timezone.
    date_default_timezone_set('Europe/Helsinki');

    // Data.
    $data = array(
      'OPR_VERSION' => '0004',
      'OPR_STAMP' => date("YmdHisu"),
      'OPR_RCV_ID' => $everyday_sellerid,
      'OPR_LANGUAGE' => 'fi_FI',
      'OPR_AMOUNT' => uc_currency_format($order->getTotal(), FALSE, FALSE, ','),
      'OPR_CUR' => 'EUR',
      'OPR_REF' => _uc_everyday_reference($order->id()),
      'OPR_MSG' => '',
      'OPR_RETURN' => Url::fromRoute('uc_everyday.complete', ['cart_id' => \Drupal::service('uc_cart.manager')->get()->getId(), 'order_id' => $order->id()], ['absolute' => TRUE])->toString(),
      'OPR_REJECT' => Url::fromRoute('uc_everyday.reject', ['cart_id' => \Drupal::service('uc_cart.manager')->get()->getId()], ['absolute' => TRUE])->toString(),
      'OPR_CANCEL' => Url::fromRoute('uc_everyday.cancel', ['cart_id' => \Drupal::service('uc_cart.manager')->get()->getId()], ['absolute' => TRUE])->toString(),
      'OPR_DATE' => '',
      'OPR_TYPE' => '',
    );

    // Calculate hash code.
    $hash_fields['secret_key'] = $everyday_secretkey;
    $hash_fields['version'] = $data['OPR_VERSION'];
    $hash_fields['stamp'] = $data['OPR_STAMP'];
    $hash_fields['sellerid'] = $data['OPR_RCV_ID'];
    $hash_fields['amount'] = $data['OPR_AMOUNT'];
    $hash_fields['reference'] = $data['OPR_REF'];
    $hash_fields['date'] = $data['OPR_DATE'];
    $hash_fields['currency'] = $data['OPR_CUR'];

    // Hash code.
    $data['OPR_MAC'] = strtoupper(md5(implode('&', $hash_fields) . '&'));
    $data['OPR_KEYVERS'] = $everyday_secretkey_version;

    // Form.
    $form['#action'] = _uc_everyday_post_url();

    foreach ($data as $name => $value) {
      $form[$name] = array('#type' => 'hidden', '#value' => $value);
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->configuration['uc_everyday_checkout_button'],
    );

    return $form;
  }
}
