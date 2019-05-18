<?php

namespace Drupal\entity_gallery\Tests;

/**
 * Tests all the different buttons on the entity gallery form.
 *
 * @group entity_gallery
 */
class EntityGalleryFormButtonsTest extends EntityGalleryTestBase {

  use AssertButtonsTrait;

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create a user that has no access to change the state of the entity gallery.
    $this->webUser = $this->drupalCreateUser(array('create article entity galleries', 'edit own article entity galleries'));
    // Create a user that has access to change the state of the entity gallery.
    $this->adminUser = $this->drupalCreateUser(array('administer entity galleries', 'bypass entity gallery access'));
  }

  /**
   * Tests that the right buttons are displayed for saving entity galleries.
   */
  function testEntityGalleryFormButtons() {
    $entity_gallery_storage = $this->container->get('entity.manager')->getStorage('entity_gallery');
    // Log in as administrative user.
    $this->drupalLogin($this->adminUser);

    // Verify the buttons on an entity gallery add form.
    $this->drupalGet('gallery/add/article');
    $this->assertButtons(array(t('Save and publish'), t('Save as unpublished')));

    // Save the entity gallery and assert it's published after clicking
    // 'Save and publish'.
    $edit = array(
      'title[0][value]' => $this->randomString(),
      'entity_gallery_node[0][target_id]' => $this->drupalCreateNode(['type' => 'article'])->label(),
    );
    $this->drupalPostForm('gallery/add/article', $edit, t('Save and publish'));

    // Get the entity gallery.
    $entity_gallery_1 = $entity_gallery_storage->load(1);
    $this->assertTrue($entity_gallery_1->isPublished(), 'Entity gallery is published');

    // Verify the buttons on an entity gallery edit form.
    $this->drupalGet('gallery/' . $entity_gallery_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep published'), t('Save and unpublish')));

    // Save the entity gallery and verify it's still published after clicking
    // 'Save and keep published'.
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $entity_gallery_storage->resetCache(array(1));
    $entity_gallery_1 = $entity_gallery_storage->load(1);
    $this->assertTrue($entity_gallery_1->isPublished(), 'Entity gallery is published');

    // Save the entity gallery and verify it's unpublished after clicking
    // 'Save and unpublish'.
    $this->drupalPostForm('gallery/' . $entity_gallery_1->id() . '/edit', $edit, t('Save and unpublish'));
    $entity_gallery_storage->resetCache(array(1));
    $entity_gallery_1 = $entity_gallery_storage->load(1);
    $this->assertFalse($entity_gallery_1->isPublished(), 'Entity gallery is unpublished');

    // Verify the buttons on an unpublished entity gallery edit screen.
    $this->drupalGet('gallery/' . $entity_gallery_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep unpublished'), t('Save and publish')));

    // Create an entity gallery as a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Verify the buttons for a normal user.
    $this->drupalGet('gallery/add/article');
    $this->assertButtons(array(t('Save')), FALSE);

    // Create the entity gallery.
    $edit = array(
      'title[0][value]' => $this->randomString(),
      'entity_gallery_node[0][target_id]' => $this->drupalCreateNode(['type' => 'article'])->label(),
    );
    $this->drupalPostForm('gallery/add/article', $edit, t('Save'));
    $entity_gallery_2 = $entity_gallery_storage->load(2);
    $this->assertTrue($entity_gallery_2->isPublished(), 'Entity gallery is published');

    // Log in as an administrator and unpublish the entity gallery that just
    // was created by the normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('gallery/' . $entity_gallery_2->id() . '/edit', array(), t('Save and unpublish'));
    $entity_gallery_storage->resetCache(array(2));
    $entity_gallery_2 = $entity_gallery_storage->load(2);
    $this->assertFalse($entity_gallery_2->isPublished(), 'Entity gallery is unpublished');

    // Log in again as the normal user, save the entity gallery and verify
    // it's still unpublished.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $this->drupalPostForm('gallery/' . $entity_gallery_2->id() . '/edit', array(), t('Save'));
    $entity_gallery_storage->resetCache(array(2));
    $entity_gallery_2 = $entity_gallery_storage->load(2);
    $this->assertFalse($entity_gallery_2->isPublished(), 'Entity gallery is still unpublished');
    $this->drupalLogout();

    // Set article content type default to unpublished. This will change the
    // the initial order of buttons and/or status of the entity gallery when
    // creating an entity gallery.
    $fields = \Drupal::entityManager()->getFieldDefinitions('entity_gallery', 'article');
    $fields['status']->getConfig('article')
      ->setDefaultValue(FALSE)
      ->save();

    // Verify the buttons on an entity gallery add form for an administrator.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('gallery/add/article');
    $this->assertButtons(array(t('Save as unpublished'), t('Save and publish')));

    // Verify the entity gallery is unpublished by default for a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $edit = array(
      'title[0][value]' => $this->randomString(),
      'entity_gallery_node[0][target_id]' => $this->drupalCreateNode(['type' => 'article'])->label(),
    );
    $this->drupalPostForm('gallery/add/article', $edit, t('Save'));
    $entity_gallery_3 = $entity_gallery_storage->load(3);
    $this->assertFalse($entity_gallery_3->isPublished(), 'Entity gallery is unpublished');
  }
}
