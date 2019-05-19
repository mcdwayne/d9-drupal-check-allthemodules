<?php

namespace Drupal\tmgmt_smartling\Plugin\tmgmt_file\Format;

use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_file\Plugin\tmgmt_file\Format\Html;

/**
 * Export into XML.
 *
 * @FormatPlugin(
 *   id = "xml",
 *   label = @Translation("XML")
 * )
 */
class Xml extends Html {

  /**
   * {@inheritdoc}.
   */
  public function export(JobInterface $job, $conditions = []) {
    // Export job items data without relation to their ids.
    $items = [];

    foreach ($job->getItems($conditions) as $item) {
      $data = \Drupal::service('tmgmt.data')->filterTranslatable($item->getData());

      foreach ($data as $key => $value) {
        // TODO: identical filename task.
        // $items[$item->id()][$this->encodeIdSafeBase64($item->getItemType() . ':' . $item->getItemId() . '][' . $key)] = $value;
        $items[$item->id()][$this->encodeIdSafeBase64($item->id() . '][' . $key)] = $value + [
          'sl-variant' => $item->getItemType() . '-' . $item->getItemId() . '-' . $key,
        ];
      }
    }

    // Avoid rendering with "renderer" service in order to avoid theme debug
    // mode - if it's enabled we shouldn't print debug messages into XML file.
    // Use "twig" service instead.
    $variables = [
      'items' => $items,
    ];
    $theme_registry = theme_get_registry();
    $info = $theme_registry['tmgmt_smartling_xml_template'];
    $template_file = $info['template'] . '.html.twig';

    if (isset($info['path'])) {
      $template_file = $info['path'] . '/' . $template_file;
    }

    return $this->escapePluralStringDelimiter(
      \Drupal::service('twig')->loadTemplate($template_file)->render($variables)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateImport($imported_file, $job = TRUE) {
    $xml = simplexml_load_file($imported_file);

    if (!$xml) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function import($imported_file, $job = TRUE) {
    libxml_use_internal_errors(true);

    $dom = new \DOMDocument();
    $dom->loadHTMLFile($imported_file);
    $xml = simplexml_import_dom($dom);
    $data = [];

    // Get job items data from xml.
    foreach ($xml->xpath("//div[@class='atom']|//span[@class='atom']") as $atom) {
      // Assets are our strings (eq fields in nodes).
      $key = $this->decodeIdSafeBase64((string) $atom['id']);
      $data[$key]['#text'] = (string) $atom;

      // If we have some markup in plain text fields we need to decode it.
      if ($atom->getName() == 'span') {
        $data[$key]['#text'] = html_entity_decode($data[$key]['#text']);
      }

      $data[$key]['#text'] = $this->unEscapePluralStringDelimiter($data[$key]['#text']);
    }

    return \Drupal::service('tmgmt.data')->unflatten($data);

    // TODO: identical filename task.
    // Map job items from xml to job items from a given job.
    // $result = [];
    // $data = \Drupal::service('tmgmt.data')->unflatten($data);
    //
    // foreach ($data as $data_key => $data_item) {
    //   $conditions = explode(':', $data_key);
    //   $job_item = $job->getItems([
    //     'item_type' => $conditions[0],
    //     'item_id' => $conditions[1],
    //   ]);
    //   $job_item = reset($job_item);
    //
    //   if (!empty($job_item)) {
    //     $result[$job_item->id()] = $data_item;
    //   }
    // }
    //
    // return $result;
  }

  protected function escapePluralStringDelimiter($string) {
    return preg_replace("/\x03/", "!PLURAL_STRING_DELIMITER", $string);
  }

  protected function unEscapePluralStringDelimiter($string) {
    return preg_replace("/!PLURAL_STRING_DELIMITER/", "\x03", $string);
  }

}
