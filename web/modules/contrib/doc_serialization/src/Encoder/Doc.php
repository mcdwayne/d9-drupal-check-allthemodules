<?php

namespace Drupal\doc_serialization\Encoder;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\Html;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Adds DOC encoder support for the Serialization API.
 */
class Doc implements EncoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'doc';

  /**
   * Format to write DOC files as.
   *
   * @var string
   */
  protected $docFormat = 'Doc2007';

  /**
   * Constructs an DOC encoder.
   *
   * @param string $doc_format
   *   The DOC format to use.
   */
  public function __construct($doc_format = 'Doc2007') {
    $this->docFormat = $doc_format;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    switch (gettype($data)) {
      case 'array':
        // Nothing to do.
        break;

      case 'object':
        $data = (array) $data;
        break;

      default:
        $data = [$data];
        break;
    }

    // Escape HTML Entities
    Settings::setOutputEscapingEnabled(true);

    try {
      // Instantiate a new Word object.
      $word = new PhpWord();

      /* Note: any element you append to a document must reside inside of a Section. */

      // Adding an empty Section to the document...
      $section = $word->addSection();

      if (!empty($context)) {
        if (!empty($context['views_style_plugin']->view)) {
          /** @var \Drupal\views\ViewExecutable $view */
          $view = $context['views_style_plugin']->view;
          // Set the document title based on the view title within the context.
          if (!empty($view->getTitle())) {
            $word->addTitleStyle(1, [
              'name' => 'Cambria',
              'size' => 28,
            ]);
            $section->addTitle($view->getTitle(), 1);
          }
        }
      }

      // Set the data.
      $this->setData($word, $data, $context);

      $writer = IOFactory::createWriter($word, $this->docFormat);

      // @todo utilize a temporary file perhaps?
      // @todo This should also support batch processing.
      ob_start();
      $writer->save('php://output');
      return ob_get_clean();
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Set document data.
   *
   * @param \PhpOffice\PhpWord\PhpWord $word
   *   The document to put the data in.
   * @param array $data
   *   The data to be put in the document.
   * @param array $context
   *   The context options array.
   */
  protected function setData(PhpWord $word, array $data, array $context) {
    $labels = $this->extractLabels($data, $context);
    $word->addFontStyle('bold', ['bold' => TRUE]);

    foreach ($data as $row) {
      global $base_url;
      $i = 0;
      $section = $word->addSection();
      foreach ($row as $value) {
        $section->addText($labels[$i] . ':', 'bold');

        // @todo No node info at this point, is there a better way then strpos?
        if (strpos($value, '<img src="') !== FALSE) {
          $img_url = explode('"', explode('<img src="', $value)[1])[0];
          $section->addImage($base_url . $img_url);
        }
        else {
          $section->addText($this->formatValue($value));
        }
        $i++;
      }
    }
  }

  /**
   * Formats a single value for a given value.
   *
   * @param string $value
   *   The raw value to be formatted.
   *
   * @return string
   *   The formatted value.
   */
  protected function formatValue($value) {
    // @todo Make these filters configurable.
    $value = Html::decodeEntities($value);
    $value = strip_tags($value);
    $value = trim($value);

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format === static::$format;
  }

  /**
   * Extract the labels from the data array.
   *
   * Uses View Labels if available, field names otherwise.
   *
   * @param array $data
   *   The data array.
   * @param array $context
   *   The context options array.
   *
   * @return string[]
   *   An array of labels or field names to be used.
   */
  protected function extractLabels(array $data, array $context) {
    $labels = [];
    if ($first_row = reset($data)) {
      if (!empty($context)) {
        /** @var \Drupal\views\ViewExecutable $view */
        $view = $context['views_style_plugin']->view;
        $fields = $view->field;
        foreach ($first_row as $key => $value) {
          $labels[] = !empty($fields[$key]->options['label']) ? $fields[$key]->options['label'] : $key;
        }
      }
      else {
        $labels = array_keys($first_row);
      }
    }

    return $labels;
  }

}
