<?php

namespace Drupal\node_paypal_payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node_paypal_payment\NPPObject;

/**
 * Handles the "redirect" route.
 */
class NPPPayment extends ControllerBase {
  /**
   * Returns list of payments for a user.
   *
   * @return renderd
   *   content
   */
  public function paymentList() {
    // Table header.
    $header = [
      'id' => $this->t('Id'),
      'title' => $this->t('Title'),
      'txt_id' => $this->t('Trasaction Id'),
      'amount' => $this->t('Amount'),
      'status' => $this->t('Payment Status'),
      'date' => $this->t('Payment Date'),
    ];

    $rows = [];

    $uid = $this->currentUser()->id();

    foreach (NPPObject::getAll($uid) as $payment) {
      $entity_id = $payment->entity_id;
      $entity = $this->entityTypeManager()->getStorage('node')->load($entity_id);
      $url = new Url('entity.node.canonical', ['node' => $entity_id]);
      $title = $entity->getTitle();
      // Row with attributes on the row and some of its cells.
      $rows[] = [
        'data' => [
          $entity_id,
          $this->l($title, $url),
          $payment->txn_id,
          $payment->amount . ' ' . $payment->currency,
          $payment->status,
          ($payment->timestamp) ? \Drupal::service('date.formatter')->format($payment->timestamp) : '',
        ],
      ];
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'bd-contact-table',
      ],
    ];

    if (empty($rows)) {
      $no_record_message = '<div class="npp_no_record_message">No Payment you have done yet.</div>';
    }

    $element = [
      '#markup' => \Drupal::service('renderer')->render($table) . $no_record_message,
    ];

    return $element;
  }

  /**
   * Returns a payment of a user.
   *
   * @return renderd
   *   content
   */
  public function paymentAdminList() {

    // Table header.
    $header = [
      'id' => $this->t('Id'),
      'title' => $this->t('Title'),
      'txt_id' => $this->t('Trasaction Id'),
      'amount' => $this->t('Amount'),
      'status' => $this->t('Payment Status'),
      'date' => $this->t('Payment Date'),
    ];

    $rows = [];

    foreach (NPPObject::getAll() as $payment) {
      $entity_id = $payment->entity_id;
      $entity = $this->entityTypeManager()->getStorage('node')->load($entity_id);
      $url = new Url('entity.node.canonical', ['node' => $entity_id]);
      $title = $entity_id;
      if (is_object($entity)) {
        $title = $entity->getTitle();
      }
      // Row with attributes on the row and some of its cells.
      $rows[] = [
        'data' => [
          $entity_id,
          $this->l($title, $url),
          $payment->txn_id,
          $payment->amount . ' ' . $payment->currency,
          $payment->status,
          ($payment->timestamp) ? \Drupal::service('date.formatter')->format($payment->timestamp) : '',
        ],
      ];
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'bd-contact-table',
      ],
    ];

    $no_record_message = "";
    if (empty($rows)) {
      $no_record_message = '<div class="npp_no_record_message">No Record found.</div>';
    }

    $element = [
      '#markup' => \Drupal::service('renderer')->render($table) . $no_record_message,
    ];

    return $element;
  }

}
