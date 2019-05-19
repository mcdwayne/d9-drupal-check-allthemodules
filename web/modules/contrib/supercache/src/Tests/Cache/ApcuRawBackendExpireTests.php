<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendExpireTests as ExpireTests;

class ApcuRawBackendExpireTests extends ExpireTests {
  use ApcuRawBackendGeneralTestCaseTrait;

  public function testExpirations() {
    // APCu cannot be tested easily
    // like with the other backends.
    // @see http://stackoverflow.com/questions/11750223/apc-user-cache-entries-not-expiring
    $this->pass("This test cannot run on APCu.");
  }
}
