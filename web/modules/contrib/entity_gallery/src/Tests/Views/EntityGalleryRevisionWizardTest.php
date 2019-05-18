<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\entity_gallery\GalleryTypeCreationTrait;
use Drupal\views\Tests\Wizard\WizardTestBase;
use Drupal\views\Views;

/**
 * Tests the wizard with entity_gallery_revision as base table.
 *
 * @group entity_gallery
 * @see \Drupal\entity_gallery\Plugin\views\wizard\EntityGalleryRevision
 */
class EntityGalleryRevisionWizardTest extends WizardTestBase {

  use GalleryTypeCreationTrait {
    createGalleryType as drupalCreateGalleryType;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery');

  protected function setUp() {
    parent::setUp();

    // Create and log in a user with administer views permission.
    $views_admin = $this->drupalCreateUser(['administer views', 'administer blocks', 'bypass node access', 'bypass entity gallery access', 'access user profiles', 'view all revisions']);
    $this->drupalLogin($views_admin);
  }

  /**
   * Tests creating an entity gallery revision view.
   */
  public function testViewAdd() {
    $this->drupalCreateGalleryType(array('type' => 'article'));
    // Create two entity galleries with two revision.
    $entity_gallery_storage = \Drupal::entityManager()->getStorage('entity_gallery');
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $entity_gallery_storage->create(array('title' => $this->randomString(), 'type' => 'article', 'created' => REQUEST_TIME + 40));
    $entity_gallery->save();

    $entity_gallery = $entity_gallery->createDuplicate();
    $entity_gallery->setNewRevision();
    $entity_gallery->created->value = REQUEST_TIME + 20;
    $entity_gallery->save();

    $entity_gallery = $entity_gallery_storage->create(array('title' => $this->randomString(), 'type' => 'article', 'created' => REQUEST_TIME + 30));
    $entity_gallery->save();

    $entity_gallery = $entity_gallery->createDuplicate();
    $entity_gallery->setNewRevision();
    $entity_gallery->created->value = REQUEST_TIME + 10;
    $entity_gallery->save();

    $view = array();
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[wizard_key]'] = 'entity_gallery_revision';
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $view_storage_controller = \Drupal::entityManager()->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $view_storage_controller->load($view['id']);

    $this->assertEqual($view->get('base_table'), 'entity_gallery_field_revision');

    $executable = Views::executableFactory()->get($view);
    $this->executeView($executable);

    $this->assertIdenticalResultset($executable, array(array('vid' => 1), array('vid' => 3), array('vid' => 2), array('vid' => 4)),
      array('vid' => 'vid'));
  }

}
