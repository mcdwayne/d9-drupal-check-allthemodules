<?php

/**
 * @file
 * Contains \Drupal\uc_everyday\Form\EverydaySettingsForm.
 */

namespace Drupal\uc_everyday\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;

/**
 * Form for recording a received check and expected clearance date.
 */
class EverydayCheckoutForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_everyday_redirect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $everyday_config = $this->config('uc_everyday.settings');
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
    if ($everyday_config->get('uc_everyday_mode') == '1') {
      $everyday_sellerid = $everyday_config->get('uc_everyday_test_sid');
      $everyday_secretkey = $everyday_config->get('uc_everyday_test_secret_key');
      $everyday_secretkey_version = $everyday_config->get('uc_everyday_test_secret_key_version');
      drupal_set_message(t('Everyday Web Payment module is in test mode'));
    }
    else {
      $everyday_sellerid = $everyday_config->get('uc_everyday_sid');
      $everyday_secretkey = $everyday_config->get('uc_everyday_secret_key');
      $everyday_secretkey_version = $everyday_config->get('uc_everyday_secret_key_version');
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
      '#value' => $this->t('Submit evv order'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }
}
