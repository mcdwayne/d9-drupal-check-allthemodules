<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\user\Entity\User;
use Drupal\uc_order\Entity\OrderStatus;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\OrderPanePluginBase;

/**
 * View the order comments, used for communicating with customers.
 *
 * @UbercartOrderPane(
 *   id = "order_comments",
 *   title = @Translation("Order comments"),
 *   weight = 8,
 * )
 */
class OrderComments extends OrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    // @todo Simplify this or replace with Views
    if ($view_mode == 'customer') {
      $comments = uc_order_comments_load($order->id());
      $statuses = OrderStatus::loadMultiple();
      $header = [
        ['data' => $this->t('Date'), 'class' => ['date']],
        ['data' => $this->t('Status'), 'class' => ['status']],
        ['data' => $this->t('Message'), 'class' => ['message']],
      ];
      $rows[] = [
        ['data' => \Drupal::service('date.formatter')->format($order->created->value, 'short'), 'class' => ['date']],
        ['data' => '-', 'class' => ['status']],
        ['data' => $this->t('Order created.'), 'class' => ['message']],
      ];
      if (count($comments) > 0) {
        foreach ($comments as $comment) {
          $rows[] = [
            ['data' => \Drupal::service('date.formatter')->format($comment->created, 'short'), 'class' => ['date']],
            ['data' => ['#plain_text' => $statuses[$comment->order_status]->getName()], 'class' => ['status']],
            ['data' => ['#markup' => $comment->message], 'class' => ['message']],
          ];
        }
      }
      $build = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['uc-order-comments']],
      ];
    }
    else {
      $build = [
        '#theme' => 'table',
        '#header' => [
          ['data' => $this->t('Date'), 'class' => ['date']],
          ['data' => $this->t('User'), 'class' => ['user', RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Notified'), 'class' => ['notified']],
          ['data' => $this->t('Status'), 'class' => ['status', RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Comment'), 'class' => ['message']],
        ],
        '#rows' => [],
        '#attributes' => ['class' => ['order-pane-table uc-order-comments']],
        '#empty' => $this->t('This order has no comments associated with it.'),
      ];
      $comments = uc_order_comments_load($order->id());
      $statuses = OrderStatus::loadMultiple();
      foreach ($comments as $comment) {
        $icon = $comment->notified ? 'true-icon.gif' : 'false-icon.gif';
        $build['#rows'][] = [
          ['data' => \Drupal::service('date.formatter')->format($comment->created, 'short'), 'class' => ['date']],
          ['data' => ['#theme' => 'username', '#account' => User::load($comment->uid)], 'class' => ['user']],
          ['data' => ['#theme' => 'image', '#uri' => drupal_get_path('module', 'uc_order') . '/images/' . $icon], 'class' => ['notified']],
          ['data' => ['#plain_text' => $statuses[$comment->order_status]->getName()], 'class' => ['status']],
          ['data' => ['#markup' => $comment->message], 'class' => ['message']],
        ];
      }
    }

    return $build;
  }

}
