<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity_gallery\Entity\EntityGalleryType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the entity gallery entity preview functionality.
 *
 * @group entity_gallery
 */
class PagePreviewTest extends EntityGalleryTestBase {

  use EntityReferenceTestTrait;
  use CommentTestTrait;

  /**
   * Enable the comment, entity gallery and taxonomy modules to test on the
   * preview.
   *
   * @var array
   */
  public static $modules = array('entity_gallery', 'taxonomy', 'comment', 'image', 'file', 'text', 'entity_gallery_test', 'menu_ui');

  /**
   * The name of the created field.
   *
   * @var string
   */
  protected $fieldName;

  protected function setUp() {
    parent::setUp();
    $this->addDefaultCommentField('entity_gallery', 'page');

    $web_user = $this->drupalCreateUser(array('edit own page entity galleries', 'create page entity galleries', 'administer menu'));
    $this->drupalLogin($web_user);

    // Add a vocabulary so we can test different view modes.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->randomMachineName(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
    ]);
    $vocabulary->save();

    $this->vocabulary = $vocabulary;

    // Add a term to the vocabulary.
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();

    $this->term = $term;

    // Create an image field.
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'entity_gallery',
      'type' => 'image',
      'settings' => [],
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => 'field_image',
      'label' => 'Images',
      'entity_type' => 'entity_gallery',
      'bundle' => 'page',
      'required' => FALSE,
      'settings' => [],
    ]);
    $field_config->save();

    // Create a field.
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    $handler_settings = array(
      'target_bundles' => array(
        $this->vocabulary->id() => $this->vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('entity_gallery', 'page', $this->fieldName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    entity_get_form_display('entity_gallery', 'page', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'entity_reference_autocomplete_tags',
      ))
      ->save();

    // Show on default display and teaser.
    entity_get_display('entity_gallery', 'page', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'entity_reference_label',
      ))
      ->save();
    entity_get_display('entity_gallery', 'page', 'teaser')
      ->setComponent($this->fieldName, array(
        'type' => 'entity_reference_label',
      ))
      ->save();

    entity_get_form_display('entity_gallery', 'page', 'default')
      ->setComponent('field_image', array(
        'type' => 'image_image',
        'settings' => [],
      ))
      ->save();

    entity_get_display('entity_gallery', 'page', 'default')
      ->setComponent('field_image')
      ->save();

    // Create a multi-value text field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test_multi',
      'entity_type' => 'entity_gallery',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'type' => 'text',
      'settings' => [
        'max_length' => 50,
      ]
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ])->save();

    entity_get_form_display('entity_gallery', 'page', 'default')
      ->setComponent('field_test_multi', array(
        'type' => 'text_textfield',
      ))
      ->save();

    entity_get_display('entity_gallery', 'page', 'default')
      ->setComponent('field_test_multi', array(
        'type' => 'string',
      ))
      ->save();
  }

  /**
   * Checks the entity gallery preview functionality.
   */
  function testPagePreview() {
    $title_key = 'title[0][value]';
    $gallery_items_key = 'entity_gallery_node[0][target_id]';
    $term_key = $this->fieldName . '[target_id]';

    // Fill in entity gallery creation form and preview entity gallery.
    $edit = array();
    $edit[$title_key] = '<em>' . $this->randomMachineName(8) . '</em>';
    $edit[$gallery_items_key] = $this->drupalCreateNode()->label();
    $edit[$term_key] = $this->term->getName();

    // Upload an image.
    $test_image = current($this->drupalGetTestFiles('image', 39325));
    $edit['files[field_image_0][]'] = drupal_realpath($test_image->uri);
    $this->drupalPostForm('gallery/add/page', $edit, t('Upload'));

    // Add an alt tag and preview the entity gallery.
    $this->drupalPostForm(NULL, ['field_image[0][alt]' => 'Picture of llamas'], t('Preview'));

    // Check that the preview is displaying the title, gallery items and term.
    $this->assertTitle(t('@title | Drupal', array('@title' => $edit[$title_key])), 'Basic page title is preview.');
    $this->assertEscaped($edit[$title_key], 'Title displayed and escaped.');
    $this->assertText($edit[$gallery_items_key], 'Gallery items displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');
    $this->assertLink(t('Back to content editing'));

    // Get the UUID.
    $url = parse_url($this->getUrl());
    $paths = explode('/', $url['path']);
    $view_mode = array_pop($paths);
    $uuid = array_pop($paths);

    // Switch view mode.
    entity_get_display('entity_gallery', 'page', 'teaser')
      ->save();

    $view_mode_edit = array('view_mode' => 'teaser');
    $this->drupalPostForm('gallery/preview/' . $uuid . '/full', $view_mode_edit, t('Switch'));
    $this->assertRaw('view-mode-teaser', 'View mode teaser class found.');

    // Check that the title, gallery item and term fields are displayed with the
    // values after going back to the content edit page.
    $this->clickLink(t('Back to content editing'));
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($gallery_items_key, $edit[$gallery_items_key], 'Gallery items field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');
    $this->assertFieldByName('field_image[0][alt]', 'Picture of llamas');
    $this->drupalPostAjaxForm(NULL, array(), array('field_test_multi_add_more' => t('Add another item')), NULL, array(), array(), 'entity-gallery-page-form');
    $this->assertFieldByName('field_test_multi[0][value]');
    $this->assertFieldByName('field_test_multi[1][value]');

    // Return to page preview to check everything is as expected.
    $this->drupalPostForm(NULL, array(), t('Preview'));
    $this->assertTitle(t('@title | Drupal', array('@title' => $edit[$title_key])), 'Basic page title is preview.');
    $this->assertEscaped($edit[$title_key], 'Title displayed and escaped.');
    $this->assertText($edit[$gallery_items_key], 'Gallery items displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');
    $this->assertLink(t('Back to content editing'));

    // Assert the content is kept when reloading the page.
    $this->drupalGet('gallery/add/page', array('query' => array('uuid' => $uuid)));
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($gallery_items_key, $edit[$gallery_items_key], 'Gallery items field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');

    // Save the entity gallery - this is a new POST, so we need to upload the image.
    $this->drupalPostForm('gallery/add/page', $edit, t('Upload'));
    $this->drupalPostForm(NULL, ['field_image[0][alt]' => 'Picture of llamas'], t('Save'));
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit[$title_key]);

    // Check the term was displayed on the saved entity gallery.
    $this->drupalGet('gallery/' . $entity_gallery->id());
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check the term appears again on the edit form.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertFieldByName($term_key, $edit[$term_key] . ' (' . $this->term->id() . ')', 'Term field displayed.');

    // Check with two new terms on the edit form, additionally to the existing
    // one.
    $edit = array();
    $newterm1 = $this->randomMachineName(8);
    $newterm2 = $this->randomMachineName(8);
    $edit[$term_key] = $this->term->getName() . ', ' . $newterm1 . ', ' . $newterm2;
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Preview'));
    $this->assertRaw('>' . $newterm1 . '<', 'First new term displayed.');
    $this->assertRaw('>' . $newterm2 . '<', 'Second new term displayed.');
    // The first term should be displayed as link, the others not.
    $this->assertLink($this->term->getName());
    $this->assertNoLink($newterm1);
    $this->assertNoLink($newterm2);

    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Save'));

    // Check with one more new term, keeping old terms, removing the existing
    // one.
    $edit = array();
    $newterm3 = $this->randomMachineName(8);
    $edit[$term_key] = $newterm1 . ', ' . $newterm3 . ', ' . $newterm2;
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Preview'));
    $this->assertRaw('>' . $newterm1 . '<', 'First existing term displayed.');
    $this->assertRaw('>' . $newterm2 . '<', 'Second existing term displayed.');
    $this->assertRaw('>' . $newterm3 . '<', 'Third new term displayed.');
    $this->assertNoText($this->term->getName());
    $this->assertLink($newterm1);
    $this->assertLink($newterm2);
    $this->assertNoLink($newterm3);

    // Check that editing an existing entity gallery after it has been previewed
    // and not saved doesn't remember the previous changes.
    $edit = array(
      $title_key => $this->randomMachineName(8),
    );
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Preview'));
    $this->assertText($edit[$title_key], 'New title displayed.');
    $this->clickLink(t('Back to content editing'));
    $this->assertFieldByName($title_key, $edit[$title_key], 'New title value displayed.');
    // Navigate away from the entity gallery without saving.
    $this->drupalGet('<front>');
    // Go back to the edit form, the title should have its initial value.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertFieldByName($title_key, $entity_gallery->label(), 'Correct title value displayed.');

    // Check with required preview.
    $entity_gallery_type = EntityGalleryType::load('page');
    $entity_gallery_type->setPreviewMode(DRUPAL_REQUIRED);
    $entity_gallery_type->save();
    $this->drupalGet('gallery/add/page');
    $this->assertNoRaw('edit-submit');
    $this->drupalPostForm('gallery/add/page', array($title_key => 'Preview', $gallery_items_key => $this->drupalCreateNode()->label()), t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->assertRaw('edit-submit');

    // Check that destination is remembered when clicking on preview. When going
    // back to the edit form and clicking save, we should go back to the
    // original destination, if set.
    $destination = 'entity_gallery';
    $this->drupalPostForm($entity_gallery->toUrl('edit-form'), [], t('Preview'), ['query' => ['destination' => $destination]]);
    $parameters = ['entity_gallery_preview' => $entity_gallery->uuid(), 'view_mode_id' => 'full'];
    $options = ['absolute' => TRUE, 'query' => ['destination' => $destination]];
    $this->assertUrl(Url::fromRoute('entity.entity_gallery.preview', $parameters, $options));
    $this->drupalPostForm(NULL, ['view_mode' => 'teaser'], t('Switch'));
    $this->clickLink(t('Back to content editing'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertUrl($destination);
    // Check that preview page works as expected without a destination set.
    $this->drupalPostForm($entity_gallery->toUrl('edit-form'), [], t('Preview'));
    $parameters = ['entity_gallery_preview' => $entity_gallery->uuid(), 'view_mode_id' => 'full'];
    $this->assertUrl(Url::fromRoute('entity.entity_gallery.preview', $parameters, ['absolute' => TRUE]));
    $this->drupalPostForm(NULL, ['view_mode' => 'teaser'], t('Switch'));
    $this->clickLink(t('Back to content editing'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertUrl($entity_gallery->toUrl());
    $this->assertResponse(200);

    // Assert multiple items can be added and are not lost when previewing.
    $test_image_1 = current($this->drupalGetTestFiles('image', 39325));
    $edit_image_1['files[field_image_0][]'] = drupal_realpath($test_image_1->uri);
    $edit_image_1['entity_gallery_node[0][target_id]'] = $this->drupalCreateNode()->label();
    $test_image_2 = current($this->drupalGetTestFiles('image', 39325));
    $edit_image_2['files[field_image_1][]'] = drupal_realpath($test_image_2->uri);
    $edit['field_image[0][alt]'] = 'Alt 1';

    $this->drupalPostForm('gallery/add/page', $edit_image_1, t('Upload'));
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->assertFieldByName('files[field_image_1][]');
    $this->drupalPostForm(NULL, $edit_image_2, t('Upload'));
    $this->assertNoFieldByName('files[field_image_1][]');

    $title = 'entity_gallery_test_title';
    $example_text_1 = 'example_text_preview_1';
    $example_text_2 = 'example_text_preview_2';
    $example_text_3 = 'example_text_preview_3';
    $this->drupalGet('gallery/add/page');
    $edit = [
      'title[0][value]' => $title,
      'field_test_multi[0][value]' => $example_text_1,
      'entity_gallery_node[0][target_id]' => $this->drupalCreateNode()->label(),
    ];
    $this->assertRaw('Storage is not set');
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->assertRaw('Storage is set');
    $this->assertFieldByName('field_test_multi[0][value]');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Basic page ' . $title . ' has been created.');
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($title);
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, [], array('field_test_multi_add_more' => t('Add another item')));
    $this->drupalPostAjaxForm(NULL, [], array('field_test_multi_add_more' => t('Add another item')));
    $edit = [
      'field_test_multi[1][value]' => $example_text_2,
      'field_test_multi[2][value]' => $example_text_3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->assertFieldByName('field_test_multi[0][value]', $example_text_1);
    $this->assertFieldByName('field_test_multi[1][value]', $example_text_2);
    $this->assertFieldByName('field_test_multi[2][value]', $example_text_3);

    // Now save the entity gallery and make sure all values got saved.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText($example_text_1);
    $this->assertText($example_text_2);
    $this->assertText($example_text_3);

    // Edit again, change the menu_ui settings and click on preview.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $edit = [
      'menu[enabled]' => TRUE,
      'menu[title]' => 'Changed title',
    ];
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->assertFieldChecked('edit-menu-enabled', 'Menu option is still checked');
    $this->assertFieldByName('menu[title]', 'Changed title', 'Menu link title is correct after preview');

    // Save, change the title while saving and make sure that it is correctly
    // saved.
    $edit = [
      'menu[enabled]' => TRUE,
      'menu[title]' => 'Second title change',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertFieldByName('menu[title]', 'Second title change', 'Menu link title is correct after saving');

  }

  /**
   * Checks the entity gallery preview functionality, when using revisions.
   */
  function testPagePreviewWithRevisions() {
    $title_key = 'title[0][value]';
    $gallery_items_key = 'entity_gallery_node[0][target_id]';
    $term_key = $this->fieldName . '[target_id]';
    // Force revision on "Basic page" content.
    $entity_gallery_type = EntityGalleryType::load('page');
    $entity_gallery_type->setNewRevision(TRUE);
    $entity_gallery_type->save();

    // Fill in entity gallery creation form and preview entity gallery.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$gallery_items_key] = $this->drupalCreateNode()->label();
    $edit[$term_key] = $this->term->id();
    $edit['revision_log[0][value]'] = $this->randomString(32);
    $this->drupalPostForm('gallery/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title, gallery items and term.
    $this->assertTitle(t('@title | Drupal', array('@title' => $edit[$title_key])), 'Basic page title is preview.');
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$gallery_items_key], 'Gallery items displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check that the title and gallery items fields are displayed with the correct
    // values after going back to the content edit page.
    $this->clickLink(t('Back to content editing'));    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($gallery_items_key, $edit[$gallery_items_key], 'Gallery items field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');

    // Check that the revision log field has the correct value.
    $this->assertFieldByName('revision_log[0][value]', $edit['revision_log[0][value]'], 'Revision log field displayed.');

    // Save the entity gallery after coming back from the preview page so we can
    // create a forward revision for it.
    $this->drupalPostForm(NULL, [], t('Save'));
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit[$title_key]);

    // Check that previewing a forward revision of an entity gallery works. This
    // can not be accomplished through the UI so we have to use API calls.
    // @todo Change this test to use the UI when we will be able to create
    // forward revisions in core.
    // @see https://www.drupal.org/node/2725533
    $entity_gallery->setNewRevision(TRUE);
    $entity_gallery->isDefaultRevision(FALSE);

    /** @var \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver */
    $controller_resolver = \Drupal::service('controller_resolver');
    $entity_gallery_preview_controller = $controller_resolver->getControllerFromDefinition('\Drupal\entity_gallery\Controller\EntityGalleryPreviewController::view');
    $entity_gallery_preview_controller($entity_gallery, 'full');
  }

  /**
   * Checks the entity gallery preview accessible for simultaneous entity gallery editing.
   */
  public function testSimultaneousPreview() {
    $title_key = 'title[0][value]';
    $entity_gallery = $this->drupalCreateEntityGallery(array());

    $edit = array($title_key => 'New page title');
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Preview'));
    $this->assertText($edit[$title_key]);

    $user2 = $this->drupalCreateUser(array('edit any page entity galleries'));
    $this->drupalLogin($user2);
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertFieldByName($title_key, $entity_gallery->label(), 'No title leaked from previous user.');

    $edit2 = array($title_key => 'Another page title');
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit2, t('Preview'));
    $this->assertUrl(\Drupal::url('entity.entity_gallery.preview', ['entity_gallery_preview' => $entity_gallery->uuid(), 'view_mode_id' => 'full'], ['absolute' => TRUE]));
    $this->assertText($edit2[$title_key]);
  }

}
