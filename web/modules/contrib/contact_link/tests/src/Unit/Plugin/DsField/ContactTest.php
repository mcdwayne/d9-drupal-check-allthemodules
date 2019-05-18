<?php

namespace Drupal\Tests\contact_link\Unit\Plugin\DsField;

use Drupal\Tests\UnitTestCase;
use Drupal\contact_link\Plugin\DsField\Contact;

/**
 * Contact Test.
 *
 * @group contact_link
 */
class ContactTest extends UnitTestCase {

  /**
   * Tests the constructor to ensure there are no errors in construction.
   */
  public function testConstructor() {
    $moduler_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $string_translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $contact = new Contact([], $this->randomMachineName(), [], $moduler_handler, $string_translation);
  }
}
