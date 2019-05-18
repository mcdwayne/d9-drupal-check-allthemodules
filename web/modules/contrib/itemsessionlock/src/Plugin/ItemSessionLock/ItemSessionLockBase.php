<?php

namespace Drupal\itemsessionlock\Plugin\ItemSessionLock;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\Routing\Route;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\SessionHandler;
use Drupal\itemsessionlock\Lock;

class ItemSessionLockBase extends PluginBase implements ItemSessionLockInterface {

  private $iid;
  private $data = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // If iid was given as $config param, set it now.
    if (!empty($configuration['iid'])) {
      $this->setIid($configuration['iid']);
    }
    // If data was given as $config param, set it now.
    if (!empty($configuration['data'])) {
      $this->setIid($configuration['data']);
    }
  }

  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  public function getIid() {
    return $this->iid;
  }

  public function setIid($iid) {
    $this->iid = $iid;
    return $this->iid;
  }

  /**
   * Defines our routes for breaking locks.
   * @return array of Symfony\Component\Routing\Route
   */
  public function getRoutes() {
    $routes = array();
    $module = $this->getProvider();
    $type = $this->getPluginId();
    $routes[$module . '.' . $type . '.' . 'own'] = new Route(
        '/itemsessionlock/' . $module . '/break/own/{type}/{iid}', array(
      '_controller' => '\Drupal\itemsessionlock\Controller\ItemSessionLockController::lockBreakOwn',
        ), array(
      '_user_is_logged_in' => 'TRUE',
        )
    );
    $routes[$module . '.' . $type . '.' . 'any'] = new Route(
        '/itemsessionlock/' . $module . '/break/any/{type}/{iid}', array(
      '_controller' => '\Drupal\itemsessionlock\Controller\ItemSessionLockController::lockBreakAny',
        ), array(
      // We can now use + and , for multiple checks.
      '_permission' => 'break any ' . $type . ' locks+break any session lock',
        )
    );
    return $routes;
  }

  /**
   * Defines our permissions for breaking locks.
   * @return array of permission definition arrays.
   */
  public function getPermissions() {
    $permissions = array();
    $module = $this->getProvider();
    $type = $this->getPluginId();
    $permissions['break any ' . $type . ' locks'] = array(
      'title' => t('Break any locks on @type items', array('@type' => $this->getLabel())),
      'description' => t('Break locks defined by @module on @type locked by any user.', array('@module' => $module, '@type' => $this->getLabel())),
    );
    return $permissions;
  }

  /**
   * Get the user associated with a lock.
   * @return int uid of the user, 0 if no lock is found.
   */
  public function getOwner() {
    $existing = $this->get($this->getPluginId(), $this->getIid());
    if (!empty($existing->uid)) {
      return $existing->uid;
    }
    return 0;
  }

  /**
   * Get the url to break a given link
   * @param string $module defining the lock type.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   * @param string $any "any" or "own" (default to own) weither the link is to break own lock or any.
   *
   * @return string a route path.
   */
  public static function getBreakRoute($module, $type, $iid, $any = 'own') {
    $url = Url::fromRoute($module . '.' . $type . '.' . $any, array('type' => $type, 'iid' => $iid));
    return $url->toString();
  }

  /**
   * Helper function: tries to lock an item.
   * @param string $redirect a route to redirect to if lock can't be acquired.
   */
  public function ensureLock($redirect = '') {
    $module = $this->getProvider();
    $type = $this->getPluginId();
    $iid = $this->iid;
    // Check for conflicts.
    if (!$this->set($type, $iid, $this->data)) {
      $label = $this->getLabel();
      $lock = $this->get($type, $iid);
      //If user if the same using a different session, let him delete the lock directly.
      $account = \Drupal::currentUser();
      if ($account->id() == $lock->uid) {
        $url = Url::fromRoute($module . '.' . $type . '.own', array('type' => $type, 'iid' => $iid));
        $url->setOption('query', array('destination' => \Drupal::request()->getRequestUri()));
        $message = t('This @item is already locked by yourself, using another session. This can occur if you started editing it on another device or browser, or your session has expired since then. You can click here to !break, but your changes will be lost.', array('@item' => $label, '!break' => \Drupal::service('link_generator')->generate(t('break the lock'), $url)));
      }
      else {
        // Else needs perms.
        $account = User::load($lock->uid);
        $message = t('This @item is already being edited by user !user, and is locked.', array('@item' => $label, '!user' => \Drupal::l($account->getUsername(), Url::fromUri('internal:' . $account->url()))));
        if (\Drupal::currentUser()->hasPermission('break any ' . $type . ' locks')) {
          $url = Url::fromRoute($module . '.' . $type . '.any', array('type' => $type, 'iid' => $iid));
          $url->setOption('query', array('destination' => \Drupal::request()->getRequestUri()));
          $message .= ' ' . t('You can click here to !break, at the risk at loosing his work in progress.', array('!break' => \Drupal::l(t('break the lock'), $url)));
        }
      }
      drupal_set_message($message, 'warning');
      throw new AccessDeniedHttpException();
      if ($redirect) {

        $response = new RedirectResponse($redirect);
//        drupal_exit($redirect);
//        $response->sendHeaders();
      }
    }
  }

  /**
   * Set a lock on a session.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   * @param $data arbitrary data to serialize.
   *
   * @return
   * Bool, TRUE on success (or if locks belongs to same user session), FALSE is lock already exists.
   */
  public static function set($type, $iid, $data = NULL) {
    $lock = self::get($type, $iid);
    if ($lock->sid) {
      //@todo What to do with ssid ?
      if (SessionHandler::getId() === $lock->sid) {
        return TRUE;
      }
      return FALSE;
    }
    $lock = new Lock($type, $iid, $data);
    $lock->write();
    return TRUE;
  }

  /**
   * Release a lock on a session.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   * @return
   * Bool. Whether lock has been acquired or not.
   */
  public static function clear($type, $iid) {
    $lock = new Lock($type, $iid);
    return $lock->delete();
  }

  /**
   * Check wether a lock is available.
   * @param string $type of the locked item.
   * @param string $iid unique identifier for the item to lock.
   *
   * @return
   * The lock data, if one.
   */
  public static function get($type, $iid) {
    $lock = new Lock($type, $iid);
    $lock->fetch();
    return($lock);
  }

}
