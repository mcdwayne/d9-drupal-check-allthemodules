<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * @group lightning_workflow
 * @group lightning_scheduler
 *
 * @requires inline_entity_form
 */
class InlineEntityFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'inline_entity_form',
    'lightning_scheduler',
    'lightning_workflow',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'alpha']);
    $this->createContentType(['type' => 'beta']);

    $field_storage = entity_create('field_storage_config', [
      'type' => 'entity_reference',
      'entity_type' => 'user',
      'settings' => [
        'target_type' => 'node',
      ],
      'field_name' => 'field_inline_entity',
    ]);
    $this->assertSame(SAVED_NEW, $field_storage->save());

    entity_create('field_config', [
      'field_storage' => $field_storage,
      'bundle' => 'user',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'alpha' => 'alpha',
          ],
        ],
      ],
      'label' => 'Inline entity',
    ])->save();

    entity_get_form_display('user', 'user', 'default')
      ->setComponent('field_inline_entity', [
        'type' => 'inline_entity_form_simple',
      ])
      ->save();

    $field_storage = entity_create('field_storage_config', [
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'settings' => [
        'target_type' => 'node',
      ],
      'field_name' => 'field_inline_entity',
    ]);
    $this->assertSame(SAVED_NEW, $field_storage->save());

    entity_create('field_config', [
      'field_storage' => $field_storage,
      'bundle' => 'alpha',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'beta' => 'beta',
          ],
        ],
      ],
      'label' => 'Inline entity',
    ])->save();

    entity_get_form_display('node', 'alpha', 'default')
      ->setComponent('field_inline_entity', [
        'type' => 'inline_entity_form_simple',
      ])
      ->save();

    /** @var \Drupal\workflows\Entity\Workflow $workflow */
    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $plugin */
    $plugin = $workflow->getTypePlugin();
    $plugin->addEntityTypeAndBundle('node', 'alpha');
    $plugin->addEntityTypeAndBundle('node', 'beta');
    $workflow->save();

    // Inline Entity Form has a problem referencing entities with other than
    // admin users.
    // @see https://www.drupal.org/project/inline_entity_form/issues/2753553
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Asserts that an inline entity form for field_inline_entity exists.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The inline entity form element.
   */
  private function assertInlineEntityForm() {
    return $this->assertSession()
      ->elementExists('css', '#edit-field-inline-entity-wrapper');
  }

  public function test() {
    $assert = $this->assertSession();

    // Test with an un-moderated host entity.
    $this->drupalGet('/user/' . $this->rootUser->id() . '/edit');
    $assert->statusCodeEquals(200);
    $inline_entity_form = $this->assertInlineEntityForm();
    $assert->fieldExists('Title', $inline_entity_form)->setValue('Kaboom?');
    $assert->fieldExists('field_inline_entity[0][inline_entity_form][moderation_state][0][state]');
    $assert->buttonExists('Save')->press();
    $assert->statusCodeEquals(200);

    // Test with a moderated host entity.
    $this->drupalGet('node/add/alpha');
    $assert->fieldExists('title[0][value]')->setValue('Foobar');
    $inline_entity_form = $this->assertInlineEntityForm();
    $assert->fieldExists('Title', $inline_entity_form)->setValue('Foobaz');

    $host_transitions_field = 'moderation_state[0][scheduled_transitions][data]';
    $inline_transitions_field = 'field_inline_entity[0][inline_entity_form][moderation_state][0][scheduled_transitions][data]';

    $transition_1 = Json::encode([
      [
        'state' => 'published',
        'when' => gmdate('c', time() + 100),
      ],
    ]);
    $transition_2 = Json::encode([
      [
        'state' => 'published',
        'when' => gmdate('c', time() + 200),
      ],
    ]);
    $assert->hiddenFieldExists($host_transitions_field)->setValue($transition_1);
    $assert->hiddenFieldExists($inline_transitions_field, $inline_entity_form)->setValue($transition_2);
    $assert->buttonExists('Save')->press();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $alpha = $node_storage->loadByProperties([
      'type' => 'alpha',
    ]);
    $beta = $node_storage->loadByProperties([
      'type' => 'beta',
    ]);
    $this->assertCount(1, $alpha);
    $this->assertCount(1, $beta);

    $this->drupalGet(reset($alpha)->toUrl('edit-form'));
    $assert->hiddenFieldValueEquals($host_transitions_field, $transition_1);

    $this->drupalGet(reset($beta)->toUrl('edit-form'));
    $assert->hiddenFieldValueEquals($host_transitions_field, $transition_2);
  }

}
