<?php

namespace Drupal\bibcite\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Bibcite format wrapper.
 */
class BibciteFormat extends PluginBase implements BibciteFormatInterface {

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return $this->pluginDefinition['fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return $this->pluginDefinition['types'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension() {
    return $this->pluginDefinition['extension'];
  }

  /**
   * {@inheritdoc}
   */
  public function isExportFormat() {
    return !empty($this->pluginDefinition['encoder']) && is_subclass_of($this->pluginDefinition['encoder'], EncoderInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function isImportFormat() {
    return !empty($this->pluginDefinition['encoder']) && is_subclass_of($this->pluginDefinition['encoder'], DecoderInterface::class);
  }

}
