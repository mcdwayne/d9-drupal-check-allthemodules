<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_html_to_paragraphs\Plugin\MigrateHtmlPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts HTML to D8 Paragraph items.
 *
 * @MigrateProcessPlugin(
 *   id = "html_to_paragraphs"
 * )
 *
 * Usage:
 *  field_which_will_contain_paragraph_items:
 *    plugin: html_to_paragraphs
 *    source: content_with_html
 *    parser:
 *      -
 *        plugin: html_parser_img
 *      -
 *        plugin: html_parser_iframe
 *    process:
 *      -
 *        plugin: html_process_img
 *        source_base_path: '/path/which/contains/the/files'
 *        source_base_url:
 *          - 'http://www.example.com'
 *          - 'http://example.com'
 *        target_folder: 'public://migrate/legacy/path/to/store/files'
 *      -
 *        plugin: html_process_iframe
 *      -
 *        plugin: html_process_text
 *        fallback: true
 */
class HtmlToParagraphs extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The parser plugin manager.
   *
   * @var \Drupal\migrate_html_to_paragraphs\Plugin\MigrateHtmlPluginManager
   */
  protected $parserPluginManager;

  /**
   * The parsers for this plugin.
   *
   * @var array
   */
  protected $parsers;

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate_html_to_paragraphs\Plugin\MigrateHtmlPluginManager
   */
  protected $processPluginManager;

  /**
   * The processors for this plugin.
   *
   * @var array
   */
  protected $processors;

  /**
   * The fallback processor for this plugin.
   *
   * @var HtmlTagProcess
   */
  protected $fallbackProcessor = NULL;

  /**
   * HtmlToParagraphs constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate_html_to_paragraphs\Plugin\MigrateHtmlPluginManagerInterface $parser_plugin_manager
   *   The migration html parser plugin manager.
   * @param \Drupal\migrate_html_to_paragraphs\Plugin\MigrateHtmlPluginManagerInterface $process_plugin_manager
   *   The migration html process plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateHtmlPluginManagerInterface $parser_plugin_manager, MigrateHtmlPluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->parserPluginManager = $parser_plugin_manager;
    $this->processPluginManager = $process_plugin_manager;
    $this->initializeParsers();
    $this->initializeProcessors();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migrate.html.parser'),
      $container->get('plugin.manager.migrate.html.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Parsers.
    $parsers = $this->getParsers();

    // Processors.
    $processors = $this->getProcessors();
    if (empty($processors)) {
      throw new MigrateException('"Migrate HTML to Paragraphs" plugin is missing processors in its configuration.');
    }

    // Fallback Processor.
    $fallback_processor = $this->getFallbackProcessor();
    if (empty($fallback_processor)) {
      throw new MigrateException('"Migrate HTML to Paragraphs" plugin is missing a fallback processor in its configuration.');
    }

    // Logic.
    $tags = [];

    foreach ($parsers as $parser) {
      // Clone the parser in order to start with clean class,
      // otherwise the tags will be kept.
      $parser = clone $parser;
      /* @var $parser HtmlTagParser */
      if ($parser->parse($value)) {
        $tags += $parser->getTags();
      }
    }

    $paragraphs = [];

    if (empty($tags)) {
      if ($paragraph = $this->createFallbackParagraph($migrate_executable, $value)) {
        $paragraphs[] = [
          'target_id'          => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }
    else {
      // Process the tags.
      ksort($tags);
      $previous_end_position = 0;

      foreach ($tags as $position => $tag) {
        // See if we need to create a text paragraph for everything which is
        // in between the previous ending and the start position of the
        // current tag.
        if ($previous_end_position < $position) {
          $text = substr($value, $previous_end_position, $position - $previous_end_position);
          if ($paragraph = $this->createFallbackParagraph($migrate_executable, $text)) {
            $paragraphs[] = [
              'target_id'          => $paragraph->id(),
              'target_revision_id' => $paragraph->getRevisionId(),
            ];
          }
        }

        $length = strlen($tag['tag']);
        $text = trim(substr($value, $position, $length));
        $previous_end_position = $position + $length;

        $processor_plugin_id = $tag['_processor_plugin_id'];
        $processor = clone $this->getProcessor($processor_plugin_id);

        if ($processor && $processor->process($migrate_executable, $tag)) {
          $paragraph = $processor->createParagraph($text);
        }
        else {
          $paragraph = $this->createFallbackParagraph($migrate_executable, $text);
        }

        if ($paragraph) {
          $paragraphs[] = [
            'target_id'          => $paragraph->id(),
            'target_revision_id' => $paragraph->getRevisionId(),
          ];
        }
      }

      // See if we need to create a text paragraph for everything which is
      // in between the previous ending and the end position of the
      // complete value.
      if ($previous_end_position < strlen($value)) {
        $text = substr($value, $previous_end_position, strlen($value) - $previous_end_position);
        if ($paragraph = $this->createFallbackParagraph($migrate_executable, $text)) {
          $paragraphs[] = [
            'target_id'          => $paragraph->id(),
            'target_revision_id' => $paragraph->getRevisionId(),
          ];
        }
      }
    }

    return empty($paragraphs) ? NULL : $paragraphs;
  }

  /**
   * Initializes the parsers configured for this plugin implementation.
   */
  protected function initializeParsers() {
    $this->parsers = [];

    if (array_key_exists('parser', $this->configuration) && !empty($this->configuration['parser'])) {
      foreach ($this->configuration['parser'] as $parser) {
        $plugin_id = $parser['plugin'];
        $this->parsers[] = $this->parserPluginManager->createInstance($plugin_id, $parser, $this);
      }
    }
  }

  /**
   * Returns the parsers configured for this plugin implementation.
   *
   * @return array
   *   Array with parsers.
   */
  protected function getParsers() {
    return $this->parsers;
  }

  /**
   * Returns the processors configured for this plugin implementation.
   */
  protected function initializeProcessors() {
    $this->processors = [];

    if (array_key_exists('process', $this->configuration) && !empty($this->configuration['process'])) {
      foreach ($this->configuration['process'] as $process) {
        $plugin_id = $process['plugin'];
        $processor = $this->processPluginManager->createInstance($plugin_id, $process, $this);
        $this->processors[] = $processor;

        // Set this processor to be the fallback processor according to
        // configuration.
        if (isset($process['fallback']) && $process['fallback']) {
          $this->fallbackProcessor = $processor;
        }
      }
    }
  }

  /**
   * Returns the processors configured for this plugin implementation.
   *
   * @return array
   *   Array with processors.
   */
  protected function getProcessors() {
    return $this->processors;
  }

  /**
   * Returns a processor for the given plugin ID.
   *
   * @param string $plugin_id
   *   The plugin_id.
   *
   * @return HtmlTagProcess|null
   *   The HTML Tag processor or NULL if not found.
   */
  protected function getProcessor($plugin_id) {
    foreach ($this->processors as $processor) {
      /* @var HtmlTagProcess */
      if ($processor->getPluginId() == $plugin_id) {
        return $processor;
      }
    }

    return NULL;
  }

  /**
   * Returns the fallback processor configured for this plugin implementation.
   *
   * @return HtmlTagProcess
   *   The HTML Tag processor.
   */
  protected function getFallbackProcessor() {
    return $this->fallbackProcessor;
  }

  /**
   * Creates and returns a fallback paragraph.
   *
   * @param string $text
   *   The text.
   *
   * @return Paragraph|null
   *   The paragraph object or NULL on fail.
   */
  protected function createFallbackParagraph(MigrateExecutableInterface $migrate_executable, $text) {
    $processor = $this->getFallbackProcessor();
    $processor->process($migrate_executable, []);
    return $processor->createParagraph($text);
  }

}
