<?php

namespace Drupal\snowball_stemmer\Plugin\search_api\processor;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Plugin\search_api\processor\Stemmer as StemmerProcessor;
use Drupal\snowball_stemmer\Stemmer;

/**
 * Stems search terms.
 *
 * @SearchApiProcessor(
 *   id = "snowball_stemmer",
 *   label = @Translation("Snowball stemmer"),
 *   description = @Translation("Stems search terms, using <a href=""https://github.com/wamania/php-stemmer"">PHP snowball stemmer</a>, which supports multiple languages. For best results, use after tokenizing."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = 0,
 *     "preprocess_query" = 0,
 *   }
 * )
 */
class SnowballStemmer extends StemmerProcessor {

  /**
   * The stemmer service.
   *
   * @var \Drupal\snowball_stemmer\Stemmer|null
   */
  protected $stemmer;

  /**
   * The language manager.
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setStemmer($container->get('snowball_stemmer.stemmer'));
    $processor->setLanguageManager($container->get('language_manager'));

    return $processor;
  }

  /**
   * Retrieves the stemmer service.
   *
   * @return \Drupal\snowball_stemmer\Stemmer
   *   The stemmer service.
   */
  public function getStemmer() {
    $stemmer = $this->stemmer ?: \Drupal::service('snowball_stemmer.stemmer');
    // @todo allow multilingual overrides.
    $stemmer->setOverrides($this->configuration['exceptions']);
    return $stemmer;
  }

  /**
   * Sets the stemmer service.
   *
   * @param \Drupal\snowball_stemmer\Stemmer $stemmer
   *   The stemmer service.
   *
   * @return $this
   */
  public function setStemmer(Stemmer $stemmer) {
    $this->stemmer = $stemmer;
    return $this;
  }

  /**
   * Retrieves the language manager.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The language manager.
   */
  public function getLanguageManager() {
    return $this->languageManager ?: \Drupal::service('language_manager');
  }

  /**
   * Sets the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   *
   * @return $this
   */
  public function setLanguageManager(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      if ($this->getStemmer()->setLanguage($item->getLanguage())) {
        foreach ($item->getFields() as $name => $field) {
          if ($this->testField($name, $field)) {
            $this->processField($field);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    // Try the queries language, and if not fallback to the current site
    // language.
    if ($languages = $query->getLanguages()) {
      // Todo: What if there is more than one language?
      $language = reset($languages);
    }
    else {
      $language = $this->getLanguageManager()->getCurrentLanguage()->getId();
    }

    $keys = &$query->getKeys();
    if (isset($keys) && $this->getStemmer()->setLanguage($language)) {
      $this->processKeys($keys);
    }
    $conditions = $query->getConditionGroup();
    $this->processConditions($conditions->getConditions());
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // In the absence of the tokenizer, and/or HTML processor, this ensures
    // split words for stemming. Leaves strings in much the same state as
    // search api will for storage.
    $words = preg_split('/[^\p{L}\p{N}]+/u', strip_tags($value), -1, PREG_SPLIT_NO_EMPTY);
    $stemmed = array();
    foreach ($words as $word) {
      $stemmed[] = $this->stem($word);
    }
    $value = implode(' ', $stemmed);
  }

  /**
   * Actually stem word, if required.
   */
  protected function stem($word) {
    if (strlen($word)) {
      $word = $this->getStemmer()->stem($word);
    }

    return $word;
  }

}
