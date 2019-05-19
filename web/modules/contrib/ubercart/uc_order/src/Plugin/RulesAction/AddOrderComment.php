<?php

namespace Drupal\uc_order\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\rules\Core\RulesActionBase;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Add an order comment' action.
 *
 * @RulesAction(
 *   id = "uc_order_action_add_comment",
 *   label = @Translation("Add a comment to the order"),
 *   category = @Translation("Order"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "comment" = @ContextDefinition("string",
 *       label = @Translation("Comment")
 *     ),
 *     "comment_type" = @ContextDefinition("string",
 *       label = @Translation("Comment type"),
 *       restriction = "input",
 *       list_options_callback = "orderCommentTypes"
 *     )
 *   }
 * )
 */
class AddOrderComment extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs the AddOrderComment object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  /**
   * Comment types option callback.
   */
  public function orderCommentTypes() {
    return [
      'admin' => $this->t('Enter this as an admin comment.'),
      'order' => $this->t('Enter this as a customer order comment.'),
      'notified' => $this->t('Enter this as a customer order comment with a notified icon.'),
    ];
  }

  /**
   * Adds a comment to an order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   * @param string $comment
   *   Text of the comment.
   * @param string $comment_type
   *   One of 'admin' or 'order'.
   */
  protected function doExecute(OrderInterface $order, $comment, $comment_type) {
    uc_order_comment_save($order->id(), 0,
      $this->token->replace($comment, ['uc_order' => $order]),
      $comment_type == 'admin' ? 'admin' : 'order',
      $order->getStatusId(), $comment_type == 'notified');
  }

}
