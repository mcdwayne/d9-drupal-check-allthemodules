<?php

/**
 * @file
 * Contains \Drupal\habitat\EventSubscriber\HabitatSubscriber.
 */

namespace Drupal\habitat\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Site\Settings;

class HabitatSubscriber implements EventSubscriberInterface {

  public function ensureHabitat(GetResponseEvent $event) {
    $habitat_variable = \Drupal::config('habitat.settings')->get('habitat_variable');
    if ($habitat = Settings::get($habitat_variable)) {
      $this->ensureModulesDisabled($habitat);
      $this->ensureModulesEnabled($habitat);
    }
  }

  public function ensureModulesDisabled($habitat) {
    $uninstalled_modules = \Drupal::config('habitat.settings')->get('habitat_uninstall_' . $habitat);
    if (count($uninstalled_modules)) {
      $module_installer = \Drupal::service('module_installer');
      foreach ($uninstalled_modules as $module) {
        if (\Drupal::moduleHandler()->moduleExists($module)) {
          $module_installer->uninstall(array($module));
          \Drupal::logger('habitat')->info('%module was uninstalled for the %habitat habitat', array('%module' => $module, '%habitat' => $habitat));
        }
      }
    }
  }

  public function ensureModulesEnabled($habitat) {
    $installed_modules = \Drupal::config('habitat.settings')->get('habitat_install_' . $habitat);
    if (count($installed_modules)) {
      $module_installer = \Drupal::service('module_installer');
      foreach ($installed_modules as $module) {
        if (!\Drupal::moduleHandler()->moduleExists($module)) {
          $module_installer->install(array($module));
          \Drupal::logger('habitat')->info('%module was installed for the %habitat habitat', array('%module' => $module, '%habitat' => $habitat));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('ensureHabitat');
    return $events;
  }
}
