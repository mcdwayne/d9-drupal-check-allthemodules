<?php

namespace Drupal\uc_gc_client\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Manage the information for the customer's user account.
 *
 * @UbercartOrderPane(
 *   id = "gocardless_info",
 *   title = @Translation("GoCardless"),
 *   weight = 5,
 * )
 */
class GoCardlessInfo extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return ['pos-left'];
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {

    if ($view_mode != 'customer') {

      // Todo Improve this for when there is more than one product in order.
      $query = db_select('uc_gc_client', 'g');
      $gc = $query
        ->fields('g')
        ->condition('ucid', $order->id())
        ->execute()->fetch();
      
      if (empty($gc)) return;

      $gc->status == 'completed' ? $status = 'Active' : $status = $gc->status;
      $gc->type == 'P' ? $type = 'Payments' : $type = 'Subscription';
      !is_null($gc->start_date) ? $start = format_date($gc->start_date, 'uc_store') : $start = 'Not set';
      if (isset($order->products[$gc->ucpid]->data['interval_params'])) {
        $params = $order->products[$gc->ucpid]->data['interval_params'];
        $interval = $params['length'] . ' ' . $params['unit'];
      }
      else {
        $interval = 'Not set';
      }
      !is_null($gc->next_payment) ? $next_payment = format_date($gc->next_payment) : $next_payment = 'Not set';

      $params = [
        '@gcid' => $gc->gcid,
        '@created' => format_date($gc->created, 'uc_store'),
        '@status' => $status,
        '@type' => $type,
        '@start' => $start,
        '@interval' => $interval,
        '@next_payment' => $next_payment,
      ];

      $markup = t('Mandate ID: @gcid <br />', $params);
      $markup .= t('Created: @created <br />', $params);
      $markup .= t('Status: @status <br />', $params);
      $markup .= t('Type: @type <br />', $params);
      $markup .= t('Start: @start <br />', $params);
      $markup .= t('Payment Interval: @interval <br />', $params);
      if ($gc->status != 'canceled' && $gc->type == 'P') {
        $markup .= t('Next payment creation: @next_payment', $params);
      }
      return ['#markup' => $markup];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {}

}
