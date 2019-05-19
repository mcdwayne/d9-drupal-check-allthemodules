<?php

namespace Drupal\uc_file\Plugin\RulesAction;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_file\Event\NotifyGrantEvent;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a 'Renew file grant' action.
 *
 * @RulesAction(
 *   id = "uc_file_order_renew",
 *   label = @Translation("Renew the files on an order"),
 *   category = @Translation("Renewal"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     )
 *   }
 * )
 */
class RenewFile extends RulesActionBase implements ContainerFactoryPluginInterface {

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
   * Constructs a RenewFile object.
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
   * Renews an order's product files.
   *
   * This function updates access limits on all files found on all products
   * on a given order. First the order user is loaded, then the order's products
   * are scanned for file product features. An order comment is saved, and the
   * user is notified in Drupal, as well as through the email address associated
   * with the order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   */
  protected function doExecute(OrderInterface $order) {
    $user_downloads = [];

    // Load user.
    if (!($order_user = $order->getOwner())) {
      return;
    }

    // Scan products for models matching downloads.
    foreach ($order->products as $product) {
      // SELECT * FROM {uc_file_products} fp
      // INNER JOIN {uc_product_features} pf ON pf.pfid = fp.pfid
      // INNER JOIN {uc_files} f ON f.fid = fp.fid
      // WHERE nid = $product->nid
      $query = $this->database->select('uc_file_products', 'fp');
      $query->join('uc_product_features', 'pf', 'pf.pfid = fp.pfid');
      $query->join('uc_files', 'f', 'f.fid = fp.fid');
      $query->condition('nid', $product->nid);
      $query->fields('fp');
      $query->fields('pf');
      $query->fields('f');
      $files = $query->execute();

      foreach ($files as $file) {

        // Either they match, or the file was set to any SKU.
        if (!empty($file->model) && $file->model != $product->model) {
          continue;
        }

        // Grab any existing privilege so we can calculate the new expiration
        // time as an offset of the previous.
        $file_user = _uc_file_user_get($order_user, $file->fid);

        // Get the limit info from the product feature.
        $file_modification = [
          'download_limit' => uc_file_get_download_limit($file),
          'address_limit' => uc_file_get_address_limit($file),
          'expiration' => _uc_file_expiration_date(uc_file_get_time_limit($file), ($file_user ? max($file_user->expiration, REQUEST_TIME) : NULL)),
        ];

        // Add file_user(s) for this file/directory. (No overwrite).
        $new_files = uc_file_user_renew($file->fid, $order_user, $file->pfid, $file_modification, FALSE);

        // Save for notification.
        $user_downloads = array_merge($user_downloads, $new_files);

        // Note on the order where the user has gained download permission.
        if (is_dir(uc_file_qualify_file($file->filename))) {
          $comment = $this->t('User can now download files in the directory %dir.', ['%dir' => $file->filename]);
        }
        else {
          $comment = $this->t('User can now download the file %file.', ['%file' => \Drupal::service('file_system')->basename($file->filename)]);
        }
        uc_order_comment_save($order->id(), $order_user->id(), $comment);
      }
    }

    // Notify the user of their download(s).
    if ($user_downloads) {
      /* rules_invoke_event('uc_file_notify_grant', $order, $user_downloads); */
      $event = new NotifyGrantEvent($order, $user_downloads);
      $this->eventDispatcher->dispatch($event::EVENT_NAME, $event);
    }
  }

}
