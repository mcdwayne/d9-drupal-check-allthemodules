<?php

namespace Drupal\doc_serialization\Encoder;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Drupal\Component\Utility\Html;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Drupal\views\Views;
use Drupal\file\Entity\File;

/**
* Adds DOCX encoder support for the Serialization API.
*/
class Docx implements EncoderInterface {

  /**
  * The format that this encoder supports.
  *
  * @var string
  */
  protected static $format = 'docx';

  /**
  * Format to write DOC files as.
  *
  * @var string
  */
  protected $docFormat = 'Word2007';

  /**
  * Constructs an DOCX encoder.
  *
  * @param string $doc_format
  *   The DOC format to use.
  */
  public function __construct($doc_format = 'Word2007') {
    $this->docFormat = $doc_format;
  }

  /**
  * {@inheritdoc}
  *
  * @throws \Drupal\Component\Serialization\Exception\InvalidDataTypeException
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

    $views_style_plugin = $context['views_style_plugin'];
    $displayHandler = $views_style_plugin->displayHandler;

    try {
      $template_fid = $displayHandler->display['display_options']['display_extenders']['doc_serialization']['doc_serialization']['template_file'][0];
      $file = File::load($template_fid);
      $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());

      $templateProcessor = new TemplateProcessor($file_path);

      foreach ($data as $key => $row) {
        foreach ($row as $field => $value) {
          $templateProcessor->setValue($field, strip_tags($value));
        }
      }

      ob_start();
      $templateProcessor->saveAs('php://output');
      return ob_get_clean();
    }
    catch (\Exception $e) {
      \Drupal::logger('docx')->error($e->getMessage());
    }

  }

  /**
  * {@inheritdoc}
  */
  public function supportsEncoding($format) {
    return $format === static::$format;
  }

}
