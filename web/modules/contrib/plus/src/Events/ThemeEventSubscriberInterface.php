<?php

namespace Drupal\plus\Events;

/**
 * Interface ThemeEventSubscriberInterface.
 */
interface ThemeEventSubscriberInterface {

  /**
   * Responds to a "theme.activate" event.
   *
   * @param \Drupal\plus\Events\ThemeEvent $event
   *   The event object.
   *
   * @return \Drupal\plus\Events\ThemeEvent
   *   The event object.
   *
   * @see \Drupal\plus\Events\ThemeEvents::ACTIVATE
   */
  public function onThemeActivate(ThemeEvent $event);

  /**
   * Responds to a "theme.activated" event.
   *
   * @param \Drupal\plus\Events\ThemeEvent $event
   *   The event object.
   *
   * @return \Drupal\plus\Events\ThemeEvent
   *   The event object.
   *
   * @see \Drupal\plus\Events\ThemeEvents::ACTIVATED
   */
  public function onThemeActivated(ThemeEvent $event);

  /**
   * Responds to a "theme.install" event.
   *
   * @param \Drupal\plus\Events\ThemeEvent $event
   *   The event object.
   *
   * @return \Drupal\plus\Events\ThemeEvent
   *   The event object.
   *
   * @see \Drupal\plus\Events\ThemeEvents::INSTALL
   */
  public function onThemeInstall(ThemeEvent $event);

  /**
   * Responds to a "theme.installed" event.
   *
   * @param \Drupal\plus\Events\ThemeEvent $event
   *   The event object.
   *
   * @return \Drupal\plus\Events\ThemeEvent
   *   The event object.
   *
   * @see \Drupal\plus\Events\ThemeEvents::INSTALLED
   */
  public function onThemeInstalled(ThemeEvent $event);

  /**
   * Responds to a "theme.uninstall" event.
   *
   * @param \Drupal\plus\Events\ThemeEvent $event
   *   The event object.
   *
   * @return \Drupal\plus\Events\ThemeEvent
   *   The event object.
   *
   * @see \Drupal\plus\Events\ThemeEvents::UNINSTALL
   */
  public function onThemeUninstall(ThemeEvent $event);

  /**
   * Responds to a "theme.uninstalled" event.
   *
   * @param \Drupal\plus\Events\ThemeEvent $event
   *   The event object.
   *
   * @return \Drupal\plus\Events\ThemeEvent
   *   The event object.
   *
   * @see \Drupal\plus\Events\ThemeEvents::UNINSTALLED
   */
  public function onThemeUninstalled(ThemeEvent $event);

}
