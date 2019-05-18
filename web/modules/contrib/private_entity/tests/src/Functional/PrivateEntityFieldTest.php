<?php

namespace Drupal\Tests\private_entity\Functional;

/**
 * Tests the creation of a private entity field on entity_test.
 *
 * @group private_entity
 */
class PrivateEntityFieldTest extends PrivateEntityTestBase {

  /**
   * The entity type id used for this test.
   */
  const ENTITY_TYPE_ID = 'entity_test';

  /**
   * The entity bundle used for this test.
   */
  const ENTITY_BUNDLE = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      // Entity test permissions.
      'view test entity',
      'administer entity_test content',
      'administer entity_test form display',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->attachField(self::ENTITY_TYPE_ID, self::ENTITY_BUNDLE);
  }

  /**
   * Tests the existence of the private entity field.
   *
   * @todo needs work
   */
  public function testPrivateEntityField() {
    // Test the entity creation form.
    $formPath = $this->getEntityTypeFormPath(self::ENTITY_TYPE_ID, self::ENTITY_BUNDLE, 'add');
    $this->drupalGet($formPath);
    // Make sure the "private_entity_default_widget" widget is on the markup.
    $fields = $this->xpath('//div[contains(@class, "field--widget-private-entity-default-widget") and @id="edit-field-private-wrapper"]');
    $this->assertEquals(1, count($fields));
    // Make sure that the widget is visible on the entity creation form.
    $this->assertSession()->fieldExists($this->fieldName . '[0][value]');

    // Test basic definition of private_entity field on entity save.
    $edit = [];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Make sure the entity was saved.
    preg_match('|' . self::ENTITY_TYPE_ID . '/manage/(\d+)|', $this->getSession()
      ->getCurrentUrl(), $match);
    $entityId = $match[1];
    $this->assertSession()
      ->pageTextContains(sprintf('%s %d has been created.', self::ENTITY_TYPE_ID, $entityId));
    // Make sure the private_entity field is in the output.
    $this->drupalGet('entity_test/' . $entityId);
    $fields = $this->xpath('//div[contains(@class, "field--type-private-entity")]');
    $this->assertEquals(1, count($fields));
  }

}
