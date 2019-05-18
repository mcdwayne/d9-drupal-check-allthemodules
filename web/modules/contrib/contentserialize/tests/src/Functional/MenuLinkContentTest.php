<?php

namespace Drupal\Tests\contentserialize\Functional;

use Drupal\contentserialize\Destination\FileDestination;
use Drupal\contentserialize\Source\FileSource;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Test exporting/importing menu_link_content entities.
 *
 * @group contentserialize
 */
class MenuLinkContentTest extends BrowserTestBase {

  public static $modules = ['contentserialize', 'menu_link_content', 'node'];

  /**
   * Test exporting and importing a link referencing a node.
   */
  public function testNodeLink() {
    $this->drupalCreateContentType(['type' => 'page']);
    // The file source just returns in UUID order, so make sure the menu link
    // will be imported before its dependency (to test the fixer).
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'uuid' => 'e1c5eeb4-22a6-41a7-bcd2-d97c591c9a72',
    ]);

    $link = MenuLinkContent::create([
      'title' => 'Test Menu Link',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'parent' => '',
      'link' => [['uri' => 'entity:node/' . $node->id()]],
      'uuid' => '03907dac-2923-41a5-8f4f-127e2db87712',
    ]);
    $link->save();

    // Export it.
    $destination = new FileDestination(file_directory_temp());
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $serialized = $exporter->exportMultiple([$link, $node], 'json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $destination->saveMultiple($serialized);

    // Delete it.
    $link->delete();
    $node->delete();

    // Reimport it.
    /** @var \Drupal\contentserialize\ImporterInterface $importer */
    $importer = \Drupal::service('contentserialize.importer');
    $result = $importer->import(new FileSource(file_directory_temp()));

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = \Drupal::service('entity.repository');
    /** @var \Drupal\file\FileInterface $file */
    $link = $repository->loadEntityByUuid('menu_link_content', '03907dac-2923-41a5-8f4f-127e2db87712');
    $node = $repository->loadEntityByUuid('node', 'e1c5eeb4-22a6-41a7-bcd2-d97c591c9a72');

    // Check it.
    $this->assertFalse($result->getFailures());
    $this->assertEquals('03907dac-2923-41a5-8f4f-127e2db87712', $link->uuid());
    $this->assertEquals('e1c5eeb4-22a6-41a7-bcd2-d97c591c9a72', $node->uuid());
    $this->assertEquals('Test Menu Link', $link->label());
    $this->assertEquals('entity:node/' . $node->id(), $link->link->uri);
  }

}