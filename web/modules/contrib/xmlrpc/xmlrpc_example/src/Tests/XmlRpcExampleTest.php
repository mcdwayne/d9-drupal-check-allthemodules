<?php

namespace Drupal\xmlrpc_example\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\xmlrpc\XmlRpcTrait;

/**
 * Test case for testing the xmlrpc_example module.
 *
 * This class contains the test cases to check if module is performing as
 * expected.
 *
 * @group xmlrpc
 */
class XmlRpcExampleTest extends WebTestBase {

  use XmlRpcTrait;

  public static $modules = ['xmlrpc_example'];

  protected $xmlRpcUrl;

  /**
   * {@inheritdoc}
   */
  public function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->xmlRpcUrl = $this->getEndpoint();
  }

  /**
   * Perform several calls to the XML-RPC interface to test the services.
   */
  public function testXmlrpcExampleBasic() {
    // Unit test functionality.
    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.add' => [3, 4]]);
    $this->assertEqual($result, 7, 'Successfully added 3+4 = 7');

    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.subtract' => [4, 3]]);
    $this->assertEqual($result, 1, 'Successfully subtracted 4-3 = 1');

    // Make a multicall request.
    $options = [
      'xmlrpc_example.add' => [5, 2],
      'xmlrpc_example.subtract' => [5, 2],
    ];
    $expected = [7, 3];
    $result = xmlrpc($this->xmlRpcUrl, $options);
    $this->assertEqual($result, $expected, 'Successfully called multicall request');

    // Verify default limits.
    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.subtract' => [3, 4]]);
    $this->assertEqual(xmlrpc_errno(), 10002, 'Results below minimum return custom error: 10002');

    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.add' => [7, 4]]);
    $this->assertEqual(xmlrpc_errno(), 10001, 'Results beyond maximum return custom error: 10001');
  }

  /**
   * Perform several calls using XML-RPC web client.
   */
  public function testXmlrpcExampleClient() {
    // Now test the UI.
    // Add the integers.
    $edit = ['num1' => 3, 'num2' => 5];
    $this->drupalPostForm('xmlrpc_example/client', $edit, t('Add the integers'));
    $this->assertText(t('The XML-RPC server returned this response: @num', ['@num' => 8]));

    // Subtract the integers.
    $edit = ['num1' => 8, 'num2' => 3];
    $this->drupalPostForm('xmlrpc_example/client', $edit, t('Subtract the integers'));
    $this->assertText(t('The XML-RPC server returned this response: @num', ['@num' => 5]));

    // Request available methods.
    $this->drupalPostForm('xmlrpc_example/client', $edit, t('Request methods'));
    $this->assertText('xmlrpc_example.add', 'The XML-RPC Add method was found.');
    $this->assertText('xmlrpc_example.subtract', 'The XML-RPC Subtract method was found.');

    // Before testing multicall, verify that method exists.
    $this->assertText('system.multicall', 'The XML-RPC Multicall method was found.');

    // Verify multicall request.
    $edit = ['num1' => 5, 'num2' => 2];
    $this->drupalPostForm('xmlrpc_example/client', $edit, t('Add and Subtract'));

    $this->assertText('[0] =&gt; 7', 'The XML-RPC server returned the addition result.');
    $this->assertText('[1] =&gt; 3', 'The XML-RPC server returned the subtraction result.');
  }

  /**
   * Perform several XML-RPC requests with different server settings.
   */
  public function testXmlrpcExampleServer() {
    // Set different minimum and maximum values.
    $options = ['min' => 3, 'max' => 7];
    $this->drupalPostForm('xmlrpc_example/server', $options, t('Save configuration'));

    $this->assertText(t('The configuration options have been saved'), 'Results limited to >= 3 and <= 7');

    $edit = ['num1' => 8, 'num2' => 3];
    $this->drupalPostForm('xmlrpc_example/client', $edit, t('Subtract the integers'));
    $this->assertText(t('The XML-RPC server returned this response: @num', ['@num' => 5]));

    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.add' => [3, 4]]);
    $this->assertEqual($result, 7, 'Successfully added 3+4 = 7');

    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.subtract' => [4, 3]]);
    $this->assertEqual(xmlrpc_errno(), 10002, 'subtracting 4-3 = 1 returns custom error: 10002');

    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.add' => [7, 4]]);
    $this->assertEqual(xmlrpc_errno(), 10001, 'Adding 7 + 4 = 11 returns custom error: 10001');
  }

  /**
   * Perform several XML-RPC requests.
   *
   * Alter the server behaviour with hook_xmlrpc_alter API.
   *
   * @see hook_xmlrpc_alter()
   */
  public function testXmlrpcExampleAlter() {
    // Enable XML-RPC service altering functionality.
    $options = ['alter_enabled' => TRUE];
    $this->drupalPostForm('xmlrpc_example/alter', $options, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved'), 'Results are not limited due to methods alteration');

    // After altering the functionality, the add and subtract methods have no
    // limits and should not return any error.
    $edit = ['num1' => 80, 'num2' => 3];
    $this->drupalPostForm('xmlrpc_example/client', $edit, t('Subtract the integers'));
    $this->assertText(t('The XML-RPC server returned this response: @num', ['@num' => 77]));

    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.add' => [30, 4]]);
    $this->assertEqual($result, 34, 'Successfully added 30+4 = 34');

    $result = xmlrpc($this->xmlRpcUrl, ['xmlrpc_example.subtract' => [4, 30]]);
    $this->assertEqual($result, -26, 'Successfully subtracted 4-30 = -26');
  }

}
