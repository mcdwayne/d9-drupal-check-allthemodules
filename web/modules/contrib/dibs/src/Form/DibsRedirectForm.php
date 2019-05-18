<?php

namespace Drupal\dibs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DibsRedirectForm.
 *
 * @package Drupal\dibs\Form
 */
class DibsRedirectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dibs_redirect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dibs.settings');

    $transaction = $form_state->getBuildInfo()['args'][0]['transaction'];
    // @todo handle split payment
    $form['amount'] = [
      '#type' => 'hidden',
      '#value' => $transaction->amount->value,
    ];
    // @todo investigate do we need callbacks and url settings at all.
    $form['accepturl'] = [
      '#type' => 'hidden',
      '#value' => $this->getUrlGenerator()->generateFromRoute('dibs.dibs_pages_controller_accept', ['transaction_hash' => $transaction->hash->value], ['absolute' => TRUE])
    ];
    $form['cancelurl'] = [
      '#type' => 'hidden',
      '#value' => $this->getUrlGenerator()->generateFromRoute('dibs.dibs_pages_controller_cancel', ['transaction_hash' => $transaction->hash->value], ['absolute' => TRUE])
    ];
    $form['callbackurl'] = [
      '#type' => 'hidden',
      '#value' => $this->getUrlGenerator()->generateFromRoute('dibs.dibs_pages_controller_callback', ['transaction_hash' => $transaction->hash->value], ['absolute' => TRUE]),
    ];
    $form['windowtype'] = [
      '#type' => 'hidden',
      '#value' => $config->get('general.type'),
    ];
    $form['currency'] = [
      '#type' => 'hidden',
      '#value' => $transaction->currency->value ?: $config->get('general.currency'),
    ];
    $form['merchant'] = [
      '#type' => 'hidden',
      '#value' => $config->get('general.merchant_id'),
    ];
    $form['orderid'] = [
      '#type' => 'hidden',
      '#value' => $transaction->order_id->value,
    ];

    if ($config->get('general.test_mode')) {
      $form['test'] = [
        '#type' => 'hidden',
        '#value' => 1,
      ];
    }
    if ($config->get('general.account')) {
      $form['account'] = [
        '#type' => 'hidden',
        '#value' => $config->get('general.account'),
      ];
    }
    if ($config->get('flexwindow.decorator')) {
      $form['decorator'] = [
        '#type' => 'hidden',
        '#value' => $config->get('flexwindow.decorator'),
      ];
    }
    if ($config->get('flexwindow.voucher')) {
      $form['voucher'] = [
        '#type' => 'hidden',
        '#value' => 'yes',
      ];
    }
    $form['lang'] = [
      '#type' => 'hidden',
      '#value' => $transaction->lang->value ?: $config->get('general.lang'),
    ];
    if ($config->get('mobilewindow.payment_types')) {
      $form['paytype'] = [
        '#type' => 'hidden',
        '#value' => implode(',', $config->get('mobilewindow.payment_types')),
      ];
    }
    if ($config->get('advanced.calculate_fee')) {
      $form['calcfee'] = [
        '#type' => 'hidden',
        '#value' => 1,
      ];
    }
    if ($config->get('advanced.capture_now')) {
      $form['capturenow'] = [
        '#type' => 'hidden',
        '#value' => 1,
      ];
    }
    if ($config->get('advanced.unique_order_id')) {
      $form['uniqueoid'] = [
        '#type' => 'hidden',
        '#value' => $config->get('advanced.unique_order_id'),
      ];
    }

    // @todo move it to separate classes or even plugins.
    if ($config->get('general.type') == 'pay') {
      $form['#action'] = 'https://payment.architrade.com/payment/start.pml';

      // We are forcing the accept-charset to be ISO-8859-1, else will the order and delivery
      // info sent to DIBS be shown with wrong characters in the payment window and in their
      // administration system.
      $form['#attributes']['accept-charset'] = 'ISO-8859-1';

      $form['color'] = [
        '#type' => 'hidden',
        '#value' => $config->get('paymentwindow.color'),
      ];
    }
    elseif ($config->get('general.type') == 'flex') {
      $form['#action'] = 'https://payment.architrade.com/paymentweb/start.action';
      $form['color'] = [
        '#type' => 'hidden',
        '#value' => $config->get('flexwindow.color'),
      ];

      $form['decorator'] = [
        '#type' => 'hidden',
        '#value' => $config->get('flexwindow.decorator') != 'custom' ? $config->get('flexwindow.decorator') : '',
      ];

      if ($config->get('flexwindow.voucher')) {
        $form['voucher'] = [
          '#type' => 'hidden',
          '#value' => 'yes',
        ];
      }
    }
    elseif ($config->get('general.type') == 'mobile') {
      $form['#action'] = 'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';
      // Set the params that have a new key.
      $form['acceptreturnurl'] = $form['accepturl'];
      unset($form['accepturl']);
      $form['cancelreturnurl'] = $form['cancelurl'];
      unset($form['cancelurl']);
      if (!empty($form['calcfee'])) {
        $form['addfee'] = $form['calcfee'];
        unset($form['calcfee']);
      }
      $form['language'] = $form['lang'];
      unset($form['lang']);
      // Language has changed format a bit, fix this.
      if ($form['language']['#value'] == 'da') {
        $form['language']['#value'] .= '_DK';
      }
      if ($form['language']['#value'] == 'en') {
        $form['language']['#value'] .= '_UK';
      }
      if ($form['language']['#value'] == 'sv') {
        $form['language']['#value'] .= '_SE';
      }
      if ($form['language']['#value'] == 'nb') {
        $form['language']['#value'] .= '_NO';
      }
      if ($form['language']['#value'] == 'fi') {
        $form['language']['#value'] .= '_FIN';
      }

      // Unique order ids is a bit different.
      unset($form['uniqueoid']);
      $form['uniqueid'] = array(
        '#type' => 'hidden',
        '#value' => $form['orderid']['#value']
      );

      // MD5 key is never used in mobile payment window
      unset($form['md5key']);

      // @todo migrate HMAC.
    }

    // @todo implement md5 or hmac.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do because we redirect form to DIBS.
  }
}
