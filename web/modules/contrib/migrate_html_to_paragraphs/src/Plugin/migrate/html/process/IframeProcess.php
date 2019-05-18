<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Migration HTML - iframe processor.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "html_process_iframe"
 * )
 */
class IframeProcess extends HtmlTagProcess {

  /**
   * The machine name of the iframe field of the paragraph.
   *
   * @var string|null
   */
  protected $fieldName = NULL;

  /**
   * The text format to be used for the text field.
   *
   * @var string|null
   */
  protected $textFormat = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!isset($this->configuration['bundle'])) {
      $this->setBundle('embed');
    }

    $this->setFieldName('field_embed_code');
    if (isset($this->configuration['field_name'])) {
      $this->setFieldName($this->configuration['field_name']);
    }

    $this->setTextFormat('embed_codes');
    if (isset($this->configuration['text_format'])) {
      $this->setTextFormat($this->configuration['text_format']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process(MigrateExecutableInterface $migrate_executable, array $tag) {
    $this->migrateExecutable = $migrate_executable;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraph($value) {
    $text_format = $this->getTextFormat();

    if (empty(check_markup($value, $text_format))) {
      return NULL;
    }

    $paragraph = Paragraph::create([
      'id' => NULL,
      'type' => $this->getBundle(),
      $this->getFieldName() => [
        'value' => $value,
        'format' => $text_format,
      ],
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Return the machine name of the iframe field of the paragraph.
   *
   * @return string|null
   *   The machine name of the iframe field of the paragraph or null if not set.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Set the machine name of the iframe field of the paragraph.
   *
   * @param string $fieldName
   *   The machine name of the iframe field of the paragraph.
   */
  protected function setFieldName($fieldName) {
    $this->fieldName = $fieldName;
  }

  /**
   * Return the text format value.
   *
   * @return string|null
   *   Text format or null if not set.
   */
  public function getTextFormat() {
    return $this->textFormat;
  }

  /**
   * Set the text format path value.
   *
   * @param string $textFormat
   *   Text format.
   */
  protected function setTextFormat($textFormat) {
    $this->textFormat = $textFormat;
  }

}
