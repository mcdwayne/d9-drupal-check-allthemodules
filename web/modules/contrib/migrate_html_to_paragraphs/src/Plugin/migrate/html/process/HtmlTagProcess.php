<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\MigrateExecutableInterface;

/**
 * Abstract class for HTML Tag processors.
 */
abstract class HtmlTagProcess extends PluginBase {

  /**
   * The bundle of the paragraph.
   *
   * @var string|null
   */
  protected $bundle = NULL;

  /**
   * The Migration object.
   *
   * @var MigrateExecutableInterface|null
   */
  protected $migrateExecutable = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (isset($this->configuration['bundle'])) {
      $this->setBundle($this->configuration['bundle']);
    }
  }

  /**
   * Process the given tag.
   *
   * @param MigrateExecutableInterface $migrate_executable
   *   The Migration object.
   * @param array $tag
   *   The tag with it's parameters.
   *
   * @return bool
   *   Success or failure.
   */
  abstract public function process(MigrateExecutableInterface $migrate_executable, array $tag);

  /**
   * Create the paragraph.
   *
   * @param string $value
   *   The value for the paragraph to create.
   *
   * @return Paragraph|null
   *   The paragraph object or NULL if the creation failed.
   */
  abstract public function createParagraph($value);

  /**
   * Log a Migration message.
   */
  protected function logMessage($message, $level) {
    if (is_subclass_of($this->migrateExecutable, 'Drupal\migrate\MigrateExecutableInterface')) {
      $this->migrateExecutable->saveMessage($message, $level);
    }
  }

  /**
   * Return the bundle of the paragraph.
   *
   * @return string|null
   *   The bundle of the paragraph or null if not set.
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Set the bundle of the paragraph.
   *
   * @param string $bundle
   *   The bundle of the paragraph.
   */
  protected function setBundle($bundle) {
    $this->bundle = $bundle;
  }

}
