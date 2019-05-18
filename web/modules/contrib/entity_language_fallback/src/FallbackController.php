<?php

namespace Drupal\entity_language_fallback;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;

class FallbackController implements FallbackControllerInterface {

  /**
   * @var string[]
   *
   * Array of fallback language codes.
   */
  protected $fallback_chain;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * TranslationJobHandler constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   An instance of the Language management service.
   * @param $entityTypeManager
   *   An instance of entity type manager service.
   */
  public function __construct(
    LanguageManagerInterface $languageManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fallback_chain = [];
  }


  /**
   * {@inheritdoc}
   */
  public function getFallbackChain($lang_code) {
    if (!$this->ensureFallbackChain($lang_code)) {
      return FALSE;
    }
    return $this->fallback_chain[$lang_code];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslation($lang_code, ContentEntityInterface $entity) {
    if (!$this->ensureFallbackChain($lang_code)) {
      return FALSE;
    }
    foreach ($this->fallback_chain[$lang_code] as $candidate) {
      if ($entity->hasTranslation($candidate)) {
        return $entity->getTranslation($candidate);
      }
    }
    return FALSE;
  }

  /**
   * Populate internal fallbackchain information if necessary.
   *
   * @return bool
   */
  protected function ensureFallbackChain($lang_code) {
    if (!isset($this->fallback_chain[$lang_code])) {
      $language = ConfigurableLanguage::load($lang_code);
      if (!$language) {
        return FALSE;
      }
      $this->fallback_chain[$lang_code] = $language->getThirdPartySetting('entity_language_fallback', 'fallback_langcodes', []);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslations(ContentEntityInterface $entity) {
    $translations = [];
    foreach($this->languageManager->getLanguages() as $langcode => $language) {
      if ($entity->hasTranslation($langcode)) {
        $translations[$langcode] = $entity->getTranslation($langcode);
      }
      elseif ($fallback = $this->getTranslation($langcode, $entity)) {
        $translations[$langcode] = $fallback;
      }
    }
    return $translations;
  }
}
