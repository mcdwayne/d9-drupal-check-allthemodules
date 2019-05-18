<?php

/**
 * @file
 * Contains \Drupal\cas_server\Controller\ProxyController.
 */

namespace Drupal\cas_server\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cas_server\Ticket\TicketStorageInterface;
use Drupal\cas_server\Exception\TicketTypeException;
use Drupal\cas_server\Exception\TicketMissingException;
use Drupal\cas_server\Logger\DebugLogger;
use Drupal\cas_server\Configuration\ConfigHelper;
use Drupal\cas_server\Ticket\TicketFactory;

/**
 * Class ProxyController.
 */
class ProxyController implements ContainerInjectionInterface {
  
  /**
   * Used to get the query string parameters from the request.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * The ticket store.
   *
   * @var TicketStorageInterface
   */
  protected $ticketStore;

  /**
   * The logger.
   *
   * @var DebugLogger
   */
  protected $logger;

  /**
   * The configuration helper.
   *
   * @var ConfigHelper
   */
  protected $configHelper;

  /**
   * The ticket factory.
   *
   * @var TicketFactory
   */
  protected $ticketFactory;

  /**
   * Constructor.
   *
   * @param RequestStack $request_stack
   *   Symfony request stack.
   * @param TicketStorageInterface $ticket_store
   *   The ticket store.
   * @param DebugLogger $debug_logger
   *   The logger.
   * @param ConfigHelper $config_helper
   *   The configuration helper.
   * @param TicketFactory $ticket_factory
   *   The ticket factory.
   */
  public function __construct(RequestStack $request_stack, TicketStorageInterface $ticket_store, DebugLogger $debug_logger, ConfigHelper $config_helper, TicketFactory $ticket_factory) {
    $this->requestStack = $request_stack;
    $this->ticketStore = $ticket_store;
    $this->logger = $debug_logger;
    $this->configHelper = $config_helper;
    $this->ticketFactory = $ticket_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('request_stack'), $container->get('cas_server.storage'), $container->get('cas_server.logger'), $container->get('cas_server.config_helper'), $container->get('cas_server.ticket_factory'));
  }

  /**
   * Supply a proxy ticket to a request with a valid proxy-granting ticket.
   */
  public function proxy() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->query->has('format')) {
      if ($request->query->get('format') == 'JSON') {
        $format = 'json';
      }
      else {
        $format = 'xml';
      }
    }
    else {
      $format = 'xml';
    }

    if ($request->query->has('pgt') && $request->query->has('targetService')) {
      $service = urldecode($request->query->get('targetService'));
      if (!$this->configHelper->verifyServiceForSso($service)) {
        $this->logger->log("Failed to proxy $service. Service is not authorized for SSO.");
        return $this->generateUnauthorizedServiceProxyRequestResponse($format, $service);
      }

      $pgt = $request->query->get('pgt');
      try {
        $ticket = $this->ticketStore->retrieveProxyGrantingTicket($pgt);
      }
      catch (TicketTypeException $e) {
        return $this->generateInternalErrorRequestResponse($format, $e->getMessage());
      }
      catch (TicketMissingException $e) {
        return $this->generateInternalErrorRequestResponse($format, 'Ticket not found');
      }

      if (REQUEST_TIME > $ticket->getExpirationTime()) {
        $this->logger->log("Failed to validate ticket: $pgt. Ticket had expired.");
        return $this->generateTicketExpiredRequestResponse($format, $pgt);
      }

      $chain = $ticket->getProxyChain();
      $pt = $this->ticketFactory->createProxyTicket($service, FALSE, $chain, $ticket->getSession(), $ticket->getUid(), $ticket->getUser());

      return $this->generateProxySuccessRequestResponse($format, $pt->getId());

    }
    else {
      return $this->generateInvalidProxyRequestResponse($format);
    }
  }

  /**
   * Generate a proxy success request response.
   *
   * @param string $format
   *   XML or JSON
   * @param string $ticket_string
   *   The ticket Id string.
   *
   * @return Response
   *  The Response object with proxy ticket Id.
   */
  private function generateProxySuccessRequestResponse($format, $ticket_string) {
    if ($format == 'xml') {
      $response_text = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:proxySuccess>
          <cas:proxyTicket>$ticket_string</cas:proxyTicket>
        </cas:proxySuccess>
      </cas:serviceResponse>";
    }
    else if ($format == 'json') {
      //TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate an expired ticket request response.
   *
   * @param string $format
   *   XML or JSON
   * @param string $pgt
   *   The ticket string.
   *
   * @return Response
   *   The Response object with failure message.
   */
  private function generateTicketExpiredRequestResponse($format, $pgt) {
    if ($format == 'xml') {
      $response_text = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:proxyFailure code='INVALID_TICKET'>
          $pgt has expired
        </cas:proxyFailure>
      </cas:serviceResponse>";
    }
    else if ($format == 'json') {
      //TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate an internal error request response.
   *
   * @param string $format
   *   XML or JSON
   * @param string $message
   *   The message to include in the response.
   *
   * @return Response
   *   The Response object with failure message.
   */
  private function generateInternalErrorRequestResponse($format, $message) {
    if ($format == 'xml') {
      $response_text = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:proxyFailure code='INVALID_REQUEST'>
          $message
        <cas:proxyFailure>
      </cas:serviceResponse>";
    }
    else if ($format == 'json') {
      //TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate an invalid request response.
   *
   * @param string $format
   *   XML or JSON
   *
   * @return Response
   *   The Response object with failure message.
   */
  private function generateInvalidProxyRequestResponse($format) {
    if ($format == 'xml') {
      $response_text = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:proxyFailure code='INVALID_REQUEST'>
          'pgt' and 'targetService' parameters are both required
        </cas:proxyFailure>
      </cas:serviceResponse>";

    }
    else if ($format == 'json') {
      // TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate an unauthorized proxy service response.
   *
   * @param string $format
   *   XML or JSON
   * @param string $service
   *   The target service for proxy authentication.
   *
   * @return Response
   *   The Response object with failure message.
   */
  private function generateUnauthorizedServiceProxyRequestResponse($format, $service) {
    if ($format == 'xml') {
      $response_text = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:proxyFailure code='UNAUTHORIZED_SERVICE'>
          $service is not an authorized single sign on service
        </cas:proxyFailure>
      </cas:serviceResponse>";
    }
    else if ($format == 'json') {
      // TODO
    }

    return Response::create($response_text, 200);
  }


}
