<?php
/**
 * @file
 * Contains \Drupal\salsa_api\SalsaAPITest.
 */

namespace Drupal\Tests\salsa_api\Unit;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\salsa_api\SalsaApi;
use Drupal\salsa_api\SalsaApiInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass \Drupal\salsa_api\SalsaApi
 *
 * @group salsa
 */
class SalsaApiTest extends UnitTestCase {
  /**
   * URL to the Salsa API service.
   *
   * @var string
   */
  protected $url = 'http://example.com';

  /**
   * Username for authentication.
   *
   * @var string.
   */
  protected $username = 'correctUsername';

  /**
   * Authentication password.
   *
   * @var string
   */
  protected $password = 'correctPassword';

  /**
   * The request referer.
   *
   * @var string
   */
  protected $referer = 'https://www.drupal.org/project/salsa_api';

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $client;

  /**
   * The URL Generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $loggerChannelFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * @covers ::query
   * @covers ::__construct
   */
  public function testQuery() {
    $salsa = $this->getSalsaAPI();
    $this->mockQuery('SCRIPT', 'QUERY', 'BODY');

    $result = $salsa->query('SCRIPT', 'QUERY');
    $this->assertEquals($result, 'BODY');
  }

  /**
   * @covers ::deleteTag
   */
  public function testDeleteTag() {
    $salsa = $this->getSalsaAPI();

    $this->mockQuery('/deleteTag', 'table=OBJECT&key=KEY&tag=TAG', 'BODY');

    $salsa->deleteTag('OBJECT', 'KEY', 'TAG');
  }

  /**
   * @covers ::getCount
   */
  public function testGetCount() {
    $salsa = $this->getSalsaAPI();

    $conditions = array(
      'KEY' => array(
        '#operator' => 'OPERATOR',
        '#value' => array(
          'VALUE1',
          'VALUE2',
        ),
      ),
    );

    $this->mockQuery('/api/getCount.sjs', 'object=OBJECT&condition=KEYOPERATORVALUE1%2CVALUE2&columnCount=OBJECT_KEY', '<Test>BODY</Test>');

    $result = $salsa->getCount('OBJECT', $conditions);
    $this->assertEquals($result, 0);
  }

  /**
   * @covers ::getCounts
   * 
   * @expectedException \Drupal\salsa_api\SalsaQueryException
   *   Error message
   */
  public function testGetCountsError() {
    $salsa = $this->getSalsaAPI();

    $object = 'OBJECT';
    $group_by = array(
      'GROUP1',
      'GROUP2',
    );
    $conditions = array(
      'KEY' => array(
        '#operator' => 'OPERATOR',
        '#value' => array(
          'VALUE1',
          'VALUE2',
        ),
      ),
    );
    $order_by = array(
      'ORDER1',
      'ORDER2',
    );
    $limit = 10;
    $body_query_error = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <h3>Error message</h3>
     </data>';

    $this->mockQuery('/api/getCounts.sjs', 'object=OBJECT&condition=KEYOPERATORVALUE1%2CVALUE2&groupBy=GROUP1%2CGROUP2&condition=KEYOPERATORVALUE1%2CVALUE2&columnCount=OBJECT_KEY&orderBy=ORDER1%2CORDER2&limit=10', $body_query_error);

    $result = $salsa->getCounts($object, $group_by, $conditions, NULL, $order_by, $limit);
    $this->assertEquals($result, 0);
  }

  /**
   * @covers ::getCounts
   */
  public function testGetCountsSuccess() {
    $salsa = $this->getSalsaAPI();

    $object = 'OBJECT';
    $group_by = array(
      'GROUP1',
      'GROUP2',
    );
    $conditions = array(
      'KEY' => array(
        '#operator' => 'OPERATOR',
        '#value' => array(
          'VALUE1',
          'VALUE2',
        ),
      ),
    );
    $order_by = array(
      'ORDER1',
      'ORDER2',
    );
    $limit = 10;
    $body_query_error = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <' . $object . '>
       <count>10</count>
      </' . $object . '>
     </data>';

    $this->mockQuery('/api/getCounts.sjs', 'object=' . $object . '&condition=KEYOPERATORVALUE1%2CVALUE2&groupBy=GROUP1%2CGROUP2&condition=KEYOPERATORVALUE1%2CVALUE2&columnCount=' . $object . '_KEY&orderBy=ORDER1%2CORDER2&limit=10', $body_query_error);

    $result = $salsa->getCounts($object, $group_by, $conditions, NULL, $order_by, $limit);
    $this->assertEquals($result, 10);
  }

