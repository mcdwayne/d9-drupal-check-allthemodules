<?php
namespace Drupal\cloudwords;

/**
 * Represents a language resource in Cloudwords.
 *
 * @author Douglas Kim <doug@cloudwords.com>
 * @since 1.0
 */
class CloudwordsLanguage {

  protected $display;
  protected $languageCode;

  /**
   * Constructor used to create a Cloudwords language
   *
   * - display: string The language display name
   * - languageCode: string The language code
   *
   * @param array $params The parameters used to initialize a language instance
   */
  public function __construct($params) {
    if (isset($params['display'])) {
      $this->display = $params['display'];
    }
    if (isset($params['languageCode'])) {
      $this->languageCode = $params['languageCode'];
    }
  }

  public function getDisplay() {
    return $this->display;
  }

  public function setDisplay($display) {
    $this->display = $display;
  }

  public function getLanguageCode() {
    return $this->languageCode;
  }

  public function setLanguageCode($languageCode) {
    $this->languageCode = $languageCode;
  }

}
