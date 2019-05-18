<?php

namespace Drupal\Tests\file_image_formatter\Unit\Plugin\Field\FieldFormatter;


use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file_image_formatter\Plugin\Field\FieldFormatter\FileImageFormatter;
use Drupal\Tests\UnitTestCase;

/**
 * Test the FileImageFormatter plugin.
 *
 * @group file_image_formatter
 */
class FileImageFormatterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    $fileType = $this->prophesize('\Drupal\Core\Entity\EntityTypeInterface');
    $fileType->getHandlerClass('access')->willReturn(NULL);

    $fieldTypePluginManager = $this->prophesize('\Drupal\Core\Field\FieldTypePluginManagerInterface');
    $fieldTypePluginManager->getDefaultFieldSettings('file')->willReturn([]);
    $fieldTypePluginManager->getDefaultStorageSettings('file')->willReturn([]);

    $imageItemProphecy = $this->prophesize('\Drupal\file\FileInterface');
    $imageItemProphecy->getMimeType()->willReturn('image/jpeg');
    $imageItemProphecy->getFileUri()->willReturn('public://blah.jpg');
    $imageItemProphecy->getFilename()->willReturn('blah.jpg');
    $imageItemProphecy->getEntityType()->willReturn($fileType->reveal());
    $imageItemProphecy->getCacheTags()->willReturn([]);

    $this->image = $imageItemProphecy->reveal();

    $entityRepository = $this->prophesize('\Drupal\Core\Entity\EntityRepositoryInterface');
    $entityRepository
      ->getTranslationFromContext($this->image, LanguageInterface::LANGCODE_DEFAULT)
      ->willReturn($this->image);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $fieldTypePluginManager->reveal());
    $container->set('entity.repository', $entityRepository->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Assert that the viewElements method returns output.
   */
  public function testViewElements() {
    $definition = [
      'id' => 'file_image_formatter',
      'label' => 'File image formatter',
    ];
    $field_definition = BaseFieldDefinition::create('file')
      ->setLabel('Test file');
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface');
    $image_style_storage = $this->prophesize('\Drupal\Core\Entity\EntityStorageInterface');

    $formatter = new FileImageFormatter(
      'file_image_formatter',
      $definition,
      $field_definition,
      [],
      'hidden',
      'default',
      [],
      $account->reveal(),
      $image_style_storage->reveal());

    $referenceItem = $this->getMockBuilder('\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem')
      ->disableOriginalConstructor()
      ->getMock();
    $referenceItem->expects($this->any())
      ->method('__get')
      ->will($this->returnValueMap([
        ['_loaded', 'Something non-empty'],
        ['entity', $this->image],
      ]));

    $itemList = $this->prophesize('\Drupal\Core\Field\EntityReferenceFieldItemListInterface');
    $itemList->isEmpty()->willReturn(FALSE);
    $itemList->get(0)->willReturn($referenceItem);
    $itemList->offsetGet(0)->willReturn($referenceItem);
    $itemList->rewind()->willReturn();
    $itemList->valid()->willReturn(TRUE, FALSE);
    $itemList->current()->willReturn($referenceItem);
    $itemList->key()->willReturn(0);
    $itemList->next()->willReturn();

    $elements = $formatter->viewElements($itemList->reveal(), LanguageInterface::LANGCODE_DEFAULT);

    $this->assertEquals('image_formatter', $elements[0]['#theme']);
  }

}
