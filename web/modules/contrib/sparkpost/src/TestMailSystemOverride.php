<?php

namespace Drupal\sparkpost;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class TestMailSystemOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('system.mail', $names)) {
      // Use if default MailManager in use.
      $overrides['system.mail']['interface']['sparkpost_test_mail_form'] = 'sparkpost_mail';
    }
    if (in_array('mailsystem.settings', $names)) {
      // Use if mailsystem module is enabled.
      $overrides['mailsystem.settings']['modules']['sparkpost']['test_mail_form']['formatter'] = 'sparkpost_mail';
      $overrides['mailsystem.settings']['modules']['sparkpost']['test_mail_form']['sender'] = 'sparkpost_mail';
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'TestMailSystemOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
