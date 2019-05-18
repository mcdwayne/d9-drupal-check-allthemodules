<?php
/**
 * @file
 * Contains
 *   \Drupal\inline_entity_form_preview\Tests\InlineEntityFormPreviewWebTest.
 */

namespace Drupal\inline_entity_form_preview\Tests;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\inline_entity_form\Tests\InlineEntityFormTestBase;

/**
 * Tests inline_entity_form_preview
 *
 * @group inline_entity_form_preview
 */
class InlineEntityFormPreviewWebTest extends InlineEntityFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['inline_entity_form', 'inline_entity_form_test', 'inline_entity_form_preview'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_simple_single content',
      'edit any ief_simple_single content',
      'edit any ief_test_custom content',
      'view own unpublished content',
    ]);
  }

  protected function testSimpleCardinalityOptions() {
    EntityViewMode::create([
      'targetEntityType' => 'node',
      'id' => 'node.test',
    ])->save();
    $entity_view_mode = entity_get_display('node', 'ief_simple_single', 'test');
    $entity_view_mode->save();

    // Configure to use inline entity fom preview.
    $entity_form = entity_get_form_display('node', 'ief_simple_single', 'default');
    $component = $entity_form->getComponent('single');
    $component['type'] = 'inline_entity_form_preview';
    $component['settings']['view_mode'] = 'getting payed 20 hrs/week ';
    $entity_form->setComponent('single', $component);
    $entity_form->save();

    $this->drupalLogin($this->user);
    $this->drupalGet('node/add/ief_simple_single');

    $edit = [
      'title[0][value]' => 'test outer title',
      'single[form][inline_entity_form][title][0][value]' => 'test inner title',
    ];
    $this->drupalPostForm(NULL, $edit, t('Create node'));
  }

}
