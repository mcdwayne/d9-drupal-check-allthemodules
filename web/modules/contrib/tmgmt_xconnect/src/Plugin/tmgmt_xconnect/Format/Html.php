<?php

/**
 * @file
 * Contains Drupal\tmgmt_xconnect\Format\Html.
 */

namespace Drupal\tmgmt_xconnect\Plugin\tmgmt_xconnect\Format;

use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt_xconnect\Format\FormatInterface;
use Drupal\tmgmt_xconnect\Annotation\FormatPlugin;
use Drupal\Core\Annotation\Translation;

/**
 * Export into HTML.
 *
 * @FormatPlugin(
 *   id = "html",
 *   label = @Translation("HTML")
 * )
 */
class Html implements FormatInterface {

  /**
   * Returns base64 encoded data that is safe for use in xml ids.
   */
  protected function encodeIdSafeBase64($data) {
    // Prefix with a b to enforce that the first character is a letter.
    return 'b' . rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Returns decoded id safe base64 data.
   */
  protected function decodeIdSafeBase64($data) {
    // Remove prefixed b.
    $data = substr($data, 1);
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
  }

  /**
   * Implements FormatInterface::export().
   */
  public function exportJobItem(JobItemInterface $jobitem) {
    $job = $jobitem->getJob();
    $items = array();
    $data = \Drupal::service('tmgmt.data')->filterTranslatable($jobitem->getData());
    foreach ($data as $key => $value) {
      $items[$jobitem->id()][$this->encodeIdSafeBase64($jobitem->id() . '][' . $key)] = $value;
    }
    $elements = array(
      '#theme' => 'tmgmt_xconnect_html_template',
      '#tjid' => $job->id(),
      '#source_language' => $job->getRemoteSourceLanguage(),
      '#target_language' => $job->getRemoteTargetLanguage(),
      '#source_url' => $jobitem->getSourceUrl()->setAbsolute()->toString(),
      '#items' => $items,
    );
    return \Drupal::service('renderer')->renderPlain($elements);
  }

  /**
   * Implements FormatInterface::import().
   */
  public function import($imported_file) {
    $dom = new \DOMDocument();
    $dom->loadHTML($imported_file);
    $xml = simplexml_import_dom($dom);

    $data = array();
    foreach ($xml->xpath("//div[@class='atom']") as $atom) {
      // Assets are our strings (eq fields in nodes).
      $id = (string) $atom['id'];
      $key = $this->decodeIdSafeBase64($id);
      $text = $this->extractInnerHtml($dom->getElementById($id));
      $data[$key]['#text'] = $text;
    }
    return trim(\Drupal::service('tmgmt.data')->unflatten($data));
  }

  /**
   * Implements FormatInterface::validateImport().
   */
  public function validateImport($imported_file) {
    $dom = new \DOMDocument();
    if (!$dom->loadHTML($imported_file)) {
      return FALSE;
    }
    $xml = simplexml_import_dom($dom);

    // Collect meta information.
    $meta_tags = $xml->xpath('//meta');
    $meta = array();
    foreach ($meta_tags as $meta_tag) {
      $meta[(string) $meta_tag['name']] = (string) $meta_tag['content'];
    }

    // Check required meta tags.
    foreach (array('JobID', 'languageSource', 'languageTarget') as $name) {
      if (!isset($meta[$name])) {
        return FALSE;
      }
    }

    // Attempt to load the job.
    if (!$job = Job::load($meta['JobID'])) {
      drupal_set_message(t('The imported file job id @file_id is not available.', array(
        '@file_id' => $meta['JobID'],
      )), 'error');
      return FALSE;
    }

    // Check language.
    if ($meta['languageSource'] != $job->getRemoteSourceLanguage() ||
        $meta['languageTarget'] != $job->getRemoteTargetLanguage()) {
      return FALSE;
    }

    // Validation successful.
    return $job;
  }

  /**
   * Get inner html of a DOM Element.
   *
   * @param \DOMElement $element
   *   The dom element.
   *
   * @return string
   *   The inner html as string
   */
  protected function extractInnerHtml(\DOMElement $element) {
    $inner_html = '';
    $children  = $element->childNodes;

    foreach ($children as $child) {
      $inner_html .= (isset($child->wholeText))
        ? $child->wholeText
        : $element->ownerDocument->saveHTML($child);
    }

    return $inner_html;
  }

}
