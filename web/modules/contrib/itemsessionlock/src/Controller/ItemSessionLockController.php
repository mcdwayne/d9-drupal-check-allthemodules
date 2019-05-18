<?php

namespace Drupal\itemsessionlock\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\itemsessionlock\Plugin\ItemSessionLock\ItemSessionLockManager;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ItemSessionLockController implements ContainerInjectionInterface {

  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'));
  }

  /**
   * Page callback: release a lock and redirects.
   * @param string $iid
   */
  public function lockBreakOwn($type, $iid) {
    return $this->lockBreak($type, $iid);
  }

  /**
   * Page callback: release a lock and redirects.
   * @param string $iid
   */
  public function lockBreakAny($type, $iid, $any = '') {
    return $this->lockBreak($type, $iid, 'any');
  }

  /**
   * Release a lock and redirects.
   * @param string $iid
   */
  public function lockBreak($type, $iid, $any = '') {
    $manager = \Drupal::service('plugin.manager.itemsessionlock');
    $lock = $manager->createInstance($type, array('iid' => $iid));
    // Check if said lock exists.
    if ($any !== 'any') {
      $account = \Drupal::currentUser();
      $owner = $lock->getOwner();
      if (!$owner || $owner != $account->id()) {
        throw new AccessDeniedHttpException();
      }
    }
    $lock->clear($type, $iid);
    drupal_set_message(t('Lock as been removed.'));
    $redirect = Url::fromUri('internal:' . \Drupal::destination()->get());
    $redirect->setAbsolute();
    return new RedirectResponse($redirect->toString(), 301);
  }

}
