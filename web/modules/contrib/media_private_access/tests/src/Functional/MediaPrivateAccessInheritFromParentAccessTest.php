<?php

namespace Drupal\Tests\media_private_access\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\media_private_access\MediaPrivateAccessControlHandler;
use Drupal\node\Entity\Node;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests related to the Inherited-from-parent access mode.
 *
 * @group media_private_access
 */
class MediaPrivateAccessInheritFromParentAccessTest extends MediaFunctionalTestBase {

  use ContentTypeCreationTrait;

  /**
   * A non-admin test user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser1;

  /**
   * A non-admin test user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser2;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'block',
    'media_test_source',
    'entity_usage',
    'media_private_access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // This is needed to provide the user cache context for a below assertion.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->nonAdminUser1 = $this->drupalCreateUser([]);
    $this->nonAdminUser2 = $this->drupalCreateUser([]);
  }

  /**
   * Test the "inherited from parent" media access mode.
   */
  public function testInheritedFromParentMediaAccess() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $media_type = $this->createMediaType();

    // Create media.
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Generic media asset',
    ]);
    $media->save();
    $user_media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Authored media asset',
      'uid' => $this->nonAdminUser2->id(),
    ]);
    $user_media->save();

    // Set access mode on our type to be "Inherited from parent".
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/media-private-access');
    $assert_session->optionExists($media_type->label(), MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_INHERITED_FROM_PARENT);
    $page->selectFieldOption($media_type->label(), MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_INHERITED_FROM_PARENT);
    $page->pressButton('Save configuration');

    // At the standalone page only the admin and the owner have access to the
    // assets.
    $this->drupalGet('media/' . $media->id());
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(200);
    $this->drupalLogin($this->nonAdminUser1);
    $this->drupalGet('media/' . $media->id());
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(403);
    $this->drupalLogin($this->nonAdminUser2);
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(200);

    // Create a node rendering our media assets.
    $node_type = $this->createContentType();
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'label' => 'Media field',
      'field_storage' => $field_storage,
      'entity_type' => 'node',
      'bundle' => $node_type->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $media_type->id() => $media_type->id(),
          ],
        ],
      ],
    ]);
    $field->save();
    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $node_type->id() . '.default')
      ->setComponent('field_media', [
        'type' => 'entity_reference_entity_view',
      ])
      ->save();
    $node1 = Node::create([
      'title' => 'Node 1',
      'type' => $node_type->id(),
      'field_media' => [
        ['target_id' => $media->id()],
        ['target_id' => $user_media->id()],
      ],
    ]);
    $node1->save();

    // The admin can see both entities there.
    $this->drupalGet("/node/{$node1->id()}");
    $assert_session->elementContains('css', '.field--name-field-media', $media->label());
    $assert_session->elementContains('css', '.field--name-field-media', $user_media->label());
    // If the user can see the parent, they can also see the assets.
    $this->drupalLogin($this->nonAdminUser1);
    $this->drupalGet("/node/{$node1->id()}");
    $assert_session->elementContains('css', '.field--name-field-media', $media->label());
    $assert_session->elementContains('css', '.field--name-field-media', $user_media->label());
    // Access to the standalone entity is also granted for the user that can
    // see the parent.
    $this->drupalGet('media/' . $media->id());
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(200);

    // If a user has no longer access to the parent, the standalone access is
    // now denied.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    $role->revokePermission('access content')->save();
    $this->drupalGet('media/' . $media->id());
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(403);
  }

}
