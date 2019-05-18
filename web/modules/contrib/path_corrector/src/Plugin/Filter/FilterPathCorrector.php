<?php
/**
 * @file
 * Contains \Drupal\path_corrector\Plugin\Filter\PathCorrector.
 */

namespace Drupal\path_corrector\Plugin\Filter;

use Drupal\filter\Annotation\Filter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "path_corrector",
 *   title = @Translation("Rewrite/correct links and URLs in content."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "tags" = {
 *       "a" = "0",
 *       "img" = "0",
 *     },
 *     "string_replacements" = "example.com|example.org"
 *   },
 *   weight = -10
 * )
 */
class FilterPathCorrector extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['tags'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Tags'),
      '#description' => $this->t('Which tags (HTML Elements) should be corrected'),
      '#default_value' => $this->settings['tags'],
      // NOTE: These are intentionally un-t()'d...
      '#options' => array(
        'a' => '&lt;a href="">',
        'img' => '&lt;img src="">',
      )
    );

    $form['string_replacements'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('String Replacements'),
      '#default_value' => $this->settings['string_replacements'],
      '#description' => $this->t('Enter a replacement "pairs", one replacement per line. <br />') .
        $this->t('Each replacements should be in pipe-separated form: <code>example.com|example.org</code><br />'),
    );

    return $form;
  }

  /**
   * Convert the replacements string (newline separated pairs (of which are pipe
   * separated) of URL parts to replace.
   *
   * @return array     Array of From => To pairs of replacements.
   */
  private function getStringReplacementPairs() {
    $str_replace_pairs = array();
    foreach (explode("\n", $this->settings['string_replacements']) as $replacement) {
      if (strpos($replacement, '|') !== FALSE) {
        list($from, $to) = explode('|', $replacement);
        $str_replace_pairs[trim($from)] = \Drupal\Component\Utility\UrlHelper::encodePath(trim($to));
      }
    }
    return $str_replace_pairs;
  }


  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Bail early if there are no replacements.
    if (empty($this->settings['string_replacements'])) {
      return $text;
    }
    $patterns = array();

    if (isset($this->settings['tags']['a'])) {
      $patterns[] = '/<a.+?href=["\'][^\'"]+["\']/';
    }
    if (isset($this->settings['tags']['img'])) {
      $patterns[] = '/<img.+?src=["\'][^\'"]+["\']/';
    }

    $string_replacement_pairs = $this->getStringReplacementPairs();
    $callback = function ($matches) use ($string_replacement_pairs) {
      return str_replace(array_keys($string_replacement_pairs), $string_replacement_pairs, $matches[0]);
    };

    $text = preg_replace_callback($patterns, $callback, $text);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $string_replacement_pairs = $this->getStringReplacementPairs();
    array_walk($string_replacement_pairs, function(&$value, $key) {
      $value = $key . ' » ' . $value;
    });
    return $this->t('The following paths will be corrected: @source_paths', array('@source_paths' => implode(', ', $string_replacement_pairs)));
  }
}