  /**
   * @covers ::buildConditionString
   */
  public function testBuildConditionStringExplicitOperator() {
    $salsa = $this->getSalsaAPI();

    $conditions = array(
      'KEY' => array(
        '#operator' => 'OPERATOR',
        '#value' => array(
          'VALUE1',
          'VALUE2',
        ),
      ),
    );

    $result = $salsa->buildConditionString($conditions);
    $this->assertEquals($result, 'condition=KEYOPERATORVALUE1%2CVALUE2');
  }

  /**
   * @covers ::buildConditionString
   */
  public function testBuildConditionStringNoExplicitOperator() {
    $salsa = $this->getSalsaAPI();

    $conditions = array(
      '#value' => array(
        'VALUE1',
        'VALUE2',
      ),
    );

    $result = $salsa->buildConditionString($conditions);
    $this->assertEquals($result, 'condition=#valueINVALUE1%2CVALUE2');
  }

  /**
   * @covers ::buildConditionString
   */
  public function testBuildConditionStringLikeOperator() {
    $salsa = $this->getSalsaAPI();

    $conditions = array(
      'KEY' => '%OPERATOR',
    );

    $result = $salsa->buildConditionString($conditions);
    $this->assertEquals($result, 'condition=KEYLIKE%25OPERATOR');
  }

  /**
   * @covers ::getLeftJoin
   */
  public function testgetLeftJoin() {
    $salsa = $this->getSalsaAPI();

    $objects = 'OBJECT';
    $conditions = array(
      'KEY' => array(
        '#operator' => 'OPERATOR',
        '#value' => array(
          'VALUE1',
          'VALUE2',
        ),
      ),
    );
    $limit = 10;
    $include = array(
      'INCLUDE1',
      'INCLUDE2',
    );
    $order_by = array(
      'ORDER1',
      'ORDER2',
    );
    $group_by = array(
      'GROUP1',
      'GROUP2',
    );
    $assertion_expected = 'KEY1';
    $body_query = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <' . $objects . '>
       <item>
        <key>' . $assertion_expected . '</key>
       </item>
       <count>1</count>
      </' . $objects . '>
     </data>';

    $this->mockQuery('/api/getLeftJoin.sjs', 'object=' . $objects . '&condition=KEYOPERATORVALUE1%2CVALUE2&limit=10&include=INCLUDE1%2CINCLUDE2&groupBy=GROUP1%2CGROUP2&orderBy=ORDER1%2CORDER2', $body_query);

    $result = $salsa->getLeftJoin($objects, $conditions, $limit, $include, $order_by, $group_by);
    $this->assertEquals($result[0]->key, $assertion_expected);
  }

  /**
   * @covers ::getObject
   * 
   * @expectedException \Drupal\salsa_api\SalsaQueryException
   *   There was a problem with your submission, invalid object/key pair.
   */
  public function testGetObjectError() {
    $salsa = $this->getSalsaAPI();

    $body_query_error = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <supporter>
       <item>
        <result>error</result>
        <messages>There was a problem with your submission, invalid object/key pair.</messages>
       </item>
      </supporter>
     </data>';

    $this->mockQuery('/api/getObject.sjs', 'object=supporter&key=1', $body_query_error);

    $result = $salsa->getObject('supporter', '1');
    $this->assertEquals(simplexml_load_string($result), $body_query_error);

  }

  /**
   * @covers ::getObject
   */
  public function testGetObjectSuccess() {
    $salsa = $this->getSalsaAPI();

    $body_query_success = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <supporter>
       <item>
        <result>SUCCESS</result>
       </item>
      </supporter>
     </data>';
    $result_success = array(
      'result' => 'SUCCESS',
    );

    $this->mockQuery('/api/getObject.sjs', 'object=supporter&key=1', $body_query_success);

    $result = $salsa->getObject('supporter', '1');

    $this->assertEquals($result, $result_success);
  }

  /**
   * @covers ::getObjects
   */
  public function testGetObjects() {
    $salsa = $this->getSalsaAPI();

    $object = 'OBJECT';
    $conditions = array(
      'KEY' => array(
        '#operator' => 'OPERATOR',
        '#value' => array(
          'VALUE1',
          'VALUE2',
        ),
      ),
    );
    $limit = 10;
    $include = array(
      'INCLUDE1',
      'INCLUDE2',
    );
    $order_by = array(
      'ORDER1',
      'ORDER2',
    );
    $group_by = array(
      'GROUP1',
      'GROUP2',
    );
    $body_query_error = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <' . $object . '>
       <item>
        <key>KEY1</key>
       </item>
       <count>1</count>
      </' . $object . '>
     </data>';

    $this->mockQuery('/api/getObjects.sjs', 'object=' . $object . '&condition=KEYOPERATORVALUE1%2CVALUE2&limit=10&include=INCLUDE1%2CINCLUDE2&groupBy=GROUP1%2CGROUP2&orderBy=ORDER1%2CORDER2', $body_query_error);

    $result = $salsa->getObjects($object, $conditions, $limit, $include, $order_by, $group_by);
    $this->assertEquals($result['KEY1']['key'], 'KEY1');
  }

