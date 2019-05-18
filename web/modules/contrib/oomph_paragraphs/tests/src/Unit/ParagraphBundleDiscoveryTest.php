<?php

declare(strict_types=1);

namespace Drupal\Tests\oomph_paragraphs\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\oomph_paragraphs\ParagraphBundleDiscovery;

class ParagraphBundleDiscoveryTest extends UnitTestCase {

  protected $entityTypeManager;

  protected $finder;

  public function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this
      ->getMockBuilder(EntityTypeManagerInterface::class)
      ->getMock();

    $this->finder = $this
      ->getMockBuilder(Finder::class)
      ->getMock();
  }

  public function testDiscoverBundles(): void {
    $entityStorage = $this
      ->getMockBuilder(EntityStorageInterface::class)
      ->getMock();
    $entityStorage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([
        'column_group' => [],
        'image' => [],
        'row' => [],
        'video' => [],
        'wysiwyg' => [],
        'test' => [],
      ]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('paragraphs_type')
      ->willReturn($entityStorage);

    $this->finder->expects($this->once())
        ->method('files')
        ->willReturnSelf();
    $this->finder->expects($this->once())
        ->method('in')
        ->with(ParagraphBundleDiscovery::TEMPLATES_DIR)
        ->willReturnSelf();
    $this->finder->expects($this->once())
        ->method('name')
        ->with('paragraph--*.html.twig')
        ->willReturnSelf();
    $this->finder->expects($this->once())
        ->method('getIterator')
        ->willReturn($this->getFinderIterator());

    $discovery = new ParagraphBundleDiscovery(
      $this->entityTypeManager,
      $this->finder
    );

    $this->assertEquals(
      ['row', 'column_group', 'wysiwyg'],
      $discovery->discoverBundles()
    );
  }

  protected function getFinderIterator(): \Iterator {
    $templateFiles = [
      'paragraph--row.html.twig',
      'paragraph--column-group.html.twig',
      'paragraph--wysiwyg.html.twig',
      'paragraph--oomph-test.html.twig',
    ];
    $iterator = new \ArrayIterator();

    foreach ($templateFiles as $file) {
      $splFileInfoMock = $this
        ->getMockBuilder(SplFileInfo::class)
        ->disableOriginalConstructor()
        ->getMock();
      $splFileInfoMock->expects($this->once())
        ->method('getFilename')
        ->willReturn($file);

      $iterator->append($splFileInfoMock);
    }

    return $iterator;
  }

}
