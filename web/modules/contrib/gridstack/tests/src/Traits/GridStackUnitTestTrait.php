<?php

namespace Drupal\Tests\gridstack\Traits;

/**
 * A Trait common for GridStack Unit tests.
 */
trait GridStackUnitTestTrait {

  /**
   * Defines scoped definition.
   */
  protected function getGridStackFormatterDefinition() {
    return [
      'namespace' => 'gridstack',
    ] + $this->getFormatterDefinition() + $this->getDefaulEntityFormatterDefinition();
  }

}
