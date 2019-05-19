<?php

namespace Drupal\streamy_aws;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\streamy_aws\Form\AwsCdn;
use Drupal\streamy_aws\Form\AwsV3;

/**
 * Verifies that the plugins provided by this module
 * are in use by the main configuration. If so the uninstall
 * process will be stopped.
 *
 */
class StreamyAwsUninstallValidator implements ModuleUninstallValidatorInterface {

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

    if ($module_name == 'streamy_aws') {
      $streamyConfig = (array) $this->configFactory->get('streamy.streamy')->get('plugin_configuration');
      $plugin_ids = [AwsV3::PLUGIN_ID, AwsCdn::PLUGIN_ID];
      foreach ($streamyConfig as $scheme => $config) {
        foreach ($plugin_ids as $plugin_id) {
          if (in_array($plugin_id, $config, TRUE)) {
            $reasons[] = $this->t("The plugin id '@pluginid' provided by this module is in use in the Streamy main configuration scheme '@scheme'.",
                                  ['@pluginid' => $plugin_id, '@scheme' => $scheme]);
          }
        }
      }
    }

    return $reasons;
  }

}
