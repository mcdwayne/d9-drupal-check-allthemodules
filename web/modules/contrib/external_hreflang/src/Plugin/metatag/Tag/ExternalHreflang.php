<?php

namespace Drupal\external_hreflang\Plugin\metatag\Tag;

use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * A new hreflang tag will be made available for each language.
 *
 * The meta tag's values will be based upon this annotation.
 *
 * @MetatagTag(
 *   id = "hreflang_external",
 *   label = @Translation("External Hreflang"),
 *   description = @Translation("This plugin will be cloned from these settings for each enabled language."),
 *   name = "hreflang_external",
 *   group = "advanced",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class ExternalHreflang extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function output() {
    $elements = [];

    try {
      $value = $this->value() ?? '';
      $hreflangs = self::getHrefLangsArrayFromString($value);
    }
    catch (\Exception $e) {
      \Drupal::logger('ExternalHreflang')->warning($this->t('Invalid value found in hreflang_external metatag.'));
    }

    foreach ($hreflangs ?? [] as $hreflang => $link) {
      $element = [];
      $element['#tag'] = 'link';
      $element['#attributes'] = [
        'rel' => 'alternate',
        'hreflang' => $hreflang,
        'href' => $link,
      ];

      $elements[] = $element;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'hreflang';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []) {
    $form = [
      '#type' => 'textarea',
      '#title' => $this->label(),
      '#default_value' => $this->value(),
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      '#description' => $this->description(),
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->t('Enter one link per hreflang, each separated by pipe (|) for code and url. Example: en-us|https://us.site.com/en[current-page:url:relative:en]');
  }

  /**
   * {@inheritdoc}
   */
  public static function validateTag(array &$element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#name']) ?? '';

    try {
      self::getHrefLangsArrayFromString($value);
    }
    catch (\Exception $e) {
      $form_state->setError($element, t('Invalid value in @name', ['@name' => $element['#title']]));
    }
  }

  /**
   * Convert string data from metatag to href langs array.
   *
   * @param string $value
   *   Value.
   *
   * @return array
   *   Array of Href Langs.
   *
   * @throws \Exception
   */
  public static function getHrefLangsArrayFromString(string $value = '') {
    $hreflangs = [];

    // Do nothing if empty.
    if (empty($value)) {
      return $hreflangs;
    }

    // Ensure we always have PHP_EOL as line separator.
    $value = str_replace("\r\n", PHP_EOL, $value);

    // Explode lines to get one array item per link.
    $value = array_filter(explode(PHP_EOL, $value));

    if (!is_array($value) || count($value) == 0) {
      throw new \Exception('Invalid value');
    }

    foreach ($value as $hreflang) {
      $hreflang = array_filter(explode('|', $hreflang));
      if (count($hreflang) !== 2) {
        throw new \Exception('Invalid value');
      }

      $hreflangs[$hreflang[0]] = $hreflang[1];
    }

    return $hreflangs;
  }

}