  /**
   * @covers ::getReport
   * 
   * @expectedException \Drupal\salsa_api\SalsaQueryException
   *   Unable to retrieve report #KEY. Does it exist?
   */
  public function testGetReportError() {
    $salsa = $this->getSalsaAPI();

    $key = 'KEY';
    $body_query_error = '';

    $this->mockQuery('/api/getReport.sjs', 'report_KEY=' . $key, $body_query_error);

    $salsa->getReport($key);
  }

  /**
   * @covers ::getReport
   */
  public function testGetReportSuccess() {
    $salsa = $this->getSalsaAPI();

    $table = 'TABLE';
    $assertion_expected = 'Success';
    $body_query_success = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <report>' . $assertion_expected . '</report>
     </data>';

    $this->mockQuery('/api/getReport.sjs', 'report_KEY=' . $table, $body_query_success);

    $result = $salsa->getReport($table);
    $this->assertEquals($result, $assertion_expected);
  }

  /**
   * @covers ::describe
   * 
   * @expectedException \Drupal\salsa_api\SalsaQueryException
   *   Unable to retrieve schema for table KEY. Does it exist?
   */
  public function testDescribeError() {
    $salsa = $this->getSalsaAPI();

    $table = 'KEY';
    $body_query_error = '';

    $this->mockQuery('/api/describe.sjs', 'object=' . $table, $body_query_error);

    $salsa->describe($table);
  }

  /**
   * @covers ::describe
   * @covers ::parseResult
   * @covers ::convertObjectToArray
   */
  public function testDescribeSuccess() {
    $salsa = $this->getSalsaAPI();

    $table = 'KEY';
    $assertion_expected = 'Success';
    $body_query_success = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <' . $table . '>
       <item>' . $assertion_expected . '</item>
      </' . $table . '>
     </data>';

    $this->mockQuery('/api/describe.sjs', 'object=' . $table, $body_query_success);

    $result = $salsa->describe($table);
    $this->assertEquals($result, $assertion_expected);
  }

  /**
   * @covers ::describe2
   * 
   * @expectedException \Drupal\salsa_api\SalsaQueryException
   *   Unable to retrieve schema for table KEY. Does it exist?
   */
  public function testDescribe2Error() {
    $salsa = $this->getSalsaAPI();

    $table = 'KEY';
    $body_query_error = '';

    $this->mockQuery('/api/describe2.sjs', 'object=' . $table, $body_query_error);

    $salsa->describe2($table);
  }

  /**
   * @covers ::describe2
   */
  public function testDescribe2Success() {
    $salsa = $this->getSalsaAPI();

    $table = 'KEY';
    $assertion_expected = 'Success';
    $body_query_success = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <' . $table . '>
       <item>' . $assertion_expected . '</item>
      </' . $table . '>
     </data>';

    $this->mockQuery('/api/describe2.sjs', 'object=' . $table, $body_query_success);

    $result = $salsa->describe2($table);
    $this->assertEquals($result, $assertion_expected);
  }

  /**
   * @covers ::save
   * 
   * @expectedException \Drupal\salsa_api\SalsaQueryException
   */
  public function testSaveError() {
    $salsa = $this->getSalsaAPI();

    $object = 'OBJECT';
    $fields = array(
      'FIELD1' => 'VALUE1',
      'FIELD2' => 'VALUE2',
    );
    $links = array(
      'LINK1' => array(
        'link' => 'LINK',
        'linkkey' => 'LINKKEY',
      ),
    );
    $body_query_error = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <error>Error message</error>
     </data>';

    $this->mockQuery('/save', 'xml&object=' . $object . '&FIELD1=VALUE1&FIELD2=VALUE2&link=LINK&linkKey=LINKKEY', $body_query_error);

    $salsa->save($object, $fields, $links);
  }

