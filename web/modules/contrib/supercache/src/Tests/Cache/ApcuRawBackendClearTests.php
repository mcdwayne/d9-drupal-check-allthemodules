<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendClearTests as ClearTests;

class ApcuRawBackendClearTests extends ClearTests {
  use ApcuRawBackendGeneralTestCaseTrait;
}
