<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendExpireTests as ExpireTests;

class DatabaseRawBackendExpireTests extends ExpireTests {
  use DatabaseRawBackendGeneralTestCaseTrait;
}
