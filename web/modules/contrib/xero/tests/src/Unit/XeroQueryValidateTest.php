<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\xero\Plugin\DataType\XeroItemList;
use Drupal\xero\TypedData\Definition\AccountDefinition;
use Drupal\xero\TypedData\Definition\CreditDefinition;
use Drupal\xero\Plugin\DataType\Account;
use Drupal\xero\Plugin\DataType\CreditNote;

/**
 * @group Xero
 */
class XeroQueryValidateTest extends XeroQueryTestBase {

  /**
   * @expectedException InvalidArgumentException
   */
  public function testNoType() {
    $this->query->validate();
  }

  /**
   *
   * @param string $type
   *   The xero data type
   * @param string $method
   *   The method to use, get or post.
   * @param string $format
   *   The format to return into - xml or json
   * @param array $headers
   *   An array of headers.
   * @param boolean $has_condition
   *   The test contains conditions
   * @param boolean $has_data
   *   The test contains xero data to post.
   *
   * @dataProvider queryOptionsProvider
   * @expectedException InvalidArgumentException
   */
  public function testBadQuery($type, $method, $format = NULL, $headers = NULL, $has_condition = FALSE, $has_data = FALSE) {

    // Setup the xero type to use for this test.
    $listDefinition = ListDataDefinition::createFromDataType($type);
    $listDefinition->setClass('\Drupal\xero\Plugin\DataType\XeroItemList');
    $list = XeroItemList::createInstance($listDefinition);

    $definition = $this->setUpDefinition($type, $list);

    $this->query->setType($type);
    $this->query->setMethod($method);

    if (isset($format)) {
      $this->query->setFormat($format);
    }

    if ($has_condition) {
      $this->query->addCondition('Name', '==', 'A');
    }

    if ($has_data) {
      $data_class = $definition->getClass();
      $data = $data_class::createInstance($definition, $data_class::$xero_name, $list);
      $list->appendItem($data);
      $this->query->setData($list);
    }

    $this->query->validate();
  }

  /**
   * Provide various options to test validate method.
   *
   * @return \array[]
   *   An array of indexed arrays of arguments to setup the query class with:
   *   type, method, format, header, hasCondition, hasData
   */
  public function queryOptionsProvider() {
    return [
      ['xero_credit_note', 'post', 'json', NULL, FALSE, TRUE],
      ['xero_credit_note', 'post', NULL, NULL, TRUE, TRUE],
      ['xero_credit_note', 'get', NULL, NULL, FALSE, TRUE],
      ['xero_account', 'get', 'pdf', NULL, NULL],
    ];
  }

  /**
   * Setup the definition for this test.
   *
   * @param $plugin_id
   *   The plugin id for the definition.
   * @param $parent
   *   (Optional) A parent data type.
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition.
   */
  protected function setUpDefinition($plugin_id, $parent = NULL) {
    if ($plugin_id === 'xero_credit_note') {
      $definition = CreditDefinition::create($plugin_id);
      $definition->setClass('\Drupal\xero\Plugin\DataType\CreditNote');
      $data = new CreditNote($definition);
    }
    else {
      $definition = AccountDefinition::create($plugin_id);
      $definition->setClass('\Drupal\xero\Plugin\DataType\Account');
      $data = new Account($definition);
    }

    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with($plugin_id)
      ->willReturn($definition);

    return $definition;
  }

}
