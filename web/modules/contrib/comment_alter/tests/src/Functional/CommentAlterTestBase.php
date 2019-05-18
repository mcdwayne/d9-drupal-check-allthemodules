<?php

namespace Drupal\Tests\comment_alter\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Utility\Unicode;

/**
 * Base class for Comment Alter test cases.
 *
 * @group comment_alter
 */
class CommentAlterTestBase extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['comment_alter', 'entity_test', 'comment', 'field'];

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The entity to use within tests.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * The parent entity type id.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The parent entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * {@inheritoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityType = 'entity_test_rev';
    // By default this bundle is there.
    $this->bundle = 'entity_test_rev';

    CommentType::create([
      'id' => 'comment',
      'label' => 'Comment',
      'description' => 'Comment type for Comment Alter',
      'target_entity_type_id' => $this->entityType,
    ])->save();
    // Add a comment field on entity_test_bundle.
    $this->addDefaultCommentField($this->entityType, $this->bundle);
    // Provide necessary permissions to the adminUser.
    $this->adminUser = $this->drupalCreateUser([
      'administer comments',
      'post comments',
      'access comments',
      'skip comment approval',
      'view test entity',
      'view test entity field',
      'administer entity_test content',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

  }

  /**
   * Adds a field to the entity_test entity type.
   *
   * @param string $field_type
   *   The field type name (Eg. text).
   * @param string $widget_type
   *   The widget name (Eg. text_textfield).
   * @param array $storage_settings
   *   (optional) A list of field storage settings that will be added to the
   *   field.
   * @param array $field_settings
   *   (optional) A list of instance settings that will be added to the
   *   field's instance.
   * @param boolean $comment_alter
   *   (optional) Option to enable/disable comment_alter for this field.
   *
   * @return string
   *   The name of the field that was created.
   */
  protected function addField($field_type, $widget_type, $storage_settings = [], $field_settings = [], $comment_alter = TRUE) {
    $field_name = Unicode::strtolower($this->randomMachineName() . '_field_name');

    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $this->entityType,
      'type' => $field_type,
    ] + $storage_settings)->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'settings' => $field_settings,
      'widget' => [
        'type' => $widget_type,
      ],
      'third_party_settings' => [
        'comment_alter' => [
          'comment_alter_enabled' => $comment_alter,
        ],
      ],
    ])->save();

    // By default the added field is hidden so enable it and set the widget
    // type.
    entity_get_form_display($this->entityType, $this->bundle, 'default')
      ->setComponent($field_name, [
        'type' => $widget_type,
      ])
      ->save();

    // Invalidate cache after selecting comment_alter option for our field.
    \Drupal::cache()->delete('comment_alter_fields:' . $this->entityType . ':' . $this->bundle);

    return $field_name;
  }

  /**
   * Creates an entity object with the provided values.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   */
  protected function createEntityObject($values = []) {
    // Create a test entity object for the entity_test_bundle.
    $random_label = $this->randomMachineName();
    $data = ['type' => $this->bundle, 'name' => $random_label] + $values;
    $this->entity = EntityTestRev::create($data);
    $this->entity->save();
  }

  /**
   * Asserts that alterable field is present on the comment form as expected.
   *
   * Checks if the alterable field is re-orderable and is present on the
   * comment forms.
   *
   * @param string $field_name
   *   Field added to the entity_test_bundle.
   */
  protected function assertAlterableField($field_name) {
    $this->drupalGet('comment/reply/' . $this->entityType . '/' . $this->entity->id() . '/comment');
    $comment_display_form = entity_get_form_display('comment', 'comment', 'default');
    $comment_field = $this->entityType . '_' . $this->bundle . '_comment_alter_' . $field_name;
    $this->assertSession()->fieldExists($field_name);
    // To make sure that site builder can reorder the fields from the UI.
    $this->assertTrue($comment_display_form->getComponent($comment_field), 'Alterable fields is present in the comment form display.');
  }

  /**
   * Posts a comment using the psuedo browser.
   *
   * @param array $comment_edit
   *   (optional) An array that gets added to the $edit array passed to
   *   $this->drupalPostForm().
   */
  protected function postComment($comment_edit = []) {
    // Populate the subject and body fields.
    $edit['comment_body[0][value]'] = $this->randomString();
    $edit['subject[0][value]'] = $this->randomString();
    $edit = array_merge($edit, $comment_edit);
    $this->drupalGet('comment/reply/' . $this->entityType . '/' . $this->entity->id() . '/comment');
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Get a comment alteration diffs from the current page.
   *
   * @return array
   *   An associative array keyed by field label pointing to an array which
   *   contains arrays which have two values, the original and new value for the
   *   given field.
   */
  protected function getCommentAlterations() {
    // Extract the values from the
    // '<table class="comment-alter-diff">...</table>'.
    $this->drupalGet('entity_test_rev/manage/' . $this->entity->id());
    $td_s = $this->xpath('//table[@class=:class]/tbody/tr/td', [':class' => 'comment-alter-diff']);
    $fields = [];
    $i = 0;
    foreach ($td_s as $td) {
      switch ($i % 4) {
        case 0:
          $field_name = $td->getText();
          $field_name = empty($field_name) ? $last_field_name : $field_name;
          break;

        case 1:
          $old_value = $td->getText();
          break;

        case 3:
          $new_value = $td->getText();
          break;
      }
      $i++;
      if (($i % 4) == 0) {
        $last_field_name = $field_name;
        $fields[$field_name][] = [$old_value, $new_value];
      }
    }
    return $fields;
  }

  /**
   * Asserts that a Comment Alter diff table on the current page is as expected.
   *
   * @param array $test
   *   An associative array with keys for the field name referring to arrays
   *   with exactly two values: the original and new value as human readable
   *   strings.
   */
  protected function assertCommentDiff($test) {
    $fields = $this->getCommentAlterations();
    // Compare the values passed in against what's on the page.
    foreach ($test as $field_name => $values) {
      $this->assertTrue(isset($fields[$field_name]), 'Comment alterable field is present in the comment alter diff.');
      foreach ($values as $index => $value) {
        $this->assertEquals($fields[$field_name][$index][0], $value[0], 'Comment alter diff original value matches.');
        $this->assertEquals($fields[$field_name][$index][1], $value[1], 'Comment alter diff changed value matches.');
      }
    }
  }

  /**
   * Asserts that nothing breaks when a revision of parent entity is deleted.
   */
  protected function assertRevisionDelete() {
    $old_revision_id = $this->entity->getRevisionId();
    // Create a new revision as deleteRevision() can't delete active revision.
    $this->entity->setNewRevision(TRUE);
    $this->entity->save();
    $this->assertNotEquals($old_revision_id, $this->entity->getRevisionId(), 'Make sure that two revisions are different.');
    // Now delete the old revision and see if we can open the entity page.
    \Drupal::entityTypeManager()->getStorage($this->entityType)->deleteRevision($old_revision_id);
    $content = $this->drupalGet('entity_test_rev/manage/' . $this->entity->id());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Asserts that added checkboxes on field config are working as expected.
   *
   * @param string $field_name
   *   The comment alterable field added to the entity_test_bundle.
   */
  protected function assertCommentSettings($field_name) {
    $comment_config = $this->entity->getFieldDefinition('comment');
    $comment_config->setThirdPartySetting('comment_alter', 'comment_alter_reply', TRUE)->save();
    $this->drupalGet('comment/reply/' . $this->entityType . '/' . $this->entity->id() . '/comment/1');
    $this->assertSession()->fieldExists($field_name);

    $alterable_field_config = $this->entity->getFieldDefinition($field_name);
    $alterable_field_config->setThirdPartySetting('comment_alter', 'comment_alter_hide', TRUE)->save();
    $this->drupalGet('entity_test_rev/manage/' . $this->entity->id());
    $this->assertSession()->pageTextContains(t('Changes are hidden'));
  }
}
