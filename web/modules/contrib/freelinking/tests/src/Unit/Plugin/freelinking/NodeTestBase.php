<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;

/**
 * Provides helper methods for freelinking node plugins.
 */
abstract class NodeTestBase extends UnitTestCase {

  /**
   * Get a language object to pass into the plugin.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   Return language object with default values.
   */
  static public function getDefaultLanguage() {
    return new Language(Language::$defaultValues);
  }

}
