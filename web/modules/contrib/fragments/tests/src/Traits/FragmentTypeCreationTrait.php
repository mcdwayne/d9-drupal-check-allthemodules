<?php

namespace Drupal\Tests\fragments\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\fragments\Entity\FragmentType;
use PHPUnit\Framework\TestCase;

/**
 * Provides methods to create fragment type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait FragmentTypeCreationTrait {

  /**
   * Creates a custom fragment type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\fragments\Entity\FragmentType
   *   Created fragment type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When a problem occurred saving the new fragment type.
   */
  protected function createFragmentType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['id'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (FragmentType::load($id));
    }
    else {
      $id = $values['id'];
    }
    $values += [
      'id' => $id,
      'name' => $id,
    ];
    $type = FragmentType::create($values);
    $status = $type->save();

    if ($this instanceof TestCase) {
      $this->assertSame($status, SAVED_NEW, (new FormattableMarkup('Created fragment type %type.', ['%type' => $type->id()]))->__toString());
    }
    else {
      $this->assertEqual($status, SAVED_NEW, (new FormattableMarkup('Created fragment type %type.', ['%type' => $type->id()]))->__toString());
    }

    return $type;
  }

}
