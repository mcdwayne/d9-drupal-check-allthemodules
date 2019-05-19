<?php

namespace Drupal\Tests\xero\Unit\Controller;

use Drupal\xero\Controller\XeroAutocompleteController;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Assert that autocomplete works based on mocked XeroQuery output.
 *
 * @coversDefaultClass Drupal\xero\Controller\XeroAutocompleteController
 * @group Xero
 */
class XeroAutocompleteControllerTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $definition;

  /**
   * @var string
   */
  protected $search_term;

  /**
   * @var string
   */
  protected $search_guid;

  /**
   * Assert that autocomplete functions.
   *
   * @dataProvider dataProvider
   */
  public function testAutocomplete($plugin_id, $definition_class_name, $type_name, $expects) {

    // Create the data definition for this data type.
    $this->definition = $definition_class_name::create($plugin_id);
    $this->definition->setClass($type_name);

    $label_name = $type_name::$label;
    $guid_name = $type_name::$guid_name;

    // Create list data definition and item list.
    $listDefinition = ListDataDefinition::create($plugin_id);
    $listDefinition->setClass('\Drupal\Core\TypedData\Plugin\DataType\ItemList');

    $stringDefinition = DataDefinition::create('string');

    // Create item list.
    $itemList = ItemList::createInstance($listDefinition);

    // Create data types.
    $items = [];
    $data = [];
    $dataMap = [];
    if (!empty($expects)) {
      $guid_name = $type_name::$guid_name;
      $label = $type_name::$label;
      foreach ($expects as $num => $values) {
        $item = $type_name::createInstance($this->definition);
        $data[$num] = [$guid_name => $values['value'], $label => $values['label']];
        $item->setValue($data[$num], FALSE);

        if (isset($values['label'])) {
          $expects[$num]['value'] .= ' (' . $values['label'] . ')';
        }
        else {
          $expects[$num]['label'] = $values['value'];
        }
        $items[] = $item;

        // Create data type instances for
        $data[$num]['valueData'] = new StringData($stringDefinition);
        $data[$num]['valueData']->setValue($values['value']);
        $data[$num]['labelData'] = new StringData($stringDefinition);
        $data[$num]['labelData']->setValue($values['label']);
        $dataMap[] = [$itemList, $num, $item, $item];
        $dataMap[] = [$item, $label, $values['label'], $data[$num]['labelData']];
        $dataMap[] = [$item, $guid_name, $values['value'], $data[$num]['valueData']];
      }
    }

    // Mock typed data manager return.
    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with($plugin_id)
      ->will($this->returnValue(['id' => $plugin_id, 'definition class' => $definition_class_name]));
    $this->typedDataManager->expects($this->any())
      ->method('createDataDefinition')
      ->with($plugin_id)
      ->will($this->returnValue($this->definition));
    $this->typedDataManager->expects($this->any())
      ->method('createListDataDefinition')
      ->with($plugin_id)
      ->will($this->returnValue($listDefinition));
    if (empty($items)) {
      $this->typedDataManager->expects($this->any())
        ->method('getPropertyInstance');
    }
    else {
      // Add as many calls to getPropertyInstance as necessary depending on the
      // number of items that are being returned in the item list.
      $this->typedDataManager->expects($this->any())
        ->method('getPropertyInstance')
        ->will($this->returnValueMap($dataMap));
    }

    // Set the list data type.
    $itemList->setValue($items);

    // Mock the query object.
    $this->xeroQuery->expects($this->any())
      ->method('setType')
      ->with($plugin_id)
      ->will($this->returnSelf());
    $this->xeroQuery->expects($this->any())
      ->method('setMethod')
      ->with('get')
      ->will($this->returnSelf());
    $this->xeroQuery->expects($this->any())
      ->method('setFormat')
      ->with('xml')
      ->will($this->returnSelf());
    $this->xeroQuery->expects($this->any())
      ->method('addCondition')
      ->with('Name', $this->search_term, 'StartsWith')
      ->will($this->returnSelf());
    $this->xeroQuery->expects($this->any())
      ->method('setId')
      ->with($this->search_guid)
      ->will($this->returnSelf());
    $this->xeroQuery->expects($this->any())
      ->method('execute')
      ->willReturn($itemList);

    // Instantiate the XeroAutocompleteController.
    $controller = XeroAutocompleteController::create($this->container);
    // Do the request.
    $request = Request::create('/xero/autocomplete/' . $plugin_id . '/' . $this->search_term, NULL, array('q' => $this->search_term));
    $response = $controller->autocomplete($request, $plugin_id);

    // Assert response content.
    $this->assertEquals(json_encode($expects), $response->getContent());
  }

  /**
   * Provide typed data for Unit tests.
   */
  public function dataProvider() {
    $data = [];
    $this->search_term = $this->getRandomGenerator()->word(10);
    $this->search_guid = $this->createGuid();

    $expects = [
      0 => [
        'value' => $this->createGuid(FALSE),
        'label' => $this->search_term . $this->getRandomGenerator()->word(4),
      ],
      1 => [
        'value' => $this->createGuid(FALSE),
        'label' => $this->search_term . $this->getRandomGenerator()->word(4),
      ]
    ];

    $transaction = [
      0 => [
        'value' => $this->createGuid(FALSE),
        'label' => NULL
      ]
    ];

    $data[] = ['xero_contact', '\Drupal\xero\TypedData\Definition\ContactDefinition', '\Drupal\xero\Plugin\DataType\Contact', []];
    $data[] = ['xero_contact', '\Drupal\xero\TypedData\Definition\ContactDefinition', '\Drupal\xero\Plugin\DataType\Contact', [0 => $expects[0]]];
    $data[] = ['xero_contact', '\Drupal\xero\TypedData\Definition\ContactDefinition', '\Drupal\xero\Plugin\DataType\Contact', $expects];
    $data[] = ['xero_bank_transaction', '\Drupal\xero\TypedData\Definition\BankTransactionDefinition', '\Drupal\xero\Plugin\DataType\BankTransaction', $transaction];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Setup TypedDataManager mock.
    // Typed Data Manager setup.
    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();

    // Setup Xero Query mock.
    $this->xeroQuery = $this->getMockBuilder('\Drupal\xero\XeroQuery')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the container.
    $this->container = new ContainerBuilder();
    $this->container->set('typed_data_manager', $this->typedDataManager);
    $this->container->set('xero.query', $this->xeroQuery);
    \Drupal::setContainer($this->container);
  }

  /**
   * Create a Guid with or without curly braces.
   *
   * @param $braces
   *   (Optional) Return Guid wrapped in curly braces.
   * @return string
   *   Guid string.
   */
  protected function createGuid($braces = TRUE) {
    $hash = strtolower(hash('ripemd128', md5($this->getRandomGenerator()->string(100))));
    $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    $guid .= '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

    if ($braces) {
      return '{' . $guid . '}';
    }

    return $guid;
  }

}
