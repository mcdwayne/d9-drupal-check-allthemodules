<?php

namespace Drupal\commerce_cib\PluginForm;

use Drupal\commerce_cib\Event\CibEvents;
use Drupal\commerce_cib\Event\FailedInitialization;
use Drupal\commerce_cib\Event\PreQuery10;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

class CibForm extends PaymentOffsiteForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $client = \Drupal::httpClient();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    // The core return path has a csrf token but CIB does not support url query
    // parameters in the return url. So we use a variation of the core return
    // route without the csrf token.
    $form['#return_url'] = Url::fromRoute('commerce_cib.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ])->setOptions(['absolute' => TRUE])->toString();

    $amo = $payment->getAmount()->getNumber();
    /** @var \Drupal\commerce_cib\Plugin\Commerce\PaymentGateway\Cib $plugin */
    $plugin = $this->plugin;

    // Last chance to prevent redirection to CIB.
    // Useful to check an out-of-stock event.
    $event = new PreQuery10($payment);
    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(CibEvents::PRE_QUERY_10, $event);

    $des = $plugin->getMode() === 'test' ? $plugin->getConfiguration()['des-test'] : $plugin->getConfiguration()['des-live'];
    /** @var \Drupal\commerce_cib\Encryption $encryption */
    $encryption = \Drupal::service('commerce_cib.encryption');
    $encryption->setKeyfile($des);

    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $entity_type_manager->getStorage('commerce_payment');
    $query = $payment_storage->getQuery();
    $payment_ids = $query->condition('type', 'payment_cib')
      ->condition('order_id', $payment->getOrderId())
      ->execute();
    if (empty($payment_ids)) {
      $payment_id = $payment_storage->getQuery()
        ->condition('type', 'payment_cib')
        ->sort('payment_cib_eki_user', 'DESC')
        ->range(0, 1)
        ->execute();
      if (empty($payment_id)) {
        $no = 1;
      }
      else {
        $payment_id = reset($payment_id);
        $last_payment = Payment::load($payment_id);
        $last_eki_user = $last_payment->get('payment_cib_eki_user')->value;
        $no = substr($last_eki_user, 3) + 1;
      }
      $eki_user = substr($plugin->getConfiguration()['pid'], 0, 3) . str_pad($no, 8, '0', STR_PAD_LEFT);
    }
    else {
      $last_payment = Payment::load(reset($payment_ids));
      $eki_user = $last_payment->get('payment_cib_eki_user')->value;
    }

    $initialized = FALSE;
    $tries = 0;

    while (!$initialized && $tries++ < 10) {
      $trid = date("is", time());
      for ($i = 1; $i <= 12; $i++) {
        $trid .= rand(1, 9);
      }

      // Let's initialize the transaction
      $ts = date("YmdHis", time());
      $msgt10 = [
        'CRYPTO' => 1,
        'MSGT' => 10,
        'TRID' => $trid,
        'UID' => $eki_user,
        'LANG' => 'HU',
        'TS' => $ts,
        'AUTH' => 0,
        'AMO' => $amo,
        'CUR' => $plugin->getConfiguration()['cur'],
        'URL' => $form['#return_url'],
        'PID' => $plugin->getConfiguration()['pid'],
      ];
      $msgt11 = $plugin->sendRequest($msgt10);
      if ($msgt11['RC'] == '00') {
          $initialized = TRUE;
      }
    }

    if ($initialized) {
      $payment->setRemoteId($trid);
      $payment->payment_cib_eki_user = $eki_user;
      $payment->payment_cib_start = \Drupal::time()->getRequestTime();
      $payment->payment_cib_end = 0;
      $payment->save();
      $msgt20 = [
        'MSGT' => 20,
        'PID' => $plugin->getConfiguration()['pid'],
        'TRID' => $trid,
      ];
      $url = $plugin->createUrl($msgt20, 'customer');
      $vars = [
        '@pid' => $plugin->getConfiguration()['pid'],
        '@trid' => $trid,
      ];
      /** @var \Drupal\Core\Logger\LoggerChannel $logger */
      $logger = \Drupal::service('commerce_cib.logger');
      $logger->notice($this->t('CIB redirects with MSGT 20, PID @pid and TRID @trid.', $vars));
      return $this->buildRedirectForm($form, $form_state, $url, [], static::REDIRECT_GET);
    }
    else {
      $event = new FailedInitialization($payment);
      $event_dispatcher->dispatch(CibEvents::FAILED_INITIALIZATION, $event);
    }
  }

}
