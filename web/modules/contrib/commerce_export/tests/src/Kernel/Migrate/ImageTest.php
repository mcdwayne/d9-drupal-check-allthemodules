<?php

namespace Drupal\Tests\commerce_export\Kernel\Migrate;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Tests Product migration.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_export
 */
class ImageTest extends TestBase {

  use MigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fileMigrationSetup();
  }

  /**
   * Tests a single file entity.
   *
   * @param int $id
   *   The file ID.
   * @param string $name
   *   The expected file name.
   * @param string $uri
   *   The expected URI.
   * @param string $mime
   *   The expected MIME type.
   * @param int $size
   *   The expected file size.
   * @param int $uid
   *   The expected owner ID.
   */
  protected function assertEntity($id, $name, $uri, $mime, $size, $uid) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($id);
    $this->assertTrue($file instanceof FileInterface);
    $this->assertSame($name, $file->getFilename());
    $this->assertSame($uri, $file->getFileUri());
    $this->assertTrue(file_exists($uri));
    $this->assertSame($mime, $file->getMimeType());
    $this->assertSame($size, $file->getSize());
    $this->assertTrue($file->isPermanent());
    $this->assertSame($uid, $file->getOwnerId());
  }

  /**
   * Tests image file migration from CSV source file.
   */
  public function testFileMigration() {
    $this->enableModules(['commerce_export']);
    $this->executeMigration('import_image');

    $this->assertEntity(1, 'TherMaxx 3mm - Black - Mens - XS.jpeg', 'public://images/TherMaxx%203mm%20-%20Black%20-%20Mens%20-%20XS.jpeg', 'image/jpeg', '4789', '1');
    $this->assertEntity(2, 'image2.png', 'public://images/image2.png', 'image/png', '3974', '1');
    $this->assertEntity(3, 'Thumbnail 1.png', 'public://images/Thumbnail%201.png', 'image/png', '3974', '1');
    $this->assertEntity(6, 'TherMaxx 3mm - Black - Mens - Small.jpeg', 'public://images/TherMaxx%203mm%20-%20Black%20-%20Mens%20-%20Small.jpeg', 'image/jpeg', '4789', '1');
    $this->assertEntity(20, 'Aquaseal 8oz.jpeg', 'public://images/Aquaseal%208oz.jpeg', 'image/jpeg', '6131', '1');
    $this->assertEntity(21, 'Thumbnail 2.png', 'public://images/Thumbnail%202.png', 'image/png', '5424', '1');
    $this->assertEntity(22, 'Zip Care*.jpeg', 'public://images/Zip%20Care%2A.jpeg', 'image/jpeg', '4789', '1');
    $this->assertEntity(23, 'Thumbnail 3.png', 'public://images/Thumbnail%203.png', 'image/png', '3905', '1');
    $this->assertEntity(34, 'Hero 5 - Black.jpeg', 'public://images/Hero%205%20-%20Black.jpeg', 'image/jpeg', '4789', '1');
    $this->assertEntity(35, 'image3.png', 'public://images/image3.png', 'image/png', '3905', '1');
    // CTA files.
    $this->assertEntity(4, 'flying.jpeg', 'public://images/flying.jpeg', 'image/jpeg', '4789', '1');
    $this->assertEntity(5, 'rainbow.png', 'public://images/rainbow.png', 'image/png', '5424', '1');
  }

}
