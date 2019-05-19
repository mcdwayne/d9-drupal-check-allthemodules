<?php

namespace Drupal\Tests\social_simple\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Provides common helper methods for Social simple module tests.
 */
abstract class TestSocialSimpleTestBase extends BrowserTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'block',
    'taxonomy',
    'node',
    'field',
    'field_ui',
    'taxonomy',
    'social_simple',
  ];

  /**
   * User with admin permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Advanced  User with permission on social share per node.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $advancedUser;

  /**
   * Standard User without permission on social share per node.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * Entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $article;

  /**
   * A vocabulary created.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * A term created.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * A term created.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create vocabulary and terms.
    $this->vocabulary = $this->createVocabulary();
    $this->term1 = $this->createTerm($this->vocabulary);
    $this->term2 = $this->createTerm($this->vocabulary);

    if ($this->profile != 'standard') {
      $this->createContentType(['type' => 'article', 'name' => 'Article']);
      $field_name = 'field_tags';
      $handler_settings = [
        'target_bundles' => [
          $this->vocabulary->id() => $this->vocabulary->id(),
        ],
        'auto_create' => TRUE,
      ];
      $this->createEntityReferenceField('node', 'article', $field_name, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
      $this->setComponentFormDisplay('node.article.default', 'node', 'article', $field_name, 'entity_reference_autocomplete', []);
    }

    $title = 'Article1';
    $values = [
      'type' => 'article',
      'title' => $title,
      'body' => [
        'value' => 'Content body for ' . $title,
      ],
    ];
    $this->article = $this->createNode($values);

  }

  /**
   * Set a component in a View display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $mode
   *   The mode name.
   * @param string $field_name
   *   The field name to set.
   */
  protected function setComponentViewDisplay($form_display_id, $entity_type, $bundle, $mode, $field_name) {
    // Set entity view display.
    $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    if (!$this->viewDisplay) {
      EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    }
    if ($this->viewDisplay instanceof EntityViewDisplayInterface) {
      $this->viewDisplay->setComponent($field_name)->save();
    }

  }

  /**
   * Remove a component in a View display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $mode
   *   The mode name.
   * @param string $field_name
   *   The field name to set.
   */
  protected function removeComponentViewDisplay($form_display_id, $entity_type, $bundle, $mode, $field_name) {
    // Set entity view display.
    $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    if (!$this->viewDisplay) {
      EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    }
    if ($this->viewDisplay instanceof EntityViewDisplayInterface) {
      $this->viewDisplay->removeComponent($field_name)->save();
    }

  }

  /**
   * Set the widget for a component in a form display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name to set.
   * @param string $widget_id
   *   The widget id to set.
   * @param array $settings
   *   The settings of widget.
   * @param string $mode
   *   The mode name.
   */
  protected function setComponentFormDisplay($form_display_id, $entity_type, $bundle, $field_name, $widget_id, $settings, $mode = 'default') {
    // Set article's form display.
    $this->formDisplay = EntityFormDisplay::load($form_display_id);

    if (!$this->formDisplay) {
      EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->formDisplay = EntityFormDisplay::load($form_display_id);
    }
    if ($this->formDisplay instanceof EntityFormDisplayInterface) {
      $this->formDisplay->setComponent($field_name, [
        'type' => $widget_id,
        'settings' => $settings,
      ])->save();
    }
  }

}
