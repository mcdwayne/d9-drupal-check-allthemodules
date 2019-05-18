<?php

namespace Drupal\plus\Events;

/**
 * Contains all events thrown while handling entity types.
 */
class ThemeEvents {

  /**
   * The name of the event triggered before a theme is set as active.
   *
   * This event allows extensions a chance to react to a theme being set as the
   * active theme. The event listener method receives a
   * \Drupal\plus\Events\ThemeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\plus\Events\ThemeEvent
   * @see \Drupal\plus\Events\ThemeEventSubscriberInterface::onThemeActivate()
   * @see \Drupal\plus\Core\Theme\ThemeManager::setActiveTheme()
   * @see \Drupal\plus\ThemePluginManager::onThemeActivate()
   *
   * @var string
   */
  const ACTIVATE = 'theme.activate';

  /**
   * The name of the event triggered after a theme is set as active.
   *
   * This event allows extensions a chance to react to a theme being set as the
   * active theme. The event listener method receives a
   * \Drupal\plus\Events\ThemeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\plus\Events\ThemeEvent
   * @see \Drupal\plus\Events\ThemeEventSubscriberInterface::onThemeActivate()
   * @see \Drupal\plus\Core\Theme\ThemeManager::setActiveTheme()
   * @see \Drupal\plus\ThemePluginManager::onThemeActivate()
   *
   * @var string
   */
  const ACTIVATED = 'theme.activated';

  /**
   * The name of the event triggered before a theme is installed.
   *
   * This event allows extensions a chance to react to a new theme being
   * installed. The event listener method receives a
   * \Drupal\plus\Events\ThemeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\plus\Events\ThemeEvent
   * @see \Drupal\plus\Events\ThemeEventSubscriberInterface::onThemeInstall()
   * @see \Drupal\plus\Core\Extension\ThemeInstaller::install()
   * @see \Drupal\plus\ThemePluginManager::onThemeInstall()
   *
   * @var string
   */
  const INSTALL = 'theme.install';

  /**
   * The name of the event triggered after a theme is installed.
   *
   * This event allows extensions a chance to react to a new theme being
   * installed. The event listener method receives a
   * \Drupal\plus\Events\ThemeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\plus\Events\ThemeEvent
   * @see \Drupal\plus\Events\ThemeEventSubscriberInterface::onThemeInstalled()
   * @see \Drupal\plus\Core\Extension\ThemeInstaller::install()
   * @see \Drupal\plus\ThemePluginManager::onThemeInstalled()
   *
   * @var string
   */
  const INSTALLED = 'theme.installed';

  /**
   * The name of the event triggered before a theme is uninstalled.
   *
   * This event allows extensions a chance to react to a theme being
   * uninstalled. The event listener method receives a
   * \Drupal\plus\Events\ThemeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\plus\Events\ThemeEvent
   * @see \Drupal\plus\Events\ThemeEventSubscriberInterface::onThemeUninstall()
   * @see \Drupal\plus\Core\Extension\ThemeInstaller::uninstall()
   * @see \Drupal\plus\ThemePluginManager::onThemeUninstall()
   *
   * @var string
   */
  const UNINSTALL = 'theme.uninstall';

  /**
   * The name of the event triggered after a theme is uninstalled.
   *
   * This event allows extensions a chance to react to a theme being
   * uninstalled. The event listener method receives a
   * \Drupal\plus\Events\ThemeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\plus\Events\ThemeEvent
   * @see \Drupal\plus\Events\ThemeEventSubscriberInterface::onThemeUninstalled()
   * @see \Drupal\plus\Core\Extension\ThemeInstaller::uninstall()
   * @see \Drupal\plus\ThemePluginManager::onThemeUninstalled()
   *
   * @var string
   */
  const UNINSTALLED = 'theme.uninstalled';

}
