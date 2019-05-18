<?php

namespace Drupal\Tests\lti_tool_provider\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Tests\UnitTestCase;
use OauthProvider;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Drupal\lti_tool_provider\Authentication\Provider\LTIToolProvider;

define("LTI_TOOL_PROVIDER_NONCE_INTERVAL", (5 * 60));
define("LTI_TOOL_PROVIDER_NONCE_EXPIRY", (1.5 * 60 * 60));

if (!class_exists('\Oauth')) {
    define("OAUTH_OK", 0);
    define("OAUTH_BAD_NONCE", 4);
    define("OAUTH_BAD_TIMESTAMP", 8);
    define("OAUTH_CONSUMER_KEY_UNKNOWN", 16);
}

/**
 * LTIToolProvider unit tests.
 *
 * @ingroup lti_tool_provider
 *
 * @group lti_tool_provider
 *
 * @coversDefaultClass \Drupal\lti_tool_provider\Authentication\Provider\LTIToolProvider
 */
class LTIToolProviderTest extends UnitTestCase
{
    /**
     * The mocked configuration factory.
     *
     * @var ConfigFactoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $configFactory;

    /**
     * The mocked Entity Manager.
     *
     * @var EntityTypeManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityTypeManager;

    /**
     * A mocked logger instance.
     *
     * @var LoggerChannelFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerFactory;

    /**
     * The mocked module handler.
     *
     * @var ModuleHandlerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $moduleHandler;

    /**
     * The mocked private temp store for storing LTI context info.
     *
     * @var PrivateTempStoreFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $tempStore;

    /**
     * The mocked PECL OauthProvider class.
     *
     * @var OauthProvider | mixed
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configFactory = $this->createMock('\Drupal\Core\Config\ConfigFactoryInterface');
        $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
        $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');

        $this->loggerFactory = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannelFactory')
            ->setMethods(['__construct'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->tempStore = $this->getMockBuilder('\Drupal\Core\TempStore\PrivateTempStoreFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = $this->getMockBuilder('\OAuthProvider')
            ->setMethods(['__construct', 'checkOAuthRequest'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider->expects($this->any())
            ->method('checkOAuthRequest')
            ->will($this->returnValue(true));
    }

    /**
     * Test the applies() method.
     *
     * @dataProvider appliesProvider
     * @covers ::applies
     * @covers ::__construct
     * @param $expected
     * @param $request
     */
    public function testApplies($expected, $request)
    {
        $provider = new LTIToolProvider(
            $this->configFactory,
            $this->entityTypeManager,
            $this->loggerFactory,
            $this->moduleHandler,
            $this->tempStore
        );

        $actual = $provider->applies($request);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Request Provider.
     */
    public function appliesProvider()
    {
        return [
            'empty request' => [false, Request::create('/lti', 'POST', [])],
            'get request' => [
                false,
                Request::create(
                    '/lti',
                    'GET',
                    [
                        'oauth_consumer_key' => 'oauth_consumer_key',
                        'lti_message_type' => 'basic-lti-launch-request',
                        'lti_version' => 'LTI-1p0',
                        'resource_link_id' => 'resource_link_id',
                    ]
                ),
            ],
            'LTI-1p0 request' => [
                true,
                Request::create(
                    '/lti',
                    'POST',
                    [
                        'oauth_consumer_key' => 'oauth_consumer_key',
                        'lti_message_type' => 'basic-lti-launch-request',
                        'lti_version' => 'LTI-1p0',
                        'resource_link_id' => 'resource_link_id',
                    ]
                ),
            ],
            'LTI-1p2 request' => [
                true,
                Request::create(
                    '/lti',
                    'POST',
                    [
                        'oauth_consumer_key' => 'oauth_consumer_key',
                        'lti_message_type' => 'basic-lti-launch-request',
                        'lti_version' => 'LTI-1p2',
                        'resource_link_id' => 'resource_link_id',
                    ]
                ),
            ],
            'missing resource link request' => [
                false,
                Request::create(
                    '/lti',
                    'POST',
                    [
                        'oauth_consumer_key' => 'oauth_consumer_key',
                        'lti_message_type' => 'basic-lti-launch-request',
                        'lti_version' => 'LTI-1p0',
                    ]
                ),
            ],
            'empty resource link request' => [
                false,
                Request::create(
                    '/lti',
                    'POST',
                    [
                        'oauth_consumer_key' => 'oauth_consumer_key',
                        'lti_message_type' => 'basic-lti-launch-request',
                        'lti_version' => 'LTI-1p0',
                        'resource_link_id' => '',
                    ]
                ),
            ],
            'missing oauth consumer key request' => [
                false,
                Request::create(
                    '/lti',
                    'POST',
                    [
                        'lti_message_type' => 'basic-lti-launch-request',
                        'lti_version' => 'LTI-1p0',
                        'resource_link_id' => 'resource_link_id',
                    ]
                ),
            ],
            'empty oauth_consumer_key request' => [
                false,
                Request::create(
                    '/lti',
                    'POST',
                    [
                        'oauth_consumer_key' => '',
                        'lti_message_type' => 'basic-lti-launch-request',
                        'lti_version' => 'LTI-1p0',
                        'resource_link_id' => 'resource_link_id',
                    ]
                ),
            ],
        ];
    }

    /**
     * Test the timestampNonceHandler() method.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampNonceHandlerMissingTimestamp()
    {
        $provider = new LTIToolProvider(
            $this->configFactory,
            $this->entityTypeManager,
            $this->loggerFactory,
            $this->moduleHandler,
            $this->tempStore
        );

        $expected = OAUTH_BAD_TIMESTAMP;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the timestampNonceHandler() method.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampNonceHandlerMissingNonceConsumer()
    {
        $provider = new LTIToolProvider(
            $this->configFactory,
            $this->entityTypeManager,
            $this->loggerFactory,
            $this->moduleHandler,
            $this->tempStore
        );

        $this->provider->timestamp = time();
        $expected = OAUTH_BAD_NONCE;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests a nonce timestamp that is too old.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampNonceHandlerOld()
    {
        $provider = new LTIToolProvider(
            $this->configFactory,
            $this->entityTypeManager,
            $this->loggerFactory,
            $this->moduleHandler,
            $this->tempStore
        );

        $this->provider->consumer_key = '';
        $this->provider->nonce = uniqid();
        $this->provider->timestamp = time() - LTI_TOOL_PROVIDER_NONCE_INTERVAL - 10;

        $expected = OAUTH_BAD_TIMESTAMP;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests a nonce timestamp that is almost too old.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampNonceHandlerAlmostTooOld()
    {
        $provider = $this->getNonceSpecificLtiToolProvider();

        $this->provider->consumer_key = '';
        $this->provider->nonce = uniqid();
        $this->provider->timestamp = time() - LTI_TOOL_PROVIDER_NONCE_INTERVAL;

        $expected = OAUTH_OK;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests a nonce timestamp that is current.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampNonceHandlerCurrent()
    {
        $provider = $this->getNonceSpecificLtiToolProvider();

        $this->provider->consumer_key = '';
        $this->provider->nonce = uniqid();
        $this->provider->timestamp = time();

        $expected = OAUTH_OK;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests a nonce timestamp that is almost too new.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampNonceHandlerAlmostTooNew()
    {
        $provider = $this->getNonceSpecificLtiToolProvider();

        $this->provider->consumer_key = '';
        $this->provider->nonce = uniqid();
        $this->provider->timestamp = time() + LTI_TOOL_PROVIDER_NONCE_INTERVAL;

        $expected = OAUTH_OK;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests a nonce timestamp that is too new.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampNonceHandlerTooNew()
    {
        $provider = new LTIToolProvider(
            $this->configFactory,
            $this->entityTypeManager,
            $this->loggerFactory,
            $this->moduleHandler,
            $this->tempStore
        );

        $this->provider->consumer_key = '';
        $this->provider->nonce = uniqid();
        $this->provider->timestamp = time() + LTI_TOOL_PROVIDER_NONCE_INTERVAL + 10;

        $expected = OAUTH_BAD_TIMESTAMP;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Generate a entity type manager for testing timestampNonceHandler.
     */
    public function getNonceSpecificLtiToolProvider()
    {
        $entityTypeManager = $this->entityTypeManager;

        $query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
        $query->expects($this->once())
            ->method('condition')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue([]));

        $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
        $storage->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $storage->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->createMock('Drupal\Core\Entity\EntityInterface')));

        $entityTypeManager
            ->expects($this->once())
            ->method('getStorage')
            ->will($this->returnValue($storage));

        return new LTIToolProvider(
            $this->configFactory,
            $entityTypeManager,
            $this->loggerFactory,
            $this->moduleHandler,
            $this->tempStore
        );
    }

    /**
     * Tests duplicate nonces.
     *
     * @covers ::timestampNonceHandler
     * @covers ::__construct
     */
    public function testTimestampDuplicateNonce()
    {
        $entityTypeManager = $this->entityTypeManager;
        $this->provider->consumer_key = '';
        $this->provider->nonce = uniqid();
        $this->provider->timestamp = time();

        $query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
        $query->expects($this->once())
            ->method('condition')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue([$this->createMock('Drupal\Core\Entity\EntityInterface')]));

        $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
        $storage->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $entityTypeManager
            ->expects($this->once())
            ->method('getStorage')
            ->will($this->returnValue($storage));

        $provider = new LTIToolProvider(
            $this->configFactory,
            $entityTypeManager,
            $this->loggerFactory,
            $this->moduleHandler,
            $this->tempStore
        );

        $expected = OAUTH_BAD_NONCE;
        $actual = $provider->timestampNonceHandler($this->provider);
        $this->assertEquals($expected, $actual);
    }

}
