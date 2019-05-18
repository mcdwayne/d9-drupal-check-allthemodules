<?php

namespace Drupal\couchbasedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\CacheTagsChecksumTests as TagsChecksumTests;

use Drupal\couchbasedrupal\Cache\CouchbaseBackendFactory;
use Drupal\couchbasedrupal\CouchbaseManager;
use Drupal\couchbasedrupal\Cache\CouchbaseTagsChecksum;

use Drupal\Core\Site\Settings;

class TagsChecksumInvalidatorTests extends TagsChecksumTests {

  public function setUp() {

    parent::setUp();

    $site_path = 'mypath';

    $loader = new \Composer\Autoload\ClassLoader();
    Settings::initialize($this->root, $site_path, $loader);
    $settings = \Drupal\Core\Site\Settings::getInstance();

    $manager = new CouchbaseManager($settings, $this->root, $site_path);

    $couchbaseTags = new CouchbaseTagsChecksum($manager);

    $this->tagChecksum = $couchbaseTags;
    $this->tagInvalidator = $couchbaseTags;

    $this->tagInvalidator->resetTags();

    $this->backendFactory = new CouchbaseBackendFactory($manager, $this->tagChecksum, TRUE);

  }

  public function tearDown() {

  }
}
