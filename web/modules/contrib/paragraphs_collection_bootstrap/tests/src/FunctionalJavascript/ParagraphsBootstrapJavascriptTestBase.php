<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Base class for paragraphs collection bootstrap Javascript functional tests.
 *
 * @package Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript
 */
abstract class ParagraphsBootstrapJavascriptTestBase extends JavascriptTestBase {

  use LoginAdminTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'field_ui',
    'block',
    'link',
    'paragraphs_collection_bootstrap',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $node_type = NodeType::create([
      'type' => 'paragraphed_test',
      'name' => 'paragraphed_test',
    ]);
    $node_type->save();

    // Add paragraph field to the paragraphed_test.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_paragraphs',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'paragraphed_test',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'paragraphed_test',
      'mode' => 'default',
      'edit_mode' => 'open',
      'status' => TRUE,
    ])->setComponent('field_paragraphs', ['type' => 'paragraphs']);
    $form_display->save();

    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'paragraphed_test',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_paragraphs', ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();
  }

}
