<?php

namespace Drupal\bibcite\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Processor plugins.
 */
abstract class BibCiteProcessorBase extends PluginBase implements BibCiteProcessorInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

}
