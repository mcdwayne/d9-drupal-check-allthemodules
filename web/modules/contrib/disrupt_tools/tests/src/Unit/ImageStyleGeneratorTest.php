<?php

namespace Drupal\Tests\disrupt_tools\Unit;

use Drupal\Tests\UnitTestCase;

define('FILE_STATUS_PERMANENT', 1);

/**
 * @coversDefaultClass \Drupal\disrupt_tools\Service\ImageStyleGenerator
 * @group disrupt_tools
 */
class ImageStyleGeneratorTest extends UnitTestCase {
  private $files = [
        // Used as existing file.
    '10' => [
      'path' => '/images/10.JPG',
      'uri'  => 'public://images/10.JPG',
      'url'  => 'http://domain.ltd/sites/default/files/styles/thumbnail/public/images/10.jpg?itok=AU4gVaykG',
    ],

        // Used as none existing file.
    '20' => [
      'path' => '/images/20.JPG',
      'uri'  => 'public://images/20.JPG',
      'url'  => 'http://domain.ltd/sites/default/files/styles/thumbnail/public/images/20.jpg?itok=AU4gVaykG',
    ],
  ];

  /**
   * PHPUnit setup.
   */
  public function setUp() {
    // Mock image style.
    $imageStyleProphet = $this->prophesize('\Drupal\image\Entity\ImageStyle');
    $imageStyleProphet->buildUrl('public://images/10.JPG')->willReturn('http://domain.ltd/sites/default/files/styles/thumbnail/public/images/10.jpg?itok=AU4gVaykG');

    // Mock image style storage.
    $imageStyleStorageProphet = $this->prophesize('\Drupal\image\ImageStyleStorage');
    $imageStyleStorageProphet->load('thumbnail')->willReturn($imageStyleProphet->reveal());

    // Mock file(s) entity.
    foreach ($this->files as $id => $file) {
      $this->{'fileProphet_' . $id} = $this->prophesize('\Drupal\file\Entity\File');
      $this->{'fileProphet_' . $id}->id()->willReturn($id);
      $this->{'fileProphet_' . $id}->getFileUri()->willReturn($file['uri']);
    }

    // Mock file field item.
    foreach ($this->files as $id => $file) {
      $this->{'fileFieldProphet_' . $id} = $this->prophesize('\Drupal\file\Plugin\Field\FieldType\FileFieldItemList');
      $this->{'fileFieldProphet_' . $id}->getValue()->willReturn([0 => ['target_id' => $id]]);
    }

    // Mock file storage.
    $fileStorageProphet = $this->prophesize('\Drupal\file\FileStorage');
    $fileStorageProphet->load(NULL)->willReturn(NULL);
    foreach ($this->files as $id => $file) {
      $fileStorageProphet->load($id)->willReturn($this->{'fileProphet_' . $id}->reveal());
    }

    // Mock fso.
    $fsoProphet = $this->prophesize('\Drupal\Core\File\FileSystem');
    foreach ($this->files as $id => $file) {
      $fsoProphet->realpath($file['uri'])->willReturn($file['path']);
    }

    // Mock entity type manager.
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityManagerProphet->getStorage('file')->willReturn($fileStorageProphet->reveal());
    $entityManagerProphet->getStorage('image_style')->willReturn($imageStyleStorageProphet->reveal());

    // Image Style Generator.
    $this->imageStyleGenerator = $this->getMockBuilder('\Drupal\disrupt_tools\Service\ImageStyleGenerator')
      ->setConstructorArgs([$entityManagerProphet->reveal(), $fsoProphet->reveal()])
      ->setMethods(['fileExist'])
      ->getMock();

    // Mock the method fileExist.
    $this->imageStyleGenerator
      ->expects($this->any())
      ->method('fileExist')
      ->will(
              $this->returnValueMap([
                  ['/images/10.JPG', TRUE],
                  ['/images/20.JPG', FALSE],
              ])
          );
  }

  /**
   * Check fromField works properly.
   *
   * Use an existing file and want to retrieve a thumbnail of this one.
   */
  public function testFromFieldWorks() {
    $styles = $this->imageStyleGenerator->fromField($this->fileFieldProphet_10->reveal(), ['thumb' => 'thumbnail']);
    $this->assertArrayHasKey('thumb', $styles);
    $this->assertEquals('http://domain.ltd/sites/default/files/styles/thumbnail/public/images/10.jpg?itok=AU4gVaykG', $styles['thumb']);
  }

  /**
   * Check fromField return empty when no styles are requested.
   */
  public function testFromFieldFailWhenNoStyles() {
    $this->assertEmpty($this->imageStyleGenerator->fromField($this->fileFieldProphet_10->reveal(), []));
  }

  /**
   * Check fromField return empty when submitted file not exist.
   */
  public function testFromFieldFailWhenNoExistFile() {
    $this->assertEmpty($this->imageStyleGenerator->fromField($this->fileFieldProphet_20->reveal(), ['thumb' => 'thumbnail']));
  }

  /**
   * Check fromFile works properly.
   */
  public function testfromFileWorks() {
    $styles = $this->imageStyleGenerator->fromFile(10, ['thumb' => 'thumbnail']);
    $this->assertArrayHasKey('thumb', $styles);
    $this->assertEquals('http://domain.ltd/sites/default/files/styles/thumbnail/public/images/10.jpg?itok=AU4gVaykG', $styles['thumb']);
  }

  /**
   * Check fromFile return empty when no styles are requested.
   */
  public function testFromFileFailWhenNoStyles() {
    $this->assertEmpty($this->imageStyleGenerator->fromFile(10, []));
  }

  /**
   * Check fromFile return empty NULL used as fid.
   */
  public function testFromFileFailWhenNullFile() {
    $this->assertEmpty($this->imageStyleGenerator->fromFile(NULL, ['thumb' => 'thumbnail']));
  }

  /**
   * Check fromFile return empty when submitted file not exist.
   */
  public function testFromFileFailWhenNoExistFile() {
    $this->assertEmpty($this->imageStyleGenerator->fromFile(20, ['thumb' => 'thumbnail']));
  }

}
