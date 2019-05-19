<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\sir_trevor\IconSvgMergerInterface;

class IconSvgMergerMock implements IconSvgMergerInterface {
  /**
   * {@inheritdoc}
   */
  public function merge(array $fileNames) {
    return implode(';', $fileNames);
  }
}
