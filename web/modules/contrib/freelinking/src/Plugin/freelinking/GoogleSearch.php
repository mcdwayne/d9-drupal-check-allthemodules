<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;

/**
 * Freelinking google search plugin.
 *
 * @Freelinking(
 *   id = "google",
 *   title = @Translation("Google Search"),
 *   weight = 1,
 *   hidden = false,
 *   settings = {  }
 * )
 */
class GoogleSearch extends FreelinkingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/^google$/';
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Search google for the specified terms.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $searchString = str_replace(' ', '+', $target['dest']);
    return self::createRenderArray($searchString, $target['text'], $target['language'], $this->getTip());
  }

  /**
   * Create a google search link.
   *
   * @param string $searchString
   *   The search string to use, which should already have spaces replaced with
   *   plus signs.
   * @param string $text
   *   The text to display for the link.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   The language code.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $tip
   *   The tooltip to use.
   *
   * @return array
   *   A valid link render array.
   */
  public static function createRenderArray($searchString, $text, $language, TranslatableMarkup $tip) {
    // @todo Fix so that language is typed.
    $lang = NULL === $language || LanguageInterface::LANGCODE_DEFAULT === $language->getId() ? 'en' : $language->getId();

    return [
      '#type' => 'link',
      '#title' => 'Google Search ' . $text,
      '#url' => Url::fromUri(
        'https://google.com/search',
        [
          'absolute' => TRUE,
          'query' => ['q' => $searchString, 'hl' => $lang],
          'language' => $language,
        ]
      ),
      '#attributes' => array(
        'title' => $tip,
      ),
    ];
  }

}
