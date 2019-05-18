<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\entity_hierarchy\Plugin\Field\FieldWidget\EntityReferenceHierarchyAutocomplete;
use Drupal\node\Entity\Node;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;
use Drupal\workbench_moderation\Entity\ModerationState;

/**
 * Defines a class for testing the warnings on delete form.
 *
 * @group entity_hierarchy
 */
class ForwardRevisionNodeValidationTest extends BrowserTestBase {

  use EntityHierarchyTestTrait;
  use ContentTypeCreationTrait;

  const FIELD_NAME = 'parents';
  const ENTITY_TYPE = 'node';
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy',
    'system',
    'user',
    'dbal',
    'field',
    'node',
    'filter',
    'options',
    'workbench_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $content_type = $this->createContentType([
      'type' => 'article',
      'new_revision' => 1,
    ]);
    $content_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $content_type->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', array_keys(ModerationState::loadMultiple()));
    $content_type->save();
    $this->setupEntityHierarchyField(static::ENTITY_TYPE, 'article', static::FIELD_NAME);
    $this->getEntityFormDisplay(self::ENTITY_TYPE, 'article', 'default')
      ->setComponent(self::FIELD_NAME, [
        'type' => 'entity_reference_hierarchy_autocomplete',
        'weight' => 20,
        'settings' => ['hide_weight' => TRUE] + EntityReferenceHierarchyAutocomplete::defaultSettings(),
      ])
      ->save();
    $this->additionalSetup();
  }

  /**
   * Tests validation warning.
   */
  public function testValidationWarning() {
    $entities = $this->createChildEntities($this->parent->id());
    $first_child = reset($entities);
    $this->drupalLogin($this->drupalCreateUser(array_keys($this->container->get('user.permissions')
      ->getPermissions()), NULL, TRUE));
    $this->drupalGet($this->parent->toUrl('edit-form'));
    // Try to submit form with child as parent.
    $buttons = [
      'Save and Publish',
      'Save and Create New Draft',
      'Save and Archive',
      'Preview',
    ];
    foreach ($buttons as $button) {
      $this->submitForm([
        sprintf('%s[0][target_id][target_id]', static::FIELD_NAME) => sprintf('%s (%s)', $first_child->label(), $first_child->id()),
      ], $button);
      $assert = $this->assertSession();
      $assert->pageTextContains(sprintf('This entity (node: %s) cannot be referenced as it is either a child or the same entity.', $first_child->id()));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity($parentId, $label = 'Child 1', $weight = 0) {
    $values = [
      'type' => 'article',
      'title' => $label,
      'moderation_state' => 'published',
      'status' => 1,
    ];
    if ($parentId) {
      $values[static::FIELD_NAME] = [
        'target_id' => $parentId,
        'weight' => $weight,
      ];
    }
    $entity = $this->doCreateTestEntity($values);
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    $entity = Node::create($values);
    return $entity;
  }

}
