<?php

namespace Drupal\Tests\multiversion\Functional;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the UninstallTest class.
 *
 * @group multiversion
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var array
   */
  protected $entityTypes = [
    'node' => ['type' => 'article', 'title' => 'foo'],
    //    'taxonomy_term' => ['name' => 'A term', 'vid' => 123],
    //    'comment' => [
    //      'entity_type' => 'node',
    //      'field_name' => 'comment',
    //      'subject' => 'How much wood would a woodchuck chuck',
    //      'comment_type' => 'comment',
    //    ],
    //    'block_content' => [
    //      'info' => 'New block',
    //      'type' => 'basic',
    //    ],
    'menu_link_content' => [
      'menu_name' => 'menu_test',
      'bundle' => 'menu_link_content',
      'link' => [['uri' => 'user-path:/']],
    ],
    'shortcut' => [
      'shortcut_set' => 'default',
      'title' => 'Llama',
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin']],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'multiversion',
    'key_value',
    'serialization',
    'conflict',
    'node',
    'comment',
    'menu_link_content',
    'block_content',
    'taxonomy',
    'shortcut',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('multiversion.manager')->enableEntityTypes();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests uninstalling the module when there is existing content.
   */
  public function testDisableWithExistingContent() {
    $entity_type_manager = $this->container->get('entity_type.manager');
    foreach ($this->entityTypes as $entity_type_id => $values) {
      $storage = $entity_type_manager->getStorage($entity_type_id);
      $count = 2;
      for ($i = 0; $i < $count; $i++) {
        $storage->create($values)->save();
      }
      $count_before[$entity_type_id] = $count;
    }
    /** @var \Drupal\multiversion\MultiversionManagerInterface $manager */
    $manager = $this->container->get('multiversion.manager');
    // Disable entity types.
    $manager->disableEntityTypes();
    // Delete workspace entities before uninstall.
    $storage = $entity_type_manager->getStorage('workspace');
    $entities = $storage->loadMultiple();
    $storage->delete($entities);

    // Uninstall Multiversion.
    $this->container->get('module_installer')->uninstall(['multiversion']);

    // Check that applying updates worked.
    $this->assertFalse(\Drupal::entityDefinitionUpdateManager()->needsUpdates(), 'There are no new updates to apply.');

    $ids_after = [];
    // Now check that the previously created entities still exist, have the
    // right IDs and are multiversion enabled.
    foreach ($this->entityTypes as $entity_type_id => $values) {
      $storage = $entity_type_manager->getStorage($entity_type_id);
      $storage_class = $storage->getEntityType($entity_type_id)->getStorageClass();
      $this->assertFalse(is_subclass_of($storage_class, ContentEntityStorageInterface::class), "$entity_type_id got the correct storage handler assigned.");
      $this->assertTrue($storage->getQuery() instanceof QueryInterface, "$entity_type_id got the correct query handler assigned.");
      $ids_after[$entity_type_id] = $storage->getQuery()->execute();
      $this->assertCount($count_before[$entity_type_id], $ids_after[$entity_type_id], "All ${entity_type_id}s were migrated.");
    }
  }

}
