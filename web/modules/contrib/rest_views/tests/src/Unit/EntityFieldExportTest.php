<?php

namespace Drupal\Tests\rest_views\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rest_views\Normalizer\DataNormalizer;
use Drupal\rest_views\Normalizer\RenderNormalizer;
use Drupal\rest_views\Plugin\views\field\EntityFieldExport;
use Drupal\rest_views\SerializedData;
use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\Serializer\Serializer;

/**
 * @group rest_views
 */
class EntityFieldExportTest extends UnitTestCase {
  /**
   * @var \Drupal\rest_views\Plugin\views\field\EntityFieldExport|\PHPUnit_Framework_MockObject_MockObject
   */
  private $handler;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  private $formatterPluginManager;

  /**
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  private $fieldTypePluginManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  private $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create our field handler, mocking the required services.
    $this->entityManager = $this->getMock(EntityManagerInterface::class);
    $this->formatterPluginManager = $this->getMockBuilder(FormatterPluginManager::class)
                                         ->disableOriginalConstructor()
                                         ->getMock();
    $this->fieldTypePluginManager = $this->getMock(FieldTypePluginManagerInterface::class);
    $this->languageManager = $this->getMock(LanguageManagerInterface::class);
    $this->renderer = $this->getMock(RendererInterface::class);
    $this->handler = $this->getMockBuilder(EntityFieldExport::class)
      ->setConstructorArgs([
        [],
        NULL,
        [
          'entity_type' => 'node',
          'field_name'  => 'title',
        ],
        $this->entityManager,
        $this->formatterPluginManager,
        $this->fieldTypePluginManager,
        $this->languageManager,
        $this->renderer,
      ])
      ->setMethods(['getFieldDefinition'])
      ->getMock();

    // For the t() function to work, mock the translation service.
    $container = new ContainerBuilder();
    $translation = $this->getMock(TranslationInterface::class);
    $translation->expects($this->any())
      ->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $string) {
        return $string->getUntranslatedString();
      });
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);

    // Mock the field definition.
    $fieldDefinition = $this->getMock(BaseFieldDefinition::class);
    $fieldDefinition->expects($this->any())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldDefinition);
    $fieldDefinition->expects($this->any())
      ->method('getColumns')
      ->willReturn([]);

    // The handler accesses it through itself, and through the entity manager.
    $this->handler->expects($this->any())
      ->method('getFieldDefinition')
      ->willReturn($fieldDefinition);
    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn(['title' => $fieldDefinition]);

    // Initialize the handler, using a mocked view and display plugin.
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();
    $view->display_handler = $this->getMockBuilder(DisplayPluginBase::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->handler->init($view, $view->display_handler);

    $this->serializer = new Serializer([
      new DataNormalizer(),
      new RenderNormalizer($this->renderer),
    ], [
      new JsonEncoder(),
    ]);
  }

  /**
   * Check that the field does not use multi-type and separator options.
   */
  public function testSettings() {
    $options = $this->handler->defineOptions();
    $this->assertArrayNotHasKey('multi_type', $options);
    $this->assertArrayNotHasKey('separator', $options);
    $form = [];
    $this->handler->multiple_options_form($form, new FormState());
    $this->assertArrayNotHasKey('multi_type', $form);
    $this->assertArrayNotHasKey('separator', $form);
  }

  /**
   * Check that the handler correctly preserves serializable items.
   *
   * @param array $items
   * @param array $expected
   *
   * @dataProvider providerItems
   */
  public function testRenderItems(array $items, array $expected) {
    $this->handler->multiple = FALSE;
    $result = $this->handler->renderItems($items);
    $json = $this->serializer->serialize($result, 'json');
    $expected_json = $this->serializer->serialize($expected[0], 'json');
    $this->assertEquals($expected_json, $json);
    $this->handler->multiple = TRUE;
    $result = $this->handler->renderItems($items);
    $json = $this->serializer->serialize($result, 'json');
    $expected_json = $this->serializer->serialize($expected, 'json');
    $this->assertEquals($expected_json, $json);
  }

  /**
   * @return array
   */
  public function providerItems() {
    $data[] = [
      'items' => ['Lorem', 'ipsum', 'dolor', 'sit', 'amet'],
      'expected' => ['Lorem', 'ipsum', 'dolor', 'sit', 'amet'],
    ];
    $data[] = [
      'items' => [
        new SerializedData(['lorem' => 'ipsum']),
        new SerializedData(['dolor' => TRUE]),
        new SerializedData(['amet' => 42]),
      ],
      'expected' => [
        ['lorem' => 'ipsum'],
        ['dolor' => TRUE],
        ['amet' => 42]
      ],
    ];
    return $data;
  }

}
