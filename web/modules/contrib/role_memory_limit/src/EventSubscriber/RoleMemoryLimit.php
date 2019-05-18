<?php

namespace Drupal\role_memory_limit\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RoleMemoryLimit.
 */
class RoleMemoryLimit implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $user;

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new RoleMemoryLimit object.
   */
  public function __construct(AccountProxy $user, ConfigFactory $config) {
    $this->user = $user;
    $this->config = $config->get('role_memory_limit.config')->getRawData();
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['SetPhpMemoryLimit'];

    return $events;
  }

  /**
   * Sets the PHP memory limit based on the users role.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   */
  public function SetPhpMemoryLimit(Event $event) {
    if (empty($this->config)) {
      return;
    }

    $memory_limits = [];
    if ($this->user->id() == '1') {
      $memory_limits[] = $this->config['admin'];
    }
    else {
      foreach ($this->user->getRoles() as $role) {
        $memory_limits[] = $this->config[$role];
      }
    }
    // Take the highest memory limit
    $mb = min($memory_limits) < 0 ? -1 : max($memory_limits);

    if ($mb) {
      if ($mb != '-1') {
        $mb .= 'M';
      }

      ini_set('memory_limit', $mb);
    }
  }

}
