<?php

namespace Drupal\domain_language;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageDefault as BaseLanguageDefault;
use Drupal\Core\Language\LanguageInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;

/**
 * Class LanguageDefault
 * @package Drupal\domain_language
 */
class LanguageDefault extends BaseLanguageDefault {
  /**
   * The default language for domain.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $languageDomain;

  /**
   * @inheritDoc
   */
  public function set(LanguageInterface $language) {
    if (empty($this->languageDomain)) {
      parent::set($language);
    }
    else {
      $this->languageDomain = $language;
    }
  }

  /**
   * @inheritDoc
   */
  public function get() {
    // Try while domain is loaded.
    if (empty($this->languageDomain)) {
      // Load default language.
      $language = parent::get();

      try {
        /** @var DomainNegotiatorInterface $negotiator */
        $negotiator = \Drupal::service('domain.negotiator');

        // Try to load domain override.
        if ($domain = $negotiator->getActiveDomain()) {
          $default_langcode = \Drupal::config('system.site')->get('default_langcode');

          if ($language->getId() != $default_langcode) {
            $language = $this->getLanguage($default_langcode);

            // Todo: check if necessary ?
            \Drupal::languageManager()->reset();

            // Update default language in translation service.
            if ($translation = \Drupal::translation()) {
              $translation->setDefaultLangcode($language->getId());
            }
          }

          $this->languageDomain = $language;
        }
      } catch (ServiceCircularReferenceException $e) {
        // todo: Seems to occur only in command line.
      }

      return $language;
    }

    return $this->languageDomain;
  }

  /**
   * @param string $langcode
   *
   * @return \Drupal\Core\Language\Language
   */
  protected function getLanguage($langcode) {
    $config = \Drupal::config('language.entity.' . $langcode);
    $data = $config->get();
    $data['name'] = $data['label'];

    return new Language($data);
  }

}