  /**
   * @covers ::save
   */
  public function testSaveSuccess() {
    $salsa = $this->getSalsaAPI();

    $object = 'OBJECT';
    $fields = array(
      'FIELD1' => 'VALUE1',
      'FIELD2' => 'VALUE2',
    );
    $links = array(
      'LINK1' => array(
        'link' => 'LINK',
        'linkkey' => 'LINKKEY',
      ),
    );
    $assertion_expected = 'Success';
    $body_query_success = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <success>
       <key>' . $assertion_expected . '</key>
      </success>
     </data>';

    $this->mockQuery('/save', 'xml&object=' . $object . '&FIELD1=VALUE1&FIELD2=VALUE2&link=LINK&linkKey=LINKKEY', $body_query_success);

    $result = $salsa->save($object, $fields, $links);
    $this->assertEquals($result, 0);
  }

  /**
   * @covers ::save
   */
  public function testSaveSupporterNoLangcode() {
    $salsa = $this->getSalsaAPI();

    $language = new Language([
      'id' => 'de',
    ]);
    $this->languageManager->expects($this->once())
      ->method('getCurrentLanguage')
      ->willReturn($language);

    $object = 'supporter';
    $fields = array(
      'First_Name' => 'John',
      'Last_Name' => 'Example',
    );
    $assertion_expected = 'Success';
    $body_query_success = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <success>
       <key>' . $assertion_expected . '</key>
      </success>
     </data>';

    $this->mockQuery('/save', 'xml&object=' . $object . '&First_Name=John&Last_Name=Example&Language_Code=ger', $body_query_success);

    $result = $salsa->save($object, $fields);
    $this->assertEquals($result, 0);
  }

  /**
   * @covers ::save
   */
  public function testSaveSupporterWithLangcode() {
    $salsa = $this->getSalsaAPI();

    $object = 'supporter';
    $fields = array(
      'First_Name' => 'John',
      'Last_Name' => 'Example',
      'Language_Code' => 'en',
    );
    $assertion_expected = 'Success';
    $body_query_success = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <success>
       <key>' . $assertion_expected . '</key>
      </success>
     </data>';

    $this->mockQuery('/save', 'xml&object=' . $object . '&First_Name=John&Last_Name=Example&Language_Code=eng', $body_query_success);

    $result = $salsa->save($object, $fields);
    $this->assertEquals($result, 0);
  }

  /**
   * @covers ::parseResult
   * @covers ::convertObjectToArray
   * 
   * @expectedException \Drupal\salsa_api\SalsaQueryException
   */
  public function testParseResultError() {
    $salsa = $this->getSalsaAPI();

    $table = 'KEY';
    $body_query_error = '<div class=\'sjs error\'>';

    $this->mockQuery('/api/describe.sjs', 'object=' . $table, $body_query_error);

    $salsa->describe($table);
  }

  /**
   * @covers ::connect
   *
   * @expectedException \Drupal\salsa_api\SalsaConnectionException
   */
  public function testConnectError() {
    $salsa = $this->getSalsaAPI();
    $logger_channel = $this->createMock('\Drupal\Core\Logger\LoggerChannelInterface');

    $xml = '<?xml version="1.0"?>
    <data organization_KEY="1">
      <message>Error</message>
     </data>';
    $this->mockLogin($xml);

    $this->loggerChannelFactory->expects($this->at(0))
      ->method('get')
      ->with($this->equalTo('salsa'))
      ->will($this->returnValue($logger_channel));

    $logger_channel->expects($this->at(0))
      ->method('error')
      ->with($this->equalTo('%url/api/authenticate.sjs?email=**&password=** call result: %reply'), $this->equalTo([
        '%url' => 'http://example.com',
        '%reply' => simplexml_load_string($xml)->asXML(),
      ]));

    $salsa->connect();
  }

  /**
   * @covers ::connect
   */
  public function testConnectSuccessAndDestruct() {
    $salsa = $this->getSalsaAPI();

    $xml = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <message>Successful Login</message>
     </data>';
    $this->mockLogin($xml);

    $result = $salsa->connect();
    $this->assertTrue($result);

    // To verify that a connection exists, we have to call connect() again.
    $salsa->connect();
  }

  /**
   * @covers ::testConnect
   */
  public function testTestConnectCorrect() {
    $salsa = $this->getSalsaAPI();

    $xml = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <message>Successful Login</message>
     </data>';
    $this->mockLogin($xml, $this->url . '/api/authenticate.sjs');

    $this->assertEquals(SalsaApiInterface::CONNECTION_OK, $salsa->testConnect($this->url, $this->username, $this->password));
  }

  /**
   * @covers ::testConnect
   */
  public function testTestConnectAuthenticationFailed() {
    $salsa = $this->getSalsaAPI();

    $xml = '<?xml version="1.0"?>
     <data>
      <error>Invalid login, please try again.</error>
     </data>';
    $this->mockLogin($xml, $this->url . '/api/authenticate.sjs');

    $this->assertEquals(SalsaApiInterface::CONNECTION_AUTHENTICATION_FAILED, $salsa->testConnect($this->url, $this->username, $this->password));
  }

