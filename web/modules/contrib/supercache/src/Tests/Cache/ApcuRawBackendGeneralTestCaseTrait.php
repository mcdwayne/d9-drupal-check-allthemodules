<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendGeneralTestCase;

use Drupal\supercache\Cache\ApcuRawBackendFactory;
use Drupal\supercache\CouchbaseManager;
use Drupal\supercache\Cache\DummyTagChecksum;

use Drupal\Core\Site\Settings;


trait ApcuRawBackendGeneralTestCaseTrait {

  public function setUp() {
    apcu_clear_cache();
    $app_root = '/';
    $site_path = uniqid();
    $factory = new ApcuRawBackendFactory($app_root, $site_path);

    // The aim of this setup is to get two functional backend instances.
    $this->backend = $factory->get('test_binary');
    $this->backend2 = $factory->get('test_binary_alt');

  }

  public function tearDown() {
  }

}
