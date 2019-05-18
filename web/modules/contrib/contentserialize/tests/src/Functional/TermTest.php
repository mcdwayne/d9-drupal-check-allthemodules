<?php

namespace Drupal\Tests\contentserialize\Functional;

use Drupal\contentserialize\Destination\FileDestination;
use Drupal\contentserialize\Source\FileSource;
use Drupal\filter\Entity\FilterFormat;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Test exporting and importing terms.
 *
 * @group contentserialize
 */
class TermTest extends BrowserTestBase {

  protected static $modules = ['contentserialize', 'taxonomy'];

  public function testExportImport() {
    FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
    ])->save();
    $tags = Vocabulary::create(['name' => 'Tags', 'vid' => 'tags']);
    $tags->save();

    $a = Term::create([
      'vid' => 'tags',
      'name' => 'Test Tag A',
      'description' => [
        'value' => 'Test Tag A Description',
        'format' => 'basic_html',
      ],
    ]);
    $a->save();
    $b = Term::create([
      'vid' => 'tags',
      'name' => 'Test Tag B',
      'description' => [
        'value' => 'Test Tag B Description',
        'format' => 'basic_html',
      ],
      'parent' => $a->id(),
    ]);
    $b->save();
    $c = Term::create([
      'vid' => 'tags',
      'name' => 'Test Tag C',
      'description' => [
        'value' => 'Test Tag C Description',
        'format' => 'basic_html',
      ],
      'parent' => $b->id(),
    ]);
    $c->save();

    // Export them.
    $path = file_directory_temp();
    $destination = new FileDestination($path);
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $serialized = $exporter->exportMultiple([$a, $b, $c], 'json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $destination->saveMultiple($serialized);

    // Delete them.
    $uuids = ['a' => $a->uuid(), 'b' => $b->uuid(), 'c' => $c->uuid()];
    $a->delete();
    $b->delete();
    $c->delete();
    $tags->delete();

    // Reimport them.
    Vocabulary::create(['name' => 'Tags', 'vid' => 'tags'])->save();
    /** @var \Drupal\contentserialize\ImporterInterface $importer */
    $importer = \Drupal::service('contentserialize.importer');
    $result = $importer->import(new FileSource($path));
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['uuid' => array_values($uuids)]);
    foreach ($terms as $term) {
      $terms[$term->uuid()] = $term;
    }

    $a = $terms[$uuids['a']];
    $b = $terms[$uuids['b']];
    $c = $terms[$uuids['c']];

    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $a_parents = array_keys($storage->loadParents($a->id()));
    $b_parents = array_keys($storage->loadParents($b->id()));
    $c_parents = array_keys($storage->loadParents($c->id()));

    // Check them.
    $this->assertFalse($result->getFailures());

    $this->assertEquals('tags', $a->bundle());
    $this->assertEquals('tags', $b->bundle());
    $this->assertEquals('tags', $c->bundle());

    $this->assertEquals('Test Tag A', $a->label());
    $this->assertEquals('Test Tag B', $b->label());
    $this->assertEquals('Test Tag C', $c->label());

    $this->assertEquals('Test Tag A Description', $a->description->value);
    $this->assertEquals('Test Tag B Description', $b->description->value);
    $this->assertEquals('Test Tag C Description', $c->description->value);

    $this->assertEquals([], $a_parents);
    $this->assertEquals([$a->id()], $b_parents);
    $this->assertEquals([$b->id()], $c_parents);
  }

}