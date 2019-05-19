<?php

namespace Drupal\uc_role\Plugin\RulesAction;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_role\Event\NotifyGrantEvent;
use Drupal\uc_role\Event\NotifyRenewEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a 'Renew role' action.
 *
 * @RulesAction(
 *   id = "uc_role_order_renew",
 *   label = @Translation("Renew the roles on an order"),
 *   category = @Translation("Renewal"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "message" = @ContextDefinition("boolean",
 *       label = @Translation("Display messages to alert users of any new or updated roles."),
 *       list_options_callback = "booleanOptions"
 *     )
 *   }
 * )
 */
class RenewRole extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The database service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a RenewRole object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event_dispatcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Returns a TRUE/FALSE option set for boolean types.
   *
   * @return array
   *   A TRUE/FALSE options array.
   */
  public function booleanOptions() {
    return [
      0 => $this->t('False'),
      1 => $this->t('True'),
    ];
  }

  /**
   * Calculates the expiration time using a role_product object.
   *
   * @param $role_product
   *   The role product object whose expiration times to calculate.
   * @param int $quantity
   *   Used to multiply any relative expiration time, if the $role_product
   *   says to.
   * @param int $time
   *   The current time to use as a starting point for relative expiration
   *   calculation.
   *
   * @return int
   *   The expiration time as a Unix timestamp.
   */
  protected function getExpiration($role_product, $quantity, $time) {
    // Override the end expiration?
    if ($role_product->end_override) {

      // Absolute times are easy...
      if ($role_product->end_time) {
        return $role_product->end_time;
      }

      // We're gonna have to calculate the relative time from $time.
      $length = $role_product->duration * ($role_product->by_quantity ? $quantity : 1);
      return _uc_role_get_expiration($length, $role_product->granularity, $time);
    }

    // No override, use the default expiration values.
    else {
      // Relative...
      $roles_config = \Drupal::config('uc_role.settings');
      if ($roles_config->get('default_end_expiration') === 'rel') {
        $length = $roles_config->get('default_length') * ($role_product->by_quantity ? $quantity : 1);
        return _uc_role_get_expiration($length, $roles_config->get('default_granularity'), $time);
      }

      // Absolute...
      $end_time = $roles_config->get('default_end_time');
      if ($end_time) {
        $end_time = mktime(0, 0, 0, $end_time['month'], $end_time['day'], $end_time['year']);
      }

      return $end_time;
    }
  }

  /**
   * Renews an order's product roles.
   *
   * This function updates expiration time on all roles found on all products
   * on a given order. First the order user is loaded, then the order's products
   * are scanned for role product features. If any are found the expiration time
   * of the role is set using the feature settings to determine the new length
   * of time the new expiration will last. An order comment is saved, and the
   * user is notified in Drupal as well as through the email address associated
   * with the order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   * @param bool $message
   *   If TRUE, messages will be displayed to the user about the renewal.
   */
  protected function doExecute(OrderInterface $order, $message) {
    // Load the order's user and exit if not available.
    if (!($account = $order->getOwner())) {
      return;
    }

    // Loop through all the products on the order.
    foreach ($order->products as $product) {
      // Look for any role promotion features assigned to the product.
      $roles = $this->database->query('SELECT * FROM {uc_roles_products} WHERE nid = :nid', [':nid' => $product->nid]);

      foreach ($roles as $role) {
        // Product model matches, or was 'any'.
        if (!empty($role->model) && $role->model != $product->model) {
          continue;
        }

        $existing_role = $this->database->query('SELECT * FROM {uc_roles_expirations} WHERE uid = :uid AND rid = :rid', [':uid' => $account->id(), ':rid' => $role->rid])->fetchObject();

        // Determine the expiration timestamp for the role.
        $expiration = $this->getExpiration($role, $product->qty, isset($existing_role->expiration) ? $existing_role->expiration : NULL);

        // Leave an order comment.
        if (isset($existing_role->expiration)) {
          $op = 'renew';
          $comment = $this->t('Customer user role %role renewed.', ['%role' => _uc_role_get_name($role->rid)]);

          // Renew the user's role.
          uc_role_renew($account, $role->rid, $expiration, !$message);
        }
        else {
          $op = 'grant';
          $comment = $this->t('Customer granted user role %role.', ['%role' => _uc_role_get_name($role->rid)]);

          // Grant the role to the user.
          uc_role_grant($account, $role->rid, $expiration, TRUE, !$message);
        }

        // Get the new expiration (if applicable).
        $new_expiration = $this->database->query('SELECT * FROM {uc_roles_expirations} WHERE uid = :uid AND rid = :rid', [':uid' => $account->id(), ':rid' => $role->rid])->fetchObject();
        if (!$new_expiration) {
          $new_expiration = new stdClass();
          $new_expiration->uid = $account->uid;
          $new_expiration->rid = $role->rid;
          $new_expiration->expiration = NULL;
        }

        uc_order_comment_save($order->id(), $account->id(), $comment);

        // Trigger role email.
        /* rules_invoke_event('uc_role_notify_' . $op, $order, $new_expiration); */
        if ($op == 'grant') {
          $event = new NotifyGrantEvent($order, $new_expiration);
        }
        elseif ($op == 'renew') {
          $event = new NotifyRenewEvent($order, $new_expiration);
        }
        $this->eventDispatcher->dispatch($event::EVENT_NAME, $event);
      }
    }
  }

}
