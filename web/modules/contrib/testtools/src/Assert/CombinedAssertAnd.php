<?php

declare(strict_types=1);

namespace Drupal\testtools\Assert;

/**
 * Combines asserts with an 'and' operator.
 */
class CombinedAssertAnd extends CombinedAssert {

  /**
   * {@inheritdoc}
   */
  protected function predicate(bool $r0, bool $r1): bool {
    return $r0 && $r1;
  }

}
