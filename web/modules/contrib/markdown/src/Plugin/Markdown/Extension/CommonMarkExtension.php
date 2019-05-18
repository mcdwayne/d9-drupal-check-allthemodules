<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

/**
 * Base class for CommonMark extensions.
 */
abstract class CommonMarkExtension extends BaseExtension implements CommonMarkExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $reflection = new \ReflectionClass($this);
    return $reflection->getShortName();
  }

}
