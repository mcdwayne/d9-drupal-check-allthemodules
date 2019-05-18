<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\entity_gallery\Entity\EntityGalleryType;
use Drupal\Core\Url;

/**
 * Ensures that entity gallery type functions work correctly.
 *
 * @group entity_gallery
 */
class EntityGalleryTypeTest extends EntityGalleryTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Ensures that entity gallery type functions (entity_gallery_type_get_*) work
   * correctly.
   *
   * Load available entity gallery types and validate the returned data.
   */
  function testEntityGalleryTypeGetFunctions() {
    $entity_gallery_types = EntityGalleryType::loadMultiple();
    $entity_gallery_names = entity_gallery_type_get_names();

    $this->assertTrue(isset($entity_gallery_types['article']), 'Entity gallery type article is available.');
    $this->assertTrue(isset($entity_gallery_types['page']), 'Entity gallery type basic page is available.');

    $this->assertEqual($entity_gallery_types['article']->label(), $entity_gallery_names['article'], 'Correct entity gallery type base has been returned.');

    $article = EntityGalleryType::load('article');
    $this->assertEqual($entity_gallery_types['article'], $article, 'Correct node type has been returned.');
    $this->assertEqual($entity_gallery_types['article']->label(), $article->label(), 'Correct entity gallery type name has been returned.');
  }

  /**
   * Tests creating a entity gallery type programmatically and via a form.
   */
  function testEntityGalleryTypeCreation() {
    // Create an entity gallery type programmatically.
    $type = $this->drupalCreateGalleryType();

    $type_exists = (bool) EntityGalleryType::load($type->id());
    $this->assertTrue($type_exists, 'The new entity gallery type has been created in the database.');

    // Log in a test user.
    $web_user = $this->drupalCreateUser(array('create ' . $type->label() . ' entity galleries'));
    $this->drupalLogin($web_user);

    $this->drupalGet('gallery/add/' . $type->id());
    $this->assertResponse(200, 'The new entity gallery type can be accessed at gallery/add.');

    // Create an entity gallery type via the user interface.
    $web_user = $this->drupalCreateUser(array('bypass entity gallery access', 'administer entity gallery types'));
    $this->drupalLogin($web_user);

    $this->drupalGet('gallery/add');
    $this->assertCacheTag('config:entity_gallery_type_list');
    $this->assertCacheContext('user.permissions');
    $elements = $this->cssSelect('dl dt');
    $this->assertEqual(3, count($elements));

    $edit = array(
      'name' => 'foo',
      'title_label' => 'title for foo',
      'type' => 'foo',
      'gallery_type' => 'node',
    );
    $this->drupalPostForm('admin/structure/gallery-types/add', $edit, t('Save and manage fields'));
    $type_exists = (bool) EntityGalleryType::load('foo');
    $this->assertTrue($type_exists, 'The new entity gallery type has been created in the database.');

    $this->drupalGet('gallery/add');
    $elements = $this->cssSelect('dl dt');
    $this->assertEqual(4, count($elements));
  }

  /**
   * Tests editing an entity gallery type using the UI.
   */
  function testEntityGalleryTypeEditing() {
    $web_user = $this->drupalCreateUser(array('bypass entity gallery access', 'administer entity gallery types', 'administer entity_gallery fields'));
    $this->drupalLogin($web_user);

    // Verify that title field is displayed.
    $this->drupalGet('gallery/add/page');
    $this->assertRaw('Title', 'Title field was found.');

    // Rename the title field.
    $edit = array(
      'title_label' => 'Foo',
    );
    $this->drupalPostForm('admin/structure/gallery-types/manage/page', $edit, t('Save gallery type'));

    $this->drupalGet('gallery/add/page');
    $this->assertRaw('Foo', 'New title label was displayed.');
    $this->assertNoRaw('Title', 'Old title label was not displayed.');

    // Change the name and the description.
    $edit = array(
      'name' => 'Bar',
      'description' => 'Lorem ipsum.',
    );
    $this->drupalPostForm('admin/structure/gallery-types/manage/page', $edit, t('Save gallery type'));

    $this->drupalGet('gallery/add');
    $this->assertRaw('Bar', 'New name was displayed.');
    $this->assertRaw('Lorem ipsum', 'New description was displayed.');
    $this->clickLink('Bar');
    $this->assertRaw('Foo', 'Title field was found.');

    // Change the name through the API
    /** @var \Drupal\entity_gallery\EntityGalleryTypeInterface $entity_gallery_type */
    $entity_gallery_type = EntityGalleryType::load('page');
    $entity_gallery_type->set('name', 'NewBar');
    $entity_gallery_type->save();

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $entity_gallery_bundles = $bundle_info->getBundleInfo('entity_gallery');
    $this->assertEqual($entity_gallery_bundles['page']['label'], 'NewBar', 'Entity gallery type bundle cache is updated');
  }

  /**
   * Tests deleting an entity gallery type that still has entity galleries.
   */
  function testEntityGalleryTypeDeletion() {
    // Create an entity gallery type programmatically.
    $type = $this->drupalCreateGalleryType();

    // Log in a test user.
    $web_user = $this->drupalCreateUser(array(
      'bypass entity gallery access',
      'administer entity gallery types',
    ));
    $this->drupalLogin($web_user);

    // Add a new entity gallery of this type.
    $entity_gallery = $this->drupalCreateEntityGallery(array('type' => $type->id()));
    // Attempt to delete the entity gallery type, which should not be allowed.
    $this->drupalGet('admin/structure/gallery-types/manage/' . $type->label() . '/delete');
    $this->assertRaw(
      t('%type is used by 1 gallery on your site. You can not remove this gallery type until you have removed all of the %type galleries.', array('%type' => $type->label())),
      'The gallery type will not be deleted until all entity galleries of that type are removed.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The entity gallery type deletion confirmation form is not available.');

    // Delete the entity gallery.
    $entity_gallery->delete();
    // Attempt to delete the entity gallery type, which should now be allowed.
    $this->drupalGet('admin/structure/gallery-types/manage/' . $type->label() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the entity gallery type %type?', array('%type' => $type->label())),
      'The gallery type is available for deletion.'
    );
    $this->assertText(t('This action cannot be undone.'), 'The entity gallery type deletion confirmation form is available.');

    // Test that a locked entity gallery type could not be deleted.
    $this->container->get('module_installer')->install(array('entity_gallery_test_config'));
    // Lock the default entity gallery type.
    $locked = \Drupal::state()->get('entity_gallery.type.locked');
    $locked['default'] = 'default';
    \Drupal::state()->set('entity_gallery.type.locked', $locked);
    // Call to flush all caches after installing the forum module in the same
    // way installing a module through the UI does.
    $this->resetAll();
    $this->drupalGet('admin/structure/gallery-types/manage/default');
    $this->assertNoLink(t('Delete'));
    $this->drupalGet('admin/structure/gallery-types/manage/default/delete');
    $this->assertResponse(403);
    $this->container->get('module_installer')->uninstall(array('entity_gallery_test_config'));
    $this->container = \Drupal::getContainer();
    unset($locked['default']);
    \Drupal::state()->set('entity_gallery.type.locked', $locked);
    $this->drupalGet('admin/structure/gallery-types/manage/default');
    $this->clickLink(t('Delete'));
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertFalse((bool) EntityGalleryType::load('default'), 'Entity gallery type with machine default deleted.');
  }

  /**
   * Tests Field UI integration for entity gallery types.
   */
  public function testEntityGalleryTypeFieldUiPermissions() {
    // Create an admin user who can only manage entity gallery fields.
    $admin_user_1 = $this->drupalCreateUser(array('administer entity gallery types', 'administer entity_gallery fields'));
    $this->drupalLogin($admin_user_1);

    // Test that the user only sees the actions available to him.
    $this->drupalGet('admin/structure/gallery-types');
    $this->assertLinkByHref('admin/structure/gallery-types/manage/article/fields');
    $this->assertNoLinkByHref('admin/structure/gallery-types/manage/article/display');

    // Create another admin user who can manage entity gallery fields display.
    $admin_user_2 = $this->drupalCreateUser(array('administer entity gallery types', 'administer entity_gallery display'));
    $this->drupalLogin($admin_user_2);

    // Test that the user only sees the actions available to him.
    $this->drupalGet('admin/structure/gallery-types');
    $this->assertNoLinkByHref('admin/structure/gallery-types/manage/article/fields');
    $this->assertLinkByHref('admin/structure/gallery-types/manage/article/display');
  }

  /**
   * Tests for when there are no entity gallery types defined.
   */
  public function testEntityGalleryTypeNoEntityGalleryType() {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $this->assertEqual(2, count($bundle_info->getBundleInfo('entity_gallery')), 'The bundle information service has 2 bundles for the Entity gallery entity type.');
    $web_user = $this->drupalCreateUser(['administer entity gallery types']);
    $this->drupalLogin($web_user);

    // Delete 'article' bundle.
    $this->drupalPostForm('admin/structure/gallery-types/manage/article/delete', [], t('Delete'));
    // Delete 'page' bundle.
    $this->drupalPostForm('admin/structure/gallery-types/manage/page/delete', [], t('Delete'));

    // Navigate to entity gallery type administration screen
    $this->drupalGet('admin/structure/gallery-types');
    $this->assertRaw(t('No gallery types available. <a href=":link">Add gallery type</a>.', [
        ':link' => Url::fromRoute('entity_gallery.type_add')->toString()
      ]), 'Empty text when there are no entity gallery types in the system is correct.');

    $bundle_info->clearCachedBundles();
    $this->assertEqual(0, count($bundle_info->getBundleInfo('entity_gallery')), 'The bundle information service has 0 bundles for the Entity gallery entity type.');
  }

}
