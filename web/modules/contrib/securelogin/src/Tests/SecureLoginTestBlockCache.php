<?php

namespace Drupal\securelogin\Tests;

/**
 * Tests Secure login with user login block enabled.
 *
 * @group Secure login
 */
class SecureLoginTestBlockCache extends SecureLoginTestBlock {

  /**
   * Use a profile that enables the cache modules.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();
  }

}
