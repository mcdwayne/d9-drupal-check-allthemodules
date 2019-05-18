<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendSavingTests as SavingTests;

class ApcuRawBackendSavingTests extends SavingTests {
  use ApcuRawBackendGeneralTestCaseTrait;
}
