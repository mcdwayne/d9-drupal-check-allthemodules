<?php

namespace Drupal\Tests\box\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\box\Traits\BoxCreationTrait;
use Drupal\Tests\box\Traits\BoxTypeCreationTrait;

/**
 * Tests box validation constraints.
 *
 * @group box
 */
class BoxValidationTest extends EntityKernelTestBase {
  use BoxCreationTrait;
  use BoxTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['box'];

  /**
   * Set the default field storage backend for fields created during tests.
   */
  protected function setUp() {
    parent::setUp();

   $this->installEntitySchema('box');

    // Create a box type for testing.
    $this->createBoxType(['id' => 'wood', 'name' => 'Wooden']);
  }

  /**
   * Tests the box validation constraints.
   */
  public function testValidation() {
    $this->createUser();
    $box = $this->createBox(['type' => 'wood', 'title' => 'Test box', 'uid' => 1]);
    $violations = $box->validate();
    $this->assertEquals(0, count($violations), 'No violations when validating a default box.');

    $box->set('title', $this->randomString(256));
    $violations = $box->validate();
    $this->assertEquals(1, count($violations), 'Violation found when title is too long.');
    $this->assertEquals('title.0.value', $violations[0]->getPropertyPath());
    $this->assertEquals('<em class="placeholder">Title</em>: may not be longer than 255 characters.', $violations[0]->getMessage());

    $box->set('title', NULL);
    $violations = $box->validate();
    $this->assertEquals(1, count($violations), 'Violation found when title is not set.');
    $this->assertEquals('title', $violations[0]->getPropertyPath());
    $this->assertEquals('This value should not be null.', $violations[0]->getMessage());

    $box->set('title', '');
    $violations = $box->validate();
    $this->assertEquals(1, count($violations), 'Violation found when title is set to an empty string.');
    $this->assertEquals('title', $violations[0]->getPropertyPath());

    // Make the title valid again.
    $box->set('title', $this->randomString());
    // Save the box so that it gets an ID and a changed date.
    $box->save();
    // Set the changed date to something in the far past.
    $box->set('changed', 433918800);
    $violations = $box->validate();
    $this->assertEquals(1, count($violations), 'Violation found when changed date is before the last changed date.');
    $this->assertEquals('', $violations[0]->getPropertyPath());
    $this->assertEquals('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.', $violations[0]->getMessage());
  }

}
