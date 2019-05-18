<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Migration HTML - text processor.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "html_process_text"
 * )
 */
class TextProcess extends HtmlTagProcess {

  /**
   * The machine name of the text field of the paragraph.
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
      $this->setBundle('text');
    }

    $this->setFieldName('field_text');
    if (isset($this->configuration['field_name'])) {
      $this->setFieldName($this->configuration['field_name']);
    }

    $this->setTextFormat('full_html');
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

    // Cleanup HTML, ugly things which the text formats do not filter out.
    // Replace non breaking spaces to spaces.
    // ======================================
    // In order to be handled correctly in the next steps.
    $value = str_ireplace('&nbsp;', ' ', $value);

    // Remove commonly used occurrences.
    // =================================
    // E.g.:
    // - align="center"
    // - style="vertical-align: baseline;".
    $value = preg_replace('/(\s(align|style)="[^"]*")/i', '', $value);

    // Remove all whitespace from empty HTML tags.
    // ===========================================
    // E.g.:
    // - <p> </p> becomes <p></p>.
    // - <div>          </div> becomes <div></div>.
    // - <iframe src="">           </iframe> becomes <iframe src=""></iframe>.
    $value = preg_replace('/<(.+)>\s*<\/(.+)>/i', '<$1></$2>', $value);

    // Remove commonly used HTML tags which are empty (useless).
    // =========================================================
    // E.g.:
    // - <p></p>
    // - <div></div>
    // - <strong></strong>.
    $html_tags = '(a|b|blockquote|div|em|h1|h2|h3|h4|h5|h6|label|li|ol|p|span|strong|ul)';
    $value = preg_replace('/<' . $html_tags . '>\s*<\/' . $html_tags . '>/i', '', $value);

    if (empty(check_markup($value, $text_format))) {
      return NULL;
    }

    $paragraph = Paragraph::create([
      'id'                  => NULL,
      'type'                => $this->getBundle(),
      $this->getFieldName() => [
        'value' => $value,
        'format' => $text_format,
      ],
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Return the machine name of the text field of the paragraph.
   *
   * @return string|null
   *   The machine name of the text field of the paragraph or null if not set.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Set the machine name of the text field of the paragraph.
   *
   * @param string $fieldName
   *   The machine name of the text field of the paragraph.
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
