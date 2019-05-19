<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendGetMultipleTests as GetMultipleTests;

class ApcuRawBackendGetMultipleTests extends GetMultipleTests {
  use ApcuRawBackendGeneralTestCaseTrait;
}
