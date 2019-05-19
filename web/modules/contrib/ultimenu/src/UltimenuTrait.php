<?php

namespace Drupal\ultimenu;

/**
 * A Trait common for Ultimenu split services.
 */
trait UltimenuTrait {

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function getConfig($config = 'ultimenu.settings') {
    return $this->configFactory->get($config);
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name = NULL) {
    return $this->getConfig()->get($setting_name);
  }

  /**
   * Return a shortcut for the default theme.
   */
  public function getThemeDefault() {
    return $this->configFactory->get('system.theme')->get('default');
  }

}
