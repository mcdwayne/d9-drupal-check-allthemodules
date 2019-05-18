<?php

namespace Drupal\itemsessionlock\Plugin\ItemSessionLock;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines our interface.
 */
interface ItemSessionLockInterface extends PluginInspectionInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition);

  /**
   * Get the lock label.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Get the lock id.
   *
   * @return string
   */
  public function getIid();

  /**
   * Set the lock id.
   *
   * @return string
   */
  public function setIid($iid);

  /**
   * Defines our routes for breaking locks.
   * @return array of Symfony\Component\Routing\Route
   */
  public function getRoutes();

  /**
   * Defines our permissions for breaking locks.
   * @return array of permissions definition array.
   */
  public function getPermissions();

  /**
   * Get the user associated with a lock.
   * @return int uid of the user, 0 if no lock is found.
   */
  public function getOwner();

  /**
   * Get the url to break a given link
   * @param string $module of defining the lock type.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   * @param string $any (default to own) weither the link is to break own lock or any.
   *
   * @return string a route path.
   */
  public static function getBreakRoute($module, $type, $iid, $any = 'own');

  /**
   * CRUD: Set a lock on a session.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   * @param $data arbitrary data to serialize.
   *
   * @return
   * Bool, TRUE on success (or if locks belongs to same user session), FALSE is lock already exists.
   */
  public static function set($type, $iid, $data = NULL);

  /**
   * CRUD: Release a lock on a session.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   * @return
   * Bool. Whether lock has been acquired or not.
   */
  public static function clear($type, $iid);

  /**
   * CRUD: Check wether a lock is available.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   *
   * @return
   * The lock data, if one.
   */
  public static function get($type, $iid);
}
