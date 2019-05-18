<?php

namespace Drupal\Tests\box\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\box\Entity\BoxType;
use PHPUnit\Framework\TestCase;

/**
 * Provides methods to create box type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait BoxTypeCreationTrait {

  /**
   * Creates a custom content type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'id' => 'cardboard'.
   *
   * @return \Drupal\box\Entity\BoxType
   *   Created box type.
   */
  protected function createBoxType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (BoxType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += [
      'id' => $id,
      'name' => $id,
    ];
    $type = BoxType::create($values);
    $status = $type->save();

    if ($this instanceof TestCase) {
      $this->assertSame($status, SAVED_NEW, (new FormattableMarkup('Created box type %type.', ['%type' => $type->id()]))->__toString());
    }
    else {
      $this->assertEqual($status, SAVED_NEW, (new FormattableMarkup('Created box type %type.', ['%type' => $type->id()]))->__toString());
    }

    return $type;
  }

}
