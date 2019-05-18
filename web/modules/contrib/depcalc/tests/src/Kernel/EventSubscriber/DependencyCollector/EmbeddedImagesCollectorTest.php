<?php

namespace Drupal\Tests\depcalc\Kernel\EventSubscriber\DependencyCollector;

use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\EventSubscriber\DependencyCollector\EmbeddedImagesCollector;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Class EmbeddedImagesCollectorTest.
 *
 * @group depcalc
 */
class EmbeddedImagesCollectorTest extends KernelTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'system',
    'filter',
    'field',
    'text',
    'file',
    'editor',
    'depcalc',
  ];

  /**
   * Calculates all the dependencies of a given entity.
   *
   * @var \Drupal\depcalc\DependencyCalculator
   */
  private $calculator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('filter');
    $this->installConfig('node');
    $this->installSchema('node', 'node_access');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');

    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    $this->calculator = \Drupal::service('entity.dependency.calculator');
  }

  /**
   * Tests dependency calculation.
   *
   * Checks that node's dependencies contains attached files.
   *
   * @param array $files_ids
   *   UUIDs of attached files.
   * @param array $usages
   *   Usages map for files.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider dependenciesCalculationProvider
   */
  public function testDependenciesCalculation(array $files_ids, array $usages) {
    $node = $this->createNode();
    foreach ($files_ids as $delta => $uuid) {
      $this->uploadAndAttachFileToNode($uuid, $usages[$delta], $node);
    }
    try {
      $wrapper = new DependentEntityWrapper($node);
    }
    catch (\Exception $exception) {
      $this->markTestIncomplete($exception->getMessage());
      return;
    }

    $dependencies = $this->calculator->calculateDependencies($wrapper, new DependencyStack());
    foreach ($files_ids as $uuid) {
      $this->assertArrayHasKey($uuid, $dependencies);
    }
  }

  /**
   * Checks EmbeddedImagesCollector absence.
   *
   * Checks that without EmbeddedImagesCollector uploaded images
   * isn't node dependencies.
   *
   * @param array $files_ids
   *   UUIDs of attached files.
   * @param array $usages
   *   Usages map for files.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider dependenciesCalculationProvider
   */
  public function testDependenciesCalculationWithoutImagesCollector(array $files_ids, array $usages) {
    $mock = $this->getMockBuilder(EmbeddedImagesCollector::class)
      ->disableOriginalConstructor()
      ->getMock();
    \Drupal::getContainer()->set('embedded_images.calculator', $mock);

    $node = $this->createNode();
    foreach ($files_ids as $delta => $uuid) {
      $this->uploadAndAttachFileToNode($uuid, $usages[$delta], $node);
    }
    try {
      $wrapper = new DependentEntityWrapper($node);
    }
    catch (\Exception $exception) {
      $this->markTestIncomplete($exception->getMessage());
      return;
    }

    $dependencies = $this->calculator->calculateDependencies($wrapper, new DependencyStack());
    foreach ($files_ids as $uuid) {
      $this->assertArrayNotHasKey($uuid, $dependencies);
    }
  }

  /**
   * Data provider for testDependenciesCalculation.
   */
  public function dependenciesCalculationProvider() {
    yield [
      ['123e4567-e89b-12d3-a456-426655440000'],
      [1],
    ];

    yield [
      [
        '123e4567-e89b-12d3-a456-426655440000',
        '123e4567-e89b-12d3-a456-426655440001',
      ],
      [1, 1],
    ];

    yield [
      [
        '123e4567-e89b-12d3-a456-426655440000',
        '123e4567-e89b-12d3-a456-426655440001',
      ],
      [2, 2],
    ];
  }

  /**
   * Simulates file attachment process to node via editor.
   *
   * @param string $uuid
   *   File UUID.
   * @param string $usages
   *   File usages count.
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function uploadAndAttachFileToNode($uuid, $usages, NodeInterface $node) {
    $uri = sprintf('public://file-%s.png', $this->randomMachineName());
    file_put_contents($uri, '');
    $file = File::create([
      'uri' => $uri,
      'filename' => 'file.png',
      'uuid' => $uuid,
    ]);
    $file->save();

    while ($usages--) {
      self::fileUsage()->add($file, 'editor', 'node', $node->id());
    }
  }

  /**
   * Wraps the file usage service.
   *
   * @return \Drupal\file\FileUsage\FileUsageInterface
   *   File usage service.
   */
  protected static function fileUsage() {
    return \Drupal::service('file.usage');
  }

}