  /**
   * @covers ::testConnect
   */
  public function testTestConnectWrongUrlException() {
    $salsa = $this->getSalsaAPI();

    $this->client->expects($this->at(0))
      ->method('request')
      ->with($this->equalTo('GET'), $this->equalTo($this->url . '/api/authenticate.sjs'), $this->equalTo([
        'query' => [
          'email' => $this->username,
          'password' => $this->password,
        ]]))
      ->will($this->throwException(new RequestException('', new Request('GET', $this->url . '/api/authenticate.sjs'))));

    $this->assertEquals(SalsaApiInterface::CONNECTION_WRONG_URL, $salsa->testConnect($this->url, $this->username, $this->password));
  }

  /**
   * @covers ::testConnect
   */
  public function testTestConnectWrongUrl() {
    $salsa = $this->getSalsaAPI();

    $xml = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <message>No match</message>
     </data>';
    $this->mockLogin($xml, $this->url . '/api/authenticate.sjs');

    $this->assertEquals(SalsaApiInterface::CONNECTION_WRONG_URL, $salsa->testConnect($this->url, $this->username, $this->password));
  }

  /**
   * Returns a Salsa API instance with the necessary mock objects injected.
   *
   * @return \Drupal\salsa_api\SalsaApi
   *   Returns a Salsa API instance with the necessary mock objects injected.
   */
  protected function getSalsaAPI() {
    $this->clientFactory = $this->createMock(ClientFactory::class);
    $this->client = $this->createMock(Client::class);
    $this->clientFactory->expects($this->any())
      ->method('fromOptions')
      ->with($this->equalTo(array(
        'base_uri' => $this->url,
        'cookies' => TRUE,
        'connect_timeout' => 10,
        'timeout' => 10,
      )))
      ->willReturn($this->client);

    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->with($this->equalTo('<current>'), $this->equalTo([]), $this->equalTo(['absolute' => TRUE]))
      ->willReturn($this->referer);

    $this->loggerChannelFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->languageManager = $this->createMock(LanguageManagerInterface::class);

    return new SalsaApi($this->getSalsaApiConfigFactory(), $this->clientFactory, $this->urlGenerator, $this->loggerChannelFactory, $this->languageManager);
  }

  /**
   * Mocks the login response.
   *
   * @param string $xml
   *   The XML response
   * @param string $url
   *   The expected login request URL.
   */
  protected function mockLogin($xml, $url = '/api/authenticate.sjs') {
    $login_response = $this->createMock(ResponseInterface::class);
    $login_response->expects($this->atLeastOnce())
      ->method('getBody')
      ->willReturn($xml);

    $this->client->expects($this->at(0))
      ->method('request')
      ->with($this->equalTo('GET'), $this->equalTo($url), $this->equalTo([
        'query' => [
          'email' => $this->username,
          'password' => $this->password,
        ]]))
      ->willReturn($login_response);
  }

  /**
   * Query helper test function.
   *
   * Will fail in case a test calls query() multiple times.
   *
   * @param string $script
   *   The file path string.
   * @param string $query
   *   The file query string.
   * @param string $body
   *   The expected return value of getBody().
   */
  protected function mockQuery($script, $query, $body) {
    $xml = '<?xml version="1.0"?>
     <data organization_KEY="1">
      <message>Successful Login</message>
     </data>';
    $this->mockLogin($xml);

    $query_response = $this->createMock(ResponseInterface::class);
    $query_response->expects($this->at(0))
      ->method('getBody')
      ->willReturn($body);

    $request = new Request('GET', $script . '?' . $query, [
      'Referer' => $this->referer,
    ]);

      // query() -> getRequest() -> createRequest()
    $this->client->expects($this->at(1))
      ->method('send')
      ->with($this->equalTo($request))
      ->willReturn($query_response);
  }

  /**
   * Helper function.
   *
   * @return \PHPUnit_Framework_MockObject_MockBuilder
   *   The config factory.
   */
  protected function getSalsaApiConfigFactory() {
    $config_factory = $this->getConfigFactoryStub(
      array(
        'salsa_api.settings' => array(
          'query_timeout' => 10,
          'url' => $this->url,
          'username' => $this->username,
          'password' => $this->password,
          'language_code_mapping' => [
            'de' => 'ger',
            'en' => 'eng',
          ]
        ),
      )
    );
    return $config_factory;
  }

}
