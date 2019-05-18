<?php

namespace Drupal\chunker;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base implementation for a chunker Method plugin.
 *
 * @see plugin_api
 */
abstract class ChunkerMethodBase extends PluginBase implements ChunkerMethodInterface {

  /**
   * Creates a Chunker method.
   *
   * @inheritdoc
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Options used by the chunker methods.
   *
   * @return array
   *   The default settings used by 'custom' method.
   */
  public function defaultConfiguration() {
    return [
      'start_level' => 3,
      'section_tag' => 'div',
      'section_class' => 'chunker__section',
      'content_tag' => 'div',
      'content_class' => 'chunker__content',
      'wrapper_tag' => '',
      'wrapper_class' => '',
      'enwrap_first_element' => FALSE,
      'heading_tag' => '',
      'heading_class' => '',
      'heading_inner_tag' => '',
      'heading_inner_class' => '',
      'enwrap_first_elements' => TRUE,
      'reset' => FALSE,
      'permalink_string' => '',
      'fieldsets' => [
        'section_class' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += $this->defaultConfiguration();
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($text = '') {
    return chunker_chunk_text($text, $this->getConfiguration());
  }

}
