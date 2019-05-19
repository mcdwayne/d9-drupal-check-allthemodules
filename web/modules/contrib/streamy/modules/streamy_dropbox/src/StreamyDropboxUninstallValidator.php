<?php

namespace Drupal\streamy_dropbox;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\streamy_dropbox\Form\Dropbox;

/**
 * Verifies that the plugins provided by this module
 * are in use by the main configuration. If so the uninstall
 * process will be stopped.
 *
 */
class StreamyDropboxUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * StreamyDropboxUninstallValidator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module_name) {
    $reasons = [];

    if ($module_name == 'streamy_dropbox') {
      $streamyConfig = (array) $this->configFactory->get('streamy.streamy')->get('plugin_configuration');
      $plugin_id = Dropbox::PLUGIN_ID;
      foreach ($streamyConfig as $scheme => $config) {
        if (in_array($plugin_id, $config)) {
          $reasons[] = $this->t("The plugin id '@pluginid' provided by this module is in use in the Streamy main configuration scheme '@scheme'.",
                                ['@pluginid' => $plugin_id, '@scheme' => $scheme]);
        }
      }
    }

    return $reasons;
  }

}
