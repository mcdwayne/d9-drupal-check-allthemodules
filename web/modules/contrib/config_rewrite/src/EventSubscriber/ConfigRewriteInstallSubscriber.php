<?php

namespace Drupal\config_rewrite\EventSubscriber;

use Drupal\config_rewrite\ConfigRewriter;
use Drupal\Core\Extension\ModuleEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigRewriteInstallSubscriber.
 *
 * @package Drupal\config_rewrite
 */
class ConfigRewriteInstallSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\config_rewrite\ConfigRewriter
   */
  protected $configRewriter;

  /**
   * ConfigRewriteInstallSubscriber constructor.
   * @param \Drupal\config_rewrite\ConfigRewriter $config_rewriter
   */
  public function __construct(ConfigRewriter $config_rewriter) {
    $this->configRewriter = $config_rewriter;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ModuleEvents::MODULE_INSTALLED][] = ['module_install_config_rewrite'];
    return $events;
  }

  /**
   * @param \Symfony\Component\EventDispatcher\Event $event
   */
  public function module_install_config_rewrite(Event $event) {
    $this->configRewriter->rewriteModuleConfig($event->getModule());
  }

}
