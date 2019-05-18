<?php

namespace Drupal\Tests\permanent_entities\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\permanent_entities\Entity\PermanentEntity;
use Drupal\permanent_entities\Entity\PermanentEntityType;

/**
 * Checks that is impossible create or delete permanent entities from the UI.
 *
 * @group permanent_entities
 */
class CrudTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['permanent_entities'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin = $this->drupalCreateUser([], 'superadmin', TRUE);
    $this->drupalLogin($this->admin);
  }

  /**
   * Test that is not possible to create or delete permanent entities.
   */
  public function testNoAddOrDelete() {
    $this->drupalGet(Url::fromRoute('entity.permanent_entity.collection'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertRaw('Permanent Entities');
    $this->assertNoRaw('add');
    $this->assertNoRaw('delete');
  }

  /**
   * Test that for an existent entity is only possible to edit it.
   */
  public function testOnlyEdit() {
    $this->createEntityByCode('jupiter', 'Jupiter', 'planet');

    $this->drupalGet(Url::fromRoute('entity.permanent_entity.collection'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertRaw('Jupiter');
    $this->assertRaw('edit');
    $this->assertNoRaw('add');
    $this->assertNoRaw('delete');

    $this->drupalGet(
      Url::fromRoute(
        'entity.permanent_entity.edit_form',
        ['permanent_entity' => 'jupiter']
      )
    );

    $this->assertSession()->statusCodeEquals(200);
    $this->assertRaw('Jupiter');
    $save_button = $this->xpath('//input[@value="Save"]');
    $this->assertCount(1, $save_button, 'The Save button exists.');
    $this->assertNoRaw('delete');
  }

  /**
   * Creates a permanent entity.
   *
   * @param string $id
   *   The id of the entity.
   * @param string $label
   *   The label of the entity.
   * @param string $type
   *   The type or bundle of the entity.
   */
  protected function createEntityByCode(string $id, string $label, string $type) {
    if (!PermanentEntityType::load($type)) {
      PermanentEntityType::create(['label' => $type, 'id' => $type])->save();
    }

    PermanentEntity::create([
      'id' => $id,
      'label' => $label,
      'type' => $type,
    ])->save();
  }

}
