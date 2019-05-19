<?php

namespace Drupal\Tests\transactionalphp;

use Drupal\transactionalphp\TransactionalPhpAwareTrait;
use Drupal\transactionalphp\TransactionalPhpIndexerAwareTrait;

/**
 * Class TestAware.
 *
 * @package Drupal\Tests\transactionalphp
 */
class TestAware {
  use TransactionalPhpAwareTrait;
  use TransactionalPhpIndexerAwareTrait;

}
