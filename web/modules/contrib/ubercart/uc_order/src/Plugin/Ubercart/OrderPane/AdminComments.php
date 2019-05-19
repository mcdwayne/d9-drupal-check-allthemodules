<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * View the admin comments, used for administrative notes and instructions.
 *
 * @UbercartOrderPane(
 *   id = "admin_comments",
 *   title = @Translation("Admin comments"),
 *   weight = 9,
 * )
 */
class AdminComments extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode != 'customer') {
      $build = [
        '#theme' => 'table',
        '#header' => [
          ['data' => $this->t('Date'), 'class' => ['date']],
          ['data' => $this->t('User'), 'class' => ['user']],
          ['data' => $this->t('Comment'), 'class' => ['message']],
        ],
        '#rows' => [],
        '#attributes' => ['class' => ['order-pane-table uc-order-comments']],
        '#empty' => $this->t('This order has no admin comments associated with it.'),
      ];
      $comments = uc_order_comments_load($order->id(), TRUE);
      foreach ($comments as $comment) {
        $build['#rows'][] = [
          ['data' => \Drupal::service('date.formatter')->format($comment->created, 'short'), 'class' => ['date']],
          ['data' => ['#theme' => 'username', '#account' => User::load($comment->uid)], 'class' => ['user']],
          ['data' => ['#markup' => $comment->message], 'class' => ['message']],
        ];
      }
      return $build;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $items = [];
    $comments = uc_order_comments_load($order->id(), TRUE);
    foreach ($comments as $comment) {
      $items[] = [
        'username' => [
          '#theme' => 'username',
          '#account' => User::load($comment->uid),
          '#prefix' => '[',
          '#suffix' => '] ',
        ],
        'message' => [
          '#markup' => $comment->message,
        ],
      ];
    }
    $form['comments'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('No admin comments have been entered for this order.'),
    ];

    $form['admin_comment_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Add an admin comment'),
    ];
    $form['admin_comment_field']['admin_comment'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Admin comments are only seen by store administrators.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('admin_comment')) {
      $uid = \Drupal::currentUser()->id();
      uc_order_comment_save($order->id(), $uid, $form_state->getValue('admin_comment'));
    }
  }

}
