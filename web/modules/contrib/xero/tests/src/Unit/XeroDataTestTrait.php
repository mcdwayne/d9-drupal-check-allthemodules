<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\xero\Plugin\DataType\XeroItemList;
use Drupal\xero\TypedData\Definition\AccountDefinition;
use Drupal\xero\TypedData\Definition\AddressDefinition;
use Drupal\xero\TypedData\Definition\BankTransactionDefinition;
use Drupal\xero\TypedData\Definition\LineItemDefinition;
use Drupal\xero\TypedData\Definition\PhoneDefinition;
use Drupal\xero\TypedData\Definition\TrackingCategoryDefinition;
use Drupal\xero\TypedData\Definition\TrackingCategoryOptionDefinition;
use Drupal\xero\TypedData\Definition\XeroDefinitionInterface;
use Prophecy\Argument;

/**
 * Provides Typed Data Manager mocking capabilities for Xero API.
 */
trait XeroDataTestTrait {

  /**
   * The prophesized typed data manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $typedDataManagerProphet;

  /**
   * Annotated discovery class.
   *
   * @var \Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery
   */
  protected $discovery;

  /**
   * Creates the typed data manager prophecy on the object.
   */
  protected function createTypedDataProphet() {
    $this->typedDataManagerProphet = $this->prophesize('\Drupal\Core\TypedData\TypedDataManagerInterface');

    FileCacheFactory::setPrefix(FileCacheFactory::DISABLE_CACHE);

    // There is no good method to figure out the app root so make two educated
    // guesses: directly under modules or under modules/contrib (qa.drupal.org).
    $root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)));
    if (!realpath($root . '/core')) {
      $root = dirname($root);
    }

    $this->discovery = new AnnotatedClassDiscovery(
      [
        '\Drupal\xero\Plugin\DataType' => [__DIR__ . '/../../../src/Plugin/DataType/'],
        '\Drupal\Core\TypedData\Plugin\DataType' => [$root . '/core/lib/Drupal/Core/TypedData/Plugin/DataType/'],
      ],
      'Drupal\Core\TypedData\Annotation\DataType',
      ['\Drupal\Core\Annotation']
    );

    $this->typedDataManagerProphet
      ->getDefinitions()
      ->willReturn($this->discovery->getDefinitions());
  }

  /**
   * Mock xero and primitive data types recursively.
   *
   * @param string $type
   *   The plugin id.
   * @param mixed $value
   *   The data value to set (not typed data).
   * @param string $name
   *   An optional property name depending on the typed data to mock.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $itemDefinition
   *   An optional data definition interface for a list.
   */
  protected function mockTypedData($type, $value, $name = NULL, DataDefinitionInterface $itemDefinition = NULL) {
    // Create typed data stub from the given type.
    switch ($type) {

      case 'xero_account':
        $definition = new AccountDefinition(
          [
            'id' => 'xero_account',
            'definition_class' => '\Drupal\xero\TypedData\Definition\AccountDefinition',
          ]
        );
        $class = '\Drupal\xero\Plugin\DataType\Account';

        $this->mockTypedDataProperties($definition, $value);
        break;

      case 'xero_address':
        $definition = new AddressDefinition(
          [
            'id' => 'xero_address',
            'definition_class' => '\Drupal\xero\TypedData\Definition\AddressDefinition',
            'list_class' => '\Drupal\xero\Plugin\DataType\XeroItemList',
          ]
        );
        $class = '\Drupal\xero\Plugin\DataType\Address';
        $this->mockTypedDataProperties($definition, $value);
        break;

      case 'xero_phone':
        $definition = new PhoneDefinition(
          [
            'id' => 'xero_phone',
            'definition_class' => '\Drupal\xero\TypedData\Definition\PhoneDefinition',
            'list_class' => '\Drupal\xero\Plugin\DataType\XeroItemList',
          ]
        );
        $class = '\Drupal\xero\Plugin\DataType\Phone';
        $this->mockTypedDataProperties($definition, $value);
        break;

      case 'xero_tracking':
        $definition = new TrackingCategoryDefinition(
          [
            'id' => 'xero_tracking',
            'definition_class' => '\Drupal\xero\TypedData\Definition\TrackingCategoryDefinition',
          ]
        );
        $class = '\Drupal\xero\Plugin\DataType\TrackingCategory';

        $this->mockTypedDataProperties($definition, $value);
        break;

      case 'xero_tracking_category_option':
        $definition = new TrackingCategoryOptionDefinition(
          [
            'id' => 'xero_tracking_category_option',
            'definition_class' => '\Drupal\xero\TypedData\Definition\TrackingCategoryOptionDefinition',
          ]
        );
        $class = '\Drupal\xero\Plugin\DataType\TrackingCategoryOption';

        $this->mockTypedDataProperties($definition, $value);
        break;

      case 'xero_bank_transaction':
        $definition = new BankTransactionDefinition(
          [
            'id' => 'xero_bank_transaction',
            'definition_class' => '\Drupal\xero\TypedData\Definition\BankTransactionDefinition',
          ]
        );
        $class = '\Drupal\xero\Plugin\DataType\BankTransaction';

        $this->mockTypedDataProperties($definition, $value);
        break;

      case 'xero_line_item':
        $definition = new LineItemDefinition(['id' => 'xero_line_item']);
        $class = '\Drupal\xero\Plugin\DataType\LineItem';
        break;

      case 'list':
        $itemType = $itemDefinition->getDataType();
        $listDefinition = new ListDataDefinition(
          [
            'id' => 'list',
            'definition_class' => '\Drupal\Core\TypedData\ListDataDefinition',
          ],
          $itemDefinition
        );
        $definition = $itemDefinition;

        $this->mockTypedData($itemType, $value[0], 0, NULL);

        $definition_array = $this->discovery->getDefinition($itemType, FALSE);
        $this->typedDataManagerProphet
          ->getDefinition($itemType)
          ->willReturn($definition_array);

        $class = $definition_array['class'];

        $this->typedDataManagerProphet
          ->createListDataDefinition($itemType)
          ->willReturn($listDefinition);
        $this->typedDataManagerProphet
          ->create($listDefinition, Argument::type('array'))
          ->willReturn(new XeroItemList($itemDefinition, $name));
        $this->typedDataManagerProphet
          ->getPropertyInstance(Argument::type('\Drupal\xero\Plugin\DataType\XeroItemList'), Argument::any(), Argument::any())
          ->will(function ($args) {
            $index = $args[1] - 1;
            return $args[0]->get($index);
          });
        break;

      case 'int':
        $definition = new DataDefinition(['id' => 'integer']);
        $class = '\Drupal\Core\TypedData\Plugin\DataType\IntegerData';
        break;

      case 'float':
        $definition = new DataDefinition(['id' => 'float']);
        $class = '\Drupal\Core\TypedData\Plugin\DataType\FloatData';
        break;

      default:
        $definition = new DataDefinition(['id' => 'string']);
        $class = '\Drupal\Core\TypedData\Plugin\DataType\StringData';
    }

    if (class_exists($class)) {
      $data = new $class($definition);
    }
    else {
      $debug_type = isset($itemType) ? $type . '<' . $itemType . '>' : $type;
      $this->fail('class does not exist for type: ' . $debug_type);
    }

    // Mock create instance.
    $this->typedDataManagerProphet
      ->create($definition, Argument::any())
      ->willReturn($data);
    $this->typedDataManagerProphet
      ->createInstance($type)
      ->willReturn($data);
    $this->typedDataManagerProphet
      ->createDataDefinition($type)
      ->willReturn($definition);
    // @todo this doesn't handle primitives. Do not assert equality.
    $this->typedDataManagerProphet
      ->getPropertyInstance(Argument::any(), $name, Argument::any())
      ->will(function () use ($data, $value) {
        $data->setValue($value);
        return $data;
      });

    if ($definition instanceof XeroDefinitionInterface) {
      $definition->getPropertyDefinitions();
    }
  }

  protected function mockTypedDataProperties(DataDefinitionInterface $definition, $values) {
    $itemDefinition = NULL;
    $dataType = NULL;

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $propDef */
    foreach ($definition->getPropertyDefinitions() as $prop => $propDef) {
      $prop_value = isset($values[$prop]) ? $values[$prop] : NULL;
      $dataType = $propDef->getDataType();

      if ($propDef->isList()) {
        /** @var \Drupal\Core\TypedData\ListDataDefinitionInterface $propDef */
        $itemDefinition = $propDef->getItemDefinition();
        $dataType = 'list';
      }

      $this->mockTypedData($dataType, $prop_value, $prop, $itemDefinition);
    }
  }
}
