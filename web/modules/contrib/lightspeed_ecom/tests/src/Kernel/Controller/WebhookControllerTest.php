<?php

namespace Drupal\Tests\lightspeed_ecom\Kernel\Controller;

use Drupal\KernelTests\KernelTestBase;
use Drupal\lightspeed_ecom\ShopInterface;
use Drupal\lightspeed_ecom\Controller\WebhookController;
use Drupal\lightspeed_ecom\Service\SecurityTokenGeneratorInterface;
use Drupal\lightspeed_ecom\Service\Webhook;
use Drupal\lightspeed_ecom\Service\WebhookEvent;
use Drupal\lightspeed_ecom\Service\WebhookRegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class WebhookControllerTest
 *
 * @group Lightspeed eCom
 */
class WebhookControllerTest extends KernelTestBase {

  public static $modules = [
    'lightspeed_ecom'
  ];

  /** @var  WebhookController */
  protected $controller;

  /** @var  HttpKernelInterface */
  protected $httpKernel;

  /** @var  ShopInterface */
  protected $shop;

  public function setUp() {
    parent::setUp();
    $this->httpKernel = $this->container->get('http_kernel');

    // Create the expected default Shop
    $this->shop = $this->container->get('entity_type.manager')
      ->getStorage('lightspeed_ecom_shop')
      ->create([
        'id' => 'default',
        'label' => 'default',
        'api_secret' => $this->randomString(64),
        'api_key' => $this->randomString(64)
      ]);
    $this->shop->save();
  }

  public function testGetRequestShouldReturn405() {
    $request = Request::create(
      '/lightspeed_ecom/webhook/default',
      'GET'
    );
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(405, $response->getStatusCode());
  }

  public function testPostRequestWithoutJsonContentTypeShouldReturn415() {
    $request = Request::create(
      '/lightspeed_ecom/webhook/default',
      'POST'
    );
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(415, $response->getStatusCode());
  }

  public function testPostRequestWithoutSignatureOrTokenShouldReturn403() {
    $request = Request::create(
      '/lightspeed_ecom/webhook/default',
      'POST'
    );
    $request->headers->add(['Content-Type' => 'application/json']);
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(403, $response->getStatusCode());
  }

  public function testPostRequestWithInvalidSignatureShouldReturn403() {
    $request = Request::create(
      '/lightspeed_ecom/webhook/default',
      'POST'
    );
    $request->headers->add([
      'Content-Type' => 'application/json',
      'X-Signature' => $this->randomString(64)
    ]);
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(403, $response->getStatusCode());
  }

  public function testPostRequestWithoutRequiredHeadersShouldReturn400() {
    $request = $this->webhookRequest('products', 'created', 'en', $this->randomObject(), $this->randomString());

    foreach (['X-Event'] as $header) {
      $invalid_request = clone $request;
      $invalid_request->headers->remove($header);
      $response = $this->httpKernel->handle($invalid_request);
      $this->assertEquals(400, $response->getStatusCode());
      $this->assertEquals("Missing required header: $header", $response->getContent());
    }

  }

  public function testPostRequestWithValidSignatureShouldReturn200() {
    $request = $this->webhookRequest('products', 'created', 'en', $this->randomObject(), $this->randomString());
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testPostRequestWithValidTokenShouldReturn200() {
    $request = $this->webhookRequest('products', 'created', 'en', $this->randomObject(), $this->randomString());
    $request->headers->remove('X-Signature');

    /** @var SecurityTokenGeneratorInterface $token */
    $tokenGenerator = $this->container->get('lightspeed.ecom.security_token');
    $request->query->set('token', $tokenGenerator->get($this->shop));
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testPostRequestWithInvalidContentShouldReturn400() {
    $request = $this->webhookRequest('products', 'created', 'en', $this->randomObject(), $this->randomString());

    $invalid_request = Request::create(
      $request->getUri(),
      $request->getMethod(),
      [],
      [],
      [],
      [],
      $this->randomString(64)
    );
    $invalid_request->headers = $request->headers;
    $invalid_request->headers->set('X-Signature', md5($invalid_request->getContent() . $this->shop->apiSecret()));

    $response = $this->httpKernel->handle($invalid_request);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals("Invalid JSON", $response->getContent());
  }

  public function testPostRequestShouldDispatchEvent() {
    $object = $this->randomObject();
    $object_id = $this->randomString();
    $request = $this->webhookRequest('products', 'created', 'en', $object, $object_id);

    // Mock the registry (used as dispatcher by the controller)
    /** @var WebhookEvent $dispatched_event */
    $dispatched_event = null;

    $registry = $this->getMock(WebhookRegistryInterface::class);
    $registry->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function ($event) use (&$dispatched_event) {
        $dispatched_event = $event;
      });
    $this->container->set('lightspeed.ecom.webhook_registry', $registry);

    $this->httpKernel->handle($request);

    $this->assertFalse(empty($dispatched_event), "An event should have been dispatched.");
    $this->assertEquals('products', $dispatched_event->getGroup());
    $this->assertEquals('created', $dispatched_event->getAction());
    $this->assertEquals('default', $dispatched_event->getShopId());
    $this->assertEquals('en', $dispatched_event->getLanguage());
    $this->assertEquals($object_id, $dispatched_event->getObjectId());
    $this->assertEquals((array)$object, (array)$dispatched_event->getPayload());
  }

  /**
   * Create a valid webhook request.
   *
   * @param $group
   * @param $action
   * @param $language
   * @param $object
   * @param $object_id
   *
   * @return Request
   */
  protected function webhookRequest($group, $action, $language, $object, $object_id) {
    $content = json_encode($object);
    $signature = md5($content . $this->shop->apiSecret());
    $request = Request::create(
      '/lightspeed_ecom/webhook/default',
      'POST',
      [],
      [],
      [],
      [],
      $content
    );
    $request->headers->add([
      'Content-Type' => 'application/json',
      'X-Signature' => $signature,
      'X-Language' => $language,
      'X-Event' => "$group/$action",
      "X-". ucfirst(substr($group, 0, -1)) . "-Id" => $object_id
    ]);
    return $request;
  }

}
