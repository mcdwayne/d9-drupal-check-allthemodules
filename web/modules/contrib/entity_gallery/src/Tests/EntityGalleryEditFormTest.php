<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\entity_gallery\EntityGalleryInterface;
use Drupal\user\Entity\User;

/**
 * Create an entity gallery and test entity gallery edit functionality.
 *
 * @group entity_gallery
 */
class EntityGalleryEditFormTest extends EntityGalleryTestBase {

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The entity gallery storage.
   *
   * @var \Drupal\entity_gallery\EntityGalleryStorageInterface
   */
  protected $entityGalleryStorage;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['block', 'entity_gallery', 'datetime'];

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(array('edit own page entity galleries', 'create page entity galleries'));
    $this->adminUser = $this->drupalCreateUser(array('bypass entity gallery access', 'administer entity galleries'));
    $this->drupalPlaceBlock('local_tasks_block');

    $this->entityGalleryStorage = $this->container->get('entity.manager')->getStorage('entity_gallery');
  }

  /**
   * Checks entity gallery edit functionality.
   */
  public function testEntityGalleryEdit() {
    $this->drupalLogin($this->webUser);

    $title_key = 'title[0][value]';
    $gallery_items_key = 'entity_gallery_node[0][target_id]';
    $node = $this->drupalCreateNode();
    // Create entity gallery to edit.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$gallery_items_key] = $node->label();
    $this->drupalPostForm('gallery/add/page', $edit, t('Save'));

    // Check that the entity gallery exists in the database.
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit[$title_key]);
    $this->assertTrue($entity_gallery, 'Entity gallery found in database.');

    // Check that "edit" link points to correct page.
    $this->clickLink(t('Edit'));
    $this->assertUrl($entity_gallery->url('edit-form', ['absolute' => TRUE]));

    // Check that the title and gallery items fields are displayed with the
    // correct values. As you see the expected link text has no HTML, but we are
    // using
    $link_text = 'Edit<span class="visually-hidden">(active tab)</span>';
    // @todo Ideally assertLink would support HTML, but it doesn't.
    $this->assertRaw($link_text, 'Edit tab found and marked active.');
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');

    // Edit the content of the entity gallery.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$gallery_items_key] = $this->drupalCreateNode()->label();
    // Stay on the current page, without reloading.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check that the title and gallery items fields are displayed with the
    // updated values.
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$gallery_items_key], 'Gallery items displayed.');

    // Log in as a second administrator user.
    $second_web_user = $this->drupalCreateUser(array('administer entity galleries', 'edit any page entity galleries'));
    $this->drupalLogin($second_web_user);
    // Edit the same entity gallery, creating a new revision.
    $this->drupalGet("gallery/" . $entity_gallery->id() . "/edit");
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$gallery_items_key] = $this->drupalCreateNode()->label();
    $edit['revision'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));

    // Ensure that the entity gallery revision has been created.
    $revised_entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title[0][value]'], TRUE);
    $this->assertNotIdentical($entity_gallery->getRevisionId(), $revised_entity_gallery->getRevisionId(), 'A new revision has been created.');
    // Ensure that the entity gallery author is preserved when it was not
    // changed in the edit form.
    $this->assertIdentical($entity_gallery->getOwnerId(), $revised_entity_gallery->getOwnerId(), 'The entity gallery author has been preserved.');
    // Ensure that the revision authors are different since the revisions were
    // made by different users.
    $first_entity_gallery_version = entity_gallery_revision_load($entity_gallery->getRevisionId());
    $second_entity_gallery_version = entity_gallery_revision_load($revised_entity_gallery->getRevisionId());
    $this->assertNotIdentical($first_entity_gallery_version->getRevisionUser()->id(), $second_entity_gallery_version->getRevisionUser()->id(), 'Each revision has a distinct user.');
  }

  /**
   * Tests changing a entity gallery's "authored by" field.
   */
  public function testEntityGalleryEditAuthoredBy() {
    $this->drupalLogin($this->adminUser);

    // Create entity gallery to edit.
    $gallery_items_key = 'entity_gallery_node[0][target_id]';
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$gallery_items_key] = $this->drupalCreateNode()->label();
    $this->drupalPostForm('gallery/add/page', $edit, t('Save and publish'));

    // Check that the entity gallery was authored by the currently logged in
    // user.
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title[0][value]']);
    $this->assertIdentical($entity_gallery->getOwnerId(), $this->adminUser->id(), 'Entity gallery authored by admin user.');

    $this->checkVariousAuthoredByValues($entity_gallery, 'uid[0][target_id]');

    // Check that normal users cannot change the authored by information.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertNoFieldByName('uid[0][target_id]');

    // Now test with the Autcomplete (Tags) field widget.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityManager()->getStorage('entity_form_display')->load('entity_gallery.page.default');
    $widget = $form_display->getComponent('uid');
    $widget['type'] = 'entity_reference_autocomplete_tags';
    $widget['settings'] = [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => '',
    ];
    $form_display->setComponent('uid', $widget);
    $form_display->save();

    $this->drupalLogin($this->adminUser);

    // Save the entity gallery without making any changes.
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', [], t('Save and keep published'));
    $this->entityGalleryStorage->resetCache(array($entity_gallery->id()));
    $entity_gallery = $this->entityGalleryStorage->load($entity_gallery->id());
    $this->assertIdentical($this->webUser->id(), $entity_gallery->getOwner()->id());

    $this->checkVariousAuthoredByValues($entity_gallery, 'uid[target_id]');

    // Hide the 'authored by' field from the form.
    $form_display->removeComponent('uid')->save();

    // Check that saving the entity gallery without making any changes keeps the proper
    // author ID.
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', [], t('Save and keep published'));
    $this->entityGalleryStorage->resetCache(array($entity_gallery->id()));
    $entity_gallery = $this->entityGalleryStorage->load($entity_gallery->id());
    $this->assertIdentical($this->webUser->id(), $entity_gallery->getOwner()->id());
  }

  /**
   * Checks that the "authored by" works correctly with various values.
   *
   * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery
   *   An entity gallery object.
   * @param string $form_element_name
   *   The name of the form element to populate.
   */
  protected function checkVariousAuthoredByValues(EntityGalleryInterface $entity_gallery, $form_element_name) {
    // Try to change the 'authored by' field to an invalid user name.
    $edit = array(
      $form_element_name => 'invalid-name',
    );
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertRaw(t('There are no entities matching "%name".', array('%name' => 'invalid-name')));

    // Change the authored by field to an empty string, which should assign
    // authorship to the anonymous user (uid 0).
    $edit[$form_element_name] = '';
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Save and keep published'));
    $this->entityGalleryStorage->resetCache(array($entity_gallery->id()));
    $entity_gallery = $this->entityGalleryStorage->load($entity_gallery->id());
    $uid = $entity_gallery->getOwnerId();
    // Most SQL database drivers stringify fetches but entities are not
    // necessarily stored in a SQL database. At the same time, NULL/FALSE/""
    // won't do.
    $this->assertTrue($uid === 0 || $uid === '0', 'Entity gallery authored by anonymous user.');

    // Go back to the edit form and check that the correct value is displayed
    // in the author widget.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $anonymous_user = User::getAnonymousUser();
    $expected = $anonymous_user->label() . ' (' . $anonymous_user->id() . ')';
    $this->assertFieldByName($form_element_name, $expected, 'Authored by field displays the correct value for the anonymous user.');

    // Change the authored by field to another user's name (that is not
    // logged in).
    $edit[$form_element_name] = $this->webUser->getUsername();
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->entityGalleryStorage->resetCache(array($entity_gallery->id()));
    $entity_gallery = $this->entityGalleryStorage->load($entity_gallery->id());
    $this->assertIdentical($entity_gallery->getOwnerId(), $this->webUser->id(), 'Entity gallery authored by normal user.');
  }

}
