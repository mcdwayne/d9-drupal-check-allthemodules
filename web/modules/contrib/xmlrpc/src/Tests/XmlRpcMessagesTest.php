<?php

namespace Drupal\xmlrpc\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\xmlrpc\XmlRpcTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Tests large messages and method alterations.
 *
 * @group xmlrpc
 */
class XmlRpcMessagesTest extends XmlRpcTestBase {

  use XmlRpcTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['xmlrpc', 'xmlrpc_test'];

  /**
   * Make sure that XML-RPC can transfer large messages.
   */
  public function testSizedMessages() {
    $sizes = [8, 80, 160];
    foreach ($sizes as $size) {
      $xml_message_l = xmlrpc_test_message_sized_in_kb($size);
      $xml_message_r = $this->xmlRpcGet(['messages.messageSizedInKB' => [$size]]);

      $this->assertEqual($xml_message_l, $xml_message_r, new FormattableMarkup('XML-RPC messages.messageSizedInKB of %s Kb size received', ['%s' => $size]));
    }
  }

  /**
   * Ensure that hook_xmlrpc_alter() can hide even builtin methods.
   */
  public function testAlterListMethods() {
    // Ensure xmlrpc_test.alter() is disabled and retrieve regular list of
    // methods.
    \Drupal::state()->set('xmlrpc_test.alter', FALSE);
    $methods1 = $this->xmlRpcGet(['system.listMethods' => []]);

    // Enable the alter hook and retrieve the list of methods again.
    \Drupal::state()->set('xmlrpc_test.alter', TRUE);
    $methods2 = $this->xmlRpcGet(['system.listMethods' => []]);

    $diff = array_diff($methods1, $methods2);
    $this->assertTrue(is_array($diff) && !empty($diff), 'Method list is altered by hook_xmlrpc_alter');
    $removed = reset($diff);
    $this->assertEqual($removed, 'system.methodSignature', 'Hiding builtin system.methodSignature with hook_xmlrpc_alter works');
  }

  /**
   * Ensure that XML-RPC client sets correct encoding in request http headers.
   */
  public function testRequestContentTypeDefinition() {
    $headers = xmlrpc($this->getEndpoint(), ['test.headerEcho' => []]);
    $this->assertIdentical($headers['Content-Type'], 'text/xml; charset=utf-8');
  }

  /**
   * Check XML-RPC client and server encoding information.
   *
   * Ensure that XML-RPC client sets correct processing instructions for XML
   * documents.
   *
   * Ensure that XML-RPC server sets correct encoding in response http headers
   * and processing instructions for XML documents.
   */
  public function testRequestAndResponseEncodingDefinitions() {
    $url = $this->getEndpoint();
    $client = \Drupal::httpClient();

    // We can't use the xmlrpc() function here, because we have to access the
    // full Guzzle response.
    module_load_include('inc', 'xmlrpc');
    $xmlrpc_request = xmlrpc_request('system.listMethods', []);

    $headers = ['Content-Type' => 'text/xml; charset=utf-8'];
    $request = new Request('POST', $url, $headers, $xmlrpc_request->xml);
    // These may not be initialized in some exception cases.
    $data = NULL;
    $content_type = NULL;
    try {
      $response = $client->send($request);
      $data = $response->getBody();
      $content_type = $response->getHeader('Content-Type');
      $content_type = reset($content_type);
    }
    catch (RequestException $e) {
      $this->fail($e->getMessage(), '"Normal" exception');
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage(), 'Unexpected exception');
    }

    // The request string starts with the XML processing instruction.
    $this->assertIdentical(0, strpos($request->getBody(), '<?xml version="1.0" encoding="utf-8" ?>'), 'Request Processing Instruction is "&lt;?xml version="1.0" encoding="utf-8" ?&gt;"');

    // The response body has to start with the xml processing instruction.
    $this->assertIdentical(strpos($data, '<?xml version="1.0" encoding="utf-8" ?>'), 0, 'Response Processing Instruction is "&lt;?xml version="1.0" encoding="utf-8" ?&gt;"');
    $this->assertIdentical($content_type, 'text/xml; charset=utf-8');
  }

}
