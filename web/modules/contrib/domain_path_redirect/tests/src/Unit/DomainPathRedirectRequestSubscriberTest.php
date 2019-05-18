<?php

namespace Drupal\Tests\domain_path_redirect\Unit;

use Drupal\Core\Language\Language;
use Drupal\domain\DomainInterface;
use Drupal\domain_path_redirect\Entity\DomainPathRedirect;
use Drupal\domain_path_redirect\EventSubscriber\DomainPathRedirectRequestSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Tests the domain path redirect logic.
 *
 * @group redirect
 *
 * @coversDefaultClass \Drupal\domain_path_redirect\EventSubscriber\DomainPathRedirectRequestSubscriber
 */
class DomainPathRedirectRequestSubscriberTest extends UnitTestCase {

  /**
   * Data provider for both tests.
   *
   * @return array
   *   Nested arrays of values to check:
   *   - $request_uri
   *   - $request_query
   *   - $redirect_uri
   *   - $redirect_query
   *   - $hostname
   */
  public function getDomainPathRedirectData() {
    return [
      [
        'non-existing',
        ['key' => 'val'],
        '/test-path',
        ['dummy' => 'value'],
        'example1.com',
        'example1_com',
      ],
      [
        'non-existing/',
        ['key' => 'val'],
        '/test-path',
        ['dummy' => 'value'],
        'example2.com',
        'example1_com',
      ],
    ];
  }

  /**
   * @covers ::onKernelRequestCheckDomainPathRedirect
   * @dataProvider getDomainPathRedirectData
   */
  public function testRedirectLogicWithQueryRetaining($request_uri, $request_query, $redirect_uri, $redirect_query, $hostname, $domain_id) {

    // The expected final query. This query must contain values defined
    // by the redirect entity and values from the accessed url.
    $final_query = $redirect_query + $request_query;

    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();

    $url->expects($this->once())
      ->method('setAbsolute')
      ->with(TRUE)
      ->willReturn($url);

    $url->expects($this->once())
      ->method('getOption')
      ->with('query')
      ->willReturn($redirect_query);

    $url->expects($this->once())
      ->method('setOption')
      ->with('query', $final_query);

    $url->expects($this->once())
      ->method('toString')
      ->willReturn($redirect_uri);

    $domain = $this->getDomainStub($hostname);
    $redirect = $this->getRedirectStub($url, $domain_id);

    $event = $this->callOnKernelRequestCheckDomainPathRedirect($redirect, $domain, $request_uri, $request_query, TRUE);

    $this->assertTrue($event->getResponse() instanceof RedirectResponse);
    $response = $event->getResponse();
    $this->assertEquals('/test-path', $response->getTargetUrl());
    $this->assertEquals(301, $response->getStatusCode());
    $this->assertEquals(1, $response->headers->get('X-Redirect-ID'));
  }

  /**
   * @covers ::onKernelRequestCheckDomainPathRedirect
   * @dataProvider getDomainPathRedirectData
   */
  public function testDomainPathRedirectLogicWithoutQueryRetaining($request_uri, $request_query, $redirect_uri, $redirect_query, $hostname, $domain_id) {
    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();

    $url->expects($this->once())
      ->method('setAbsolute')
      ->with(TRUE)
      ->willReturn($url);

    // No query retaining, so getOption should not be called.
    $url->expects($this->never())
      ->method('getOption');
    $url->expects($this->never())
      ->method('setOption');

    $url->expects($this->once())
      ->method('toString')
      ->willReturn($redirect_uri);

    $domain = $this->getDomainStub($hostname);
    $redirect = $this->getRedirectStub($url, $domain_id);
    $event = $this->callOnKernelRequestCheckDomainPathRedirect($redirect, $domain, $request_uri, $request_query, FALSE);

    $this->assertTrue($event->getResponse() instanceof RedirectResponse);
    $response = $event->getResponse();
    $this->assertEquals($redirect_uri, $response->getTargetUrl());
    $this->assertEquals(301, $response->getStatusCode());
    $this->assertEquals(1, $response->headers->get('X-Redirect-ID'));
  }

  /**
   * Gets the domain mock object.
   *
   * @param string $hostname
   *   Url to be returned from getHostname.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked domain object.
   */
  protected function getDomainStub($hostname) {
    $domain = $this->getMockBuilder('Drupal\domain\Entity\Domain')
      ->disableOriginalConstructor()
      ->getMock();

    $domain->expects($this->any())
      ->method('getHostname')
      ->with($hostname)
      ->willReturn($domain);

    return $domain;
  }

  /**
   * Gets the redirect mock object.
   *
   * @param string $url
   *   Url to be returned from getRedirectUrl.
   * @param string $domain_id
   *   Domain to be returned from getDomain.
   * @param int $status_code
   *   The redirect status code.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked redirect object.
   */
  protected function getRedirectStub($url, $domain_id, $status_code = 301) {
    $redirect = $this->getMockBuilder('Drupal\domain_path_redirect\Entity\DomainPathRedirect')
      ->disableOriginalConstructor()
      ->getMock();

    $redirect->expects($this->once())
      ->method('getRedirectUrl')
      ->will($this->returnValue($url));
    $redirect->expects($this->any())
      ->method('getStatusCode')
      ->will($this->returnValue($status_code));
    $redirect->expects($this->any())
      ->method('id')
      ->willReturn(1);
    $redirect->expects($this->any())
      ->method('getDomain')
      ->willReturn($domain_id);
    $redirect->expects($this->once())
      ->method('getCacheTags')
      ->willReturn(['domain_path_redirect:1']);

    return $redirect;
  }

