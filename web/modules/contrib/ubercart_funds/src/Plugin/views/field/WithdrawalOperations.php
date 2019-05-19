<?php

namespace Drupal\ubercart_funds\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Custom;
use Drupal\Core\Url;

/**
 * A handler to provide withdrawal operations for admins.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_funds_withdrawal_operations")
 */
class WithdrawalOperations extends Custom {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Override options.
    $options['alter']['contains'] = [
      'alter_text' => FALSE,
    ];
    $options['hide_alter_empty'] = TRUE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->field_alias = 'operations';
  }

  /**
   * Return the operations for withdrawal operations.
   *
   * @param Drupal\views\ResultRow $values
   *   Views handler values to be modified.
   *
   * @return array
   *   Renderable dropbutton.
   */
  protected function renderWithdrawalOperations(ResultRow $values) {
    $request_id = $values->transaction_id;
    $status = $values->_entity->getStatus();
    $links = [];
    $args = ['request_id' => $request_id];

    if (\Drupal::currentUser()->hasPermission(['administer withdraw requests'])) {
      if ($status == 'Pending approval') {
        $links['approve'] = [
          'title' => t('Approve'),
          'url' => Url::fromRoute('uc_funds.admin.withdrawal_requests.approve', $args),
        ];
        $args = [
          'action' => 'decline',
          'request_id' => $request_id,
        ];
        $links['decline'] = [
          'title' => t('Decline'),
          'url' => Url::fromRoute('uc_funds.admin.withdrawal_requests.decline', $args),
        ];
      }
      else {
        return t('None');
      }
    }

    $dropbutton = [
      '#type' => 'dropbutton',
      '#links' => $links,
      '#attributes' => [
        'class' => [
          'escrow-link',
        ],
      ],
    ];

    return $dropbutton;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->renderWithdrawalOperations($values);
  }

}
