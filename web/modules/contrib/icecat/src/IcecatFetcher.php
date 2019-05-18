<?php

namespace Drupal\icecat;

use haringsrob\Icecat\Model\Fetcher;
use haringsrob\Icecat\Model\Result;

/**
 * Extends the Icecat Fetcher.
 */
class IcecatFetcher extends Fetcher {

  /**
   * IcecatFetcher constructor.
   *
   * Inherits the parent constructor but populates the username and password.
   *
   * @param string $ean
   *   The ean to get.
   * @param string $language
   *   The language to get.
   */
  public function __construct($ean, $language = NULL) {
    // Language falls back to the default language.
    $language = !empty($language) ? $language : \Drupal::languageManager()->getDefaultLanguage()->getId();
    // Get the config for credentials.
    // @todo: secure? Not at all..
    $config = \Drupal::config('icecat.settings');
    parent::__construct($config->get('username'), $config->get('password'), $ean, $language);
  }

  /**
   * Gets the resultSet.
   *
   * @todo: This makes no sense, we have to update the library as well..
   *
   * @return \haringsrob\Icecat\Model\Result
   *   The result set object.
   */
  public function getResult() {
    $this->fetchBaseData();
    return new Result($this->getBaseData());
  }

}