  /**
   * Instantiates the subscriber and runs onKernelRequestCheckRedirect()
   *
   * @param \Drupal\domain_path_redirect\Entity\DomainPathRedirect $redirect
   *   The redirect entity.
   * @param \Drupal\domain\DomainInterface $domain
   *   A domain record object.
   * @param string $request_uri
   *   The URI of the request.
   * @param array $request_query
   *   The query that is supposed to come via request.
   * @param bool $retain_query
   *   Flag if to retain the query through the redirect.
   *
   * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
   *   The response event.
   */
  protected function callOnKernelRequestCheckDomainPathRedirect(DomainPathRedirect $redirect, DomainInterface $domain, $request_uri, array $request_query, $retain_query) {
    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query));
    $request = $event->getRequest();

    $checker = $this->getMockBuilder('Drupal\redirect\RedirectChecker')
      ->disableOriginalConstructor()
      ->getMock();
    $checker->expects($this->any())
      ->method('canRedirect')
      ->will($this->returnValue(TRUE));

    $context = $this->getMockBuilder('Symfony\Component\Routing\RequestContext')->getMock();

    $inbound_path_processor = $this->getMockBuilder('Drupal\Core\PathProcessor\InboundPathProcessorInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $inbound_path_processor->expects($this->any())
      ->method('processInbound')
      ->with($request->getPathInfo(), $request)
      ->will($this->returnValue($request->getPathInfo()));

    $alias_manager = $this->getMockBuilder('Drupal\Core\Path\AliasManager')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->getMock();
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface')
      ->getMock();
    $logger = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannelFactoryInterface')
      ->getMock();
    $url_generator = $this->getMockBuilder('Drupal\Core\Routing\UrlGeneratorInterface')
      ->getMock();
    $messenger = $this->getMockBuilder('Drupal\Core\Messenger\MessengerInterface')
      ->getMock();
    $route_match = $this->getMockBuilder('Drupal\Core\Routing\RouteMatchInterface')
      ->getMock();

    $subscriber = new DomainPathRedirectRequestSubscriber(
      $this->getDomainPathRedirectRepositoryStub('findMatchingRedirect', $redirect),
      $this->getLanguageManagerStub(),
      $this->getConfigFactoryStub(['redirect.settings' => ['passthrough_querystring' => $retain_query]]),
      $alias_manager,
      $module_handler,
      $entity_manager,
      $checker,
      $context,
      $inbound_path_processor,
      $this->getDomainNegotiatorStub('getActiveDomain', $domain),
      $logger,
      $url_generator,
      $messenger,
      $route_match
    );

    // Run the main redirect method.
    $subscriber->onKernelRequestCheckDomainPathRedirect($event);
    return $event;
  }

  /**
   * Gets the domain negotiator mock object.
   *
   * @param string $method
   *   Method to mock - either load() or findMatchingRedirect().
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain object to be returned.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The domain negotiator.
   */
  protected function getDomainNegotiatorStub($method, DomainInterface $domain) {
    $domain_negotiator = $this->getMockBuilder('Drupal\domain\DomainNegotiatorInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $domain_negotiator->expects($this->any())
      ->method($method)
      ->will($this->returnValue($domain));

    return $domain_negotiator;
  }

  /**
   * Gets the domain path redirect repository mock object.
   *
   * @param string $method
   *   Method to mock - either load() or findMatchingRedirect().
   * @param \Drupal\domain_path_redirect\Entity\DomainPathRedirect $redirect
   *   The redirect entity to be returned.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The redirect repository.
   */
  protected function getDomainPathRedirectRepositoryStub($method, DomainPathRedirect $redirect) {
    $repository = $this->getMockBuilder('Drupal\domain_path_redirect\DomainPathRedirectRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $repository->expects($this->any())
      ->method($method)
      ->will($this->returnValue($redirect));

    return $repository;
  }

  /**
   * Gets post response event.
   *
   * @param array $headers
   *   Headers to be set into the response.
   *
   * @return \Symfony\Component\HttpKernel\Event\PostResponseEvent
   *   The post response event object.
   */
  protected function getPostResponseEvent(array $headers = []) {
    $http_kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')
      ->getMock();
    $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();

    $response = new Response('', 301, $headers);

    return new PostResponseEvent($http_kernel, $request, $response);
  }

  /**
   * Gets response event object.
   *
   * @param string $path_info
   *   A string containing either an URI or a file or directory path.
   * @param string $query_string
   *   The query string of the request.
   *
   * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
   *   The response event.
   */
  protected function getGetResponseEventStub($path_info, $query_string) {
    $request = Request::create($path_info . '?' . $query_string, 'GET', [], [], [], ['SCRIPT_NAME' => 'index.php']);

    $http_kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')
      ->getMock();
    return new GetResponseEvent($http_kernel, $request, 'test');
  }

  /**
   * Gets the language manager mock object.
   *
   * @return \Drupal\language\ConfigurableLanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mocked language manager object.
   */
  protected function getLanguageManagerStub() {
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(['id' => 'en'])));

    return $language_manager;
  }

}
