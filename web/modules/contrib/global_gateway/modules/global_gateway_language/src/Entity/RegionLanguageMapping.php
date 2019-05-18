<?php

namespace Drupal\global_gateway_language\Entity;

use Drupal\global_gateway\Entity\RegionMapping;

/**
 * Defines the flag mapping entity.
 *
 * @ConfigEntityType(
 *   id = "global_gateway_language_mapping",
 *   label = @Translation("Region-Language mapping"),
 *   config_prefix = "global_gateway_language_mapping",
 *   admin_permission = "administer region languages",
 *   handlers = {
 *     "access" = "Drupal\global_gateway_language\Security\RegionMappingAccessController",
 *     "form" = {}
 *   },
 *   entity_keys = {
 *     "id" = "region",
 *     "label" = "region"
 *   },
 *   links = {}
 * )
 */
class RegionLanguageMapping extends RegionMapping {

  protected $languages;

  /**
   * Returns language codes.
   *
   * @return string[]
   *   The array of language codes.
   */
  public function getLanguages() {
    return (array) $this->languages;
  }

  /**
   * Language codes setter.
   *
   * @param string[] $languages
   *   The array of language codes.
   *
   * @return $this
   */
  public function setLanguages(array $languages) {
    $this->languages = $languages;

    return $this;
  }

  /**
   * Adds single language code to the language stack.
   *
   * @param string $lang_code
   *   The language code.
   *
   * @return $this
   */
  public function addLanguage($lang_code) {
    $this->languages[$lang_code] = strtolower($lang_code);

    return $this;
  }

  /**
   * Removes single language code from the language stack.
   *
   * @param string $lang_code
   *   The language code.
   *
   * @return $this
   */
  public function removeLanguage($lang_code) {
    unset($this->languages[$lang_code]);
    return $this;
  }

}
