<?php

namespace Drupal\couchbasedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendExpireTests as ExpireTests;

class RawBackendExpireTests extends ExpireTests {
  use RawBackendGeneralTestCaseTrait;
}
