<?php

namespace Drupal\couchbasedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendGeneralTestCase;

use Drupal\couchbasedrupal\Cache\CouchbaseRawBackendFactory;
use Drupal\couchbasedrupal\CouchbaseManager;
use Drupal\supercache\Cache\DummyTagChecksum;

use Drupal\Core\Site\Settings;


trait RawBackendGeneralTestCaseTrait {

  public function setUp() {

    parent::setUp();

    $app_root = '/';
    $site_path = uniqid();
    $loader = new \Composer\Autoload\ClassLoader();
    Settings::initialize($app_root, $site_path, $loader);
    $settings = \Drupal\Core\Site\Settings::getInstance();
    $manager = new CouchbaseManager($settings, $app_root, $site_path);
    $factory = new CouchbaseRawBackendFactory($manager);

    // The aim of this setup is to get two functional backend instances.
    $this->backend = $factory->get('test_binary');
    $this->backend2 = $factory->get('test_binary_alt');

    // Won't pass the tests if not using
    // consistent.
    $this->backend->setConsistent(TRUE);
    $this->backend2->setConsistent(TRUE);
  }

  public function tearDown() {
    if (!empty($this->backend)) {
      $this->backend->removeViews();
    }
    if (!empty($this->backend2)) {
      $this->backend2->removeViews();
    }
  }

}
