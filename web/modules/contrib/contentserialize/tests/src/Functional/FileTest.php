<?php

namespace Drupal\Tests\contentserialize\Functional;

use Drupal\contentserialize\Destination\FileDestination;
use Drupal\contentserialize\Source\FileSource;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;

/**
 * Class FileTest
 *
 * @group contentserialize
 *
 * @todo Refactor some bits out into a base class.
 */
class FileTest extends  BrowserTestBase {

  protected static $modules = ['contentserialize', 'file'];

  public function testExportImport() {
    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'drupal.txt',
      'uri' => 'public://drupal.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();

    // Export it.
    $path = file_directory_temp();
    $destination = new FileDestination($path);
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $serialized = $exporter->exportMultiple([$file], 'json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $destination->saveMultiple($serialized);

    // Delete it.
    $uuid = $file->uuid();
    $file->delete();

    // Reimport it.
    /** @var \Drupal\contentserialize\ImporterInterface $importer */
    $importer = \Drupal::service('contentserialize.importer');
    $result = $importer->import(new FileSource($path));

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = \Drupal::service('entity.repository');
    /** @var \Drupal\file\FileInterface $file */
    $file = $repository->loadEntityByUuid('file', $uuid);

    // Check it.
    $this->assertFalse($result->getFailures());
    $this->assertEquals($uuid, $file->uuid());
    $this->assertEquals(1, $file->getOwnerId());
    $this->assertEquals('drupal.txt', $file->filename->value);
    $this->assertEquals('public://drupal.txt', $file->uri->value);
    $this->assertEquals('text/plain', $file->filemime->value);
    $this->assertEquals(FILE_STATUS_PERMANENT, $file->status->value);
  }

}