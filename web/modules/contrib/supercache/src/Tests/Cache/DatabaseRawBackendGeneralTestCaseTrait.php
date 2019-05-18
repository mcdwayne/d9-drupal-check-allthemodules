<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\KernelTests\KernelTestBase;

use Drupal\supercache\Tests\Generic\Cache\BackendGeneralTestCase;

use Drupal\supercache\Cache\DatabaseRawBackendFactory;
use Drupal\supercache\CouchbaseManager;
use Drupal\supercache\Cache\DummyTagChecksum;

use Drupal\Core\Site\Settings;


trait DatabaseRawBackendGeneralTestCaseTrait {

  public function setUp() {

    KernelTestBase::setUp();

    $connection = \Drupal\Core\Database\Database::getConnection();
    $factory = new DatabaseRawBackendFactory($connection);

    // The aim of this setup is to get two functional backend instances.
    $this->backend = $factory->get('test_binary');
    $this->backend2 = $factory->get('test_binary_alt');

  }

  public function tearDown() {
  }

}
