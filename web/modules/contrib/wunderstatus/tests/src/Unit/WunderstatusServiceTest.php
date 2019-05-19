<?php

namespace Drupal\Tests\wunderstatus\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\State\StateInterface;
use Drupal\wunderstatus\WunderstatusService;
use Drupal\Tests\UnitTestCase;
use Drupal\wunderstatus\WunderstatusInfoCollector;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group wunderstatus
 */
class WunderstatusServiceTest extends UnitTestCase {

  const MODULES = ['module_a 1.0', 'module_b 2.1'];
  const RESPONSE_BODY = '{"key":"value"}';
  const SITE_NAME = 'Site name';
  const SITE_UUID = 'Site UUID';
  const WUNDERSTATUS_AUTH_PASSWORD = 'Password';
  const WUNDERSTATUS_AUTH_USERNAME = 'Username';
  const WUNDERSTATUS_KEY = 'Key';
  const WUNDERSTATUS_MANAGER_ENDPOINT_URL = 'http://www.example.com';
  
  /** @var Client */
  private $client;

  /** @var ImmutableConfig */
  private $config;

  /** @var ConfigFactory */
  private $configFactory;

  /** @var ContainerInterface */
  private $container;

  /** @var LoggerChannel */
  private $logger;

  /** @var RequestInterface */
  private $request;

  /** @var ResponseInterface */
  private $response;

  /** @var StateInterface */
  private $state;

  /** @var WunderstatusInfoCollector */
  private $wunderstatusInfoCollector;
  
  /** @var WunderstatusService */
  private $wunderstatusService;
  
  public function setUp() {
    parent::setUp();

    $this->request = $this->prophesize(RequestInterface::class);

    $this->response = $this->prophesize(ResponseInterface::class);
    $this->response->getBody()->willReturn(self::RESPONSE_BODY);
    $this->response->getStatusCode()->willReturn(Response::HTTP_OK);

    $this->client = $this->prophesize(Client::class);
    $this->client->request('POST', self::WUNDERSTATUS_MANAGER_ENDPOINT_URL, Argument::any())->willReturn($this->response);

    $this->config = $this->prophesize(ImmutableConfig::class);
    $this->config->get('name')->willReturn(self::SITE_NAME);
    $this->config->get('uuid')->willReturn(self::SITE_UUID);

    $this->configFactory = $this->prophesize(ConfigFactory::class);
    $this->configFactory->get('system.site')->willReturn($this->config);

    $this->logger = $this->prophesize(LoggerChannel::class);

    $this->wunderstatusInfoCollector = $this->prophesize(WunderstatusInfoCollector::class);
    $this->wunderstatusInfoCollector->getVersionInfo()->willReturn(self::MODULES);

    $this->state = $this->prophesize(StateInterface::class);
    $this->state->get('wunderstatus_auth_password')->willReturn(self::WUNDERSTATUS_AUTH_PASSWORD);
    $this->state->get('wunderstatus_auth_username')->willReturn(self::WUNDERSTATUS_AUTH_USERNAME);
    $this->state->get('wunderstatus_key')->willReturn(self::WUNDERSTATUS_KEY);
    $this->state->get('wunderstatus_manager_endpoint_url')->willReturn(self::WUNDERSTATUS_MANAGER_ENDPOINT_URL);

    $this->container = $this->prophesize(ContainerInterface::class);
    $this->container->get('config.factory')->willReturn($this->configFactory);
    \Drupal::setContainer($this->container->reveal());
    
    $this->wunderstatusService = new WunderstatusService(
      $this->client->reveal(),
      $this->logger->reveal(),
      $this->state->reveal(),
      $this->wunderstatusInfoCollector->reveal()
    );
  }

  /**
   * @test
   */
  public function sendModuleInfoShouldLogWarningAndReturnFalseWhenAuthenticationKeyIsNotSet() {
    $this->state->get('wunderstatus_key')->willReturn(NULL);

    $this->logger->warning('Wunderstatus authentication key is not set.')->shouldBeCalled();

    $response = $this->wunderstatusService->sendModuleInfo();

    $this->assertFalse($response);
  }

  /**
   * @test
   */
  public function sendModuleInfoShouldLogWarningAndReturnFalseWhenManagerEndpointUrlIsNotSet() {
    $this->state->get('wunderstatus_manager_endpoint_url')->willReturn(NULL);

    $this->logger->warning('Wunderstatus manager endpoint URL is not set.')->shouldBeCalled();

    $response = $this->wunderstatusService->sendModuleInfo();

    $this->assertFalse($response);
  }

  /**
   * @test
   */
  public function sendModuleInfoShouldGetVersionInfoFromWunderstatusInfoCollector() {
    $this->wunderstatusInfoCollector->getVersionInfo()->shouldBeCalled();

    $this->wunderstatusService->sendModuleInfo();
  }

  /**
   * @test
   */
  public function sendModuleInfoShouldPostCorrectDataToEndpointUrl() {
    $expectedRequestOptions = [
      RequestOptions::BODY => Json::encode([
        'key' => self::WUNDERSTATUS_KEY,
        'modules' => self::MODULES,
        'siteName' => self::SITE_NAME,
        'siteUuid' => self::SITE_UUID
      ]),
      RequestOptions::CONNECT_TIMEOUT => WunderstatusService::CONNECT_TIMEOUT_SECONDS,
      RequestOptions::TIMEOUT => WunderstatusService::REQUEST_TIMEOUT_SECONDS,
      RequestOptions::AUTH => [
        self::WUNDERSTATUS_AUTH_USERNAME,
        self::WUNDERSTATUS_AUTH_PASSWORD
      ]
    ];

    $this->client->request('POST', self::WUNDERSTATUS_MANAGER_ENDPOINT_URL, $expectedRequestOptions)->shouldBeCalled();

    $this->wunderstatusService->sendModuleInfo();
  }

  /**
   * @test
   */
  public function sendModuleInfoShouldReturnOkResponseOnSuccess() {
    $response = $this->wunderstatusService->sendModuleInfo();

    $this->assertInstanceOf(ResponseInterface::class, $response);
  }

  /**
   * @test
   */
  public function sendModuleShouldReturnFalseAndLogWarningWhenTheClientThrowsAnException() {
    $this->client = $this->prophesize(Client::class);
    
    $this->client->request(Argument::any(), Argument::any(), Argument::any())->willThrow(
      new RequestException('Exception', $this->request->reveal(), $this->response->reveal())
    );

    $this->logger->warning(
      'Status information send failed. Response: @response',
      ['@response' => self::RESPONSE_BODY])
      ->shouldBeCalled();

    $this->wunderstatusService = new WunderstatusService(
      $this->client->reveal(),
      $this->logger->reveal(),
      $this->state->reveal(),
      $this->wunderstatusInfoCollector->reveal()
    );

    $response = $this->wunderstatusService->sendModuleInfo();

    $this->assertFalse($response);
  }
}