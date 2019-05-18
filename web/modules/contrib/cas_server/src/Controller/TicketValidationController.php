<?php

/**
 * @file
 * Contains \Drupal\cas_server\Controller\TicketValidationController.
 */

namespace Drupal\cas_server\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\cas_server\Ticket\TicketStorageInterface;
use Drupal\cas_server\Exception\TicketTypeException;
use Drupal\cas_server\Exception\TicketMissingException;
use Drupal\cas_server\Logger\DebugLogger;
use Drupal\cas_server\Configuration\ConfigHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Drupal\Component\Utility\Crypt;
use Drupal\cas_server\Ticket\TicketFactory;
use Drupal\cas_server\Ticket\ProxyTicket;
use Drupal\cas_server\Ticket\Ticket;
use Drupal\cas_server\Event\CASAttributesAlterEvent;

/**
 * Class TicketValidationController.
 */
class TicketValidationController implements ContainerInjectionInterface {

  /**
   * Cas protocol version 1 validation request.
   *
   * @var int
   */
  const CAS_PROTOCOL_1 = 0;

  /**
   * Cas protocol 2 service validation request.
   *
   * @var int
   */
  const CAS_PROTOCOL_2_SERVICE = 1;

  /**
   * Cas protocol 2 proxy validation request.
   *
   * @var int
   */
  const CAS_PROTOCOL_2_PROXY = 2;

  /**
   * Cas protocol 3 service validation request.
   *
   * @var int
   */
  const CAS_PROTOCOL_3_SERVICE = 3;

  /**
   * Cas protocol 3 proxy validation request.
   *
   * @var int
   */
  const CAS_PROTOCOL_3_PROXY = 4;

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
   * The http client.
   *
   * @var Client
   */
  protected $httpClient;

  /**
   * The ticket factory.
   *
   * @var TicketFactory
   */
  protected $ticketFactory;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   *   The cas server configuration helper.
   * @param Client $http_client
   *   The HTTP Client library.
   * @param TicketFactory $ticket_factory
   *   The CAS ticket factory.
   */
  public function __construct(RequestStack $request_stack, TicketStorageInterface $ticket_store, DebugLogger $debug_logger, ConfigHelper $config_helper, Client $http_client, TicketFactory $ticket_factory, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_manager) {
    $this->requestStack = $request_stack;
    $this->ticketStore = $ticket_store;
    $this->logger = $debug_logger;
    $this->configHelper = $config_helper;
    $this->httpClient = $http_client;
    $this->ticketFactory = $ticket_factory;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('request_stack'), $container->get('cas_server.storage'), $container->get('cas_server.logger'), $container->get('cas_server.config_helper'), $container->get('http_client'), $container->get('cas_server.ticket_factory'), $container->get('event_dispatcher'), $container->get('entity.manager'));
  }

  /**
   * Global handler for validation requests.
   *
   * This function handles the top-level requirements of a validation request
   * and then delegates out to the relevant protocol-specific handler to
   * generate responses. This is done to avoid duplication of code.
   *
   * @param $validation_type int
   *   An integer representing which type of validation request this is.
   */
  public function validate($validation_type) {
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

    if ($request->query->has('ticket') && $request->query->has('service')) {
      $ticket_string = $request->query->get('ticket');
      $service_string = $request->query->get('service');
      $renew = $request->query->has('renew') ? TRUE : FALSE;

      // Load the ticket. If it doesn't exist or is the wrong type, return the
      // appropriate failure response.
      try {
        switch ($validation_type) {
          case self::CAS_PROTOCOL_1:
          case self::CAS_PROTOCOL_2_SERVICE:
          case self::CAS_PROTOCOL_3_SERVICE:
            $ticket = $this->ticketStore->retrieveServiceTicket($ticket_string);
            break;

          case self::CAS_PROTOCOL_2_PROXY:
          case self::CAS_PROTOCOL_3_PROXY:
            $ticket = $this->ticketStore->retrieveProxyTicket($ticket_string);
            break;
        }
      }
      catch (TicketTypeException $e) {
        $this->logger->log("Failed to validate ticket: $ticket_string. " . $e->getMessage());
        return $this->generateTicketTypeResponse($validation_type, $format);
      }
      catch (TicketMissingException $e) {
        $this->logger->log("Failed to validate ticket: $ticket_string. Ticket was not found in ticket store.");
        return $this->generateTicketMissingResponse($validation_type, $format);
      }

      // Check expiration time against request time.
      if (REQUEST_TIME > $ticket->getExpirationTime()) {
        $this->logger->log("Failed to validate ticket: $ticket_string. Ticket had expired.");
        return $this->generateTicketExpiredResponse($validation_type, $format, $ticket);
      }

      // Check for a service mismatch.
      if ($service_string != $ticket->getService()) {
        $this->logger->log("Failed to validate ticket: $ticket_string. Supplied service $service_string did not match ticket service " . $ticket->getService());

        // Have to delete the ticket.
        $this->ticketStore->deleteServiceTicket($ticket);

        return $this->generateTicketWrongServiceResponse($validation_type, $format, $ticket);
      }

      // Check against renew parameter.
      if ($renew && !$ticket->getRenew()) {
        $this->logger->log("Failed to validate ticket: $ticket_string. Supplied service required direct presentation of credentials.");
        return $this->generateTicketRenewResponse($validation_type, $format, $ticket);
      }

      // Handle proxy callback procedure.
      if ($request->query->has('pgtUrl')) {
        $pgtIou = $this->proxyCallback($request->query->get('pgtUrl'), $ticket);
        if ($pgtIou === FALSE) {
          return $this->generateTicketInvalidProxyCallbackResponse($validation_type, $format);
        }
      }
      else {
        $pgtIou = FALSE;
      }

      // Validation success, first delete the ticket.
      $this->ticketStore->deleteServiceTicket($ticket);

      if ($ticket instanceof ProxyTicket) {
        return $this->generateProxyTicketValidationSuccess($format, $ticket, $pgtIou);
      }

      return $this->generateTicketValidationSuccess($validation_type, $format, $ticket, $pgtIou);


    }
    else {
      $this->logger->log("Validation failed due to missing vital parameters.");
      return $this->generateMissingParametersResponse($validation_type, $format);
    }

  }

  /**
   * Generate a response for failed renew param.
   *
   * @param int $validation_type
   *   The Type of validation request.
   * @param string $format
   *   Either XML or JSON
   * @param Ticket $ticket
   *   The ticket object for context
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateTicketRenewResponse($validation_type, $format, $ticket) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Failure();
    }
    if ($format == 'xml') {
      $response_text =
        '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
          <cas:authenticationFailure code="INVALID_TICKET">
           Ticket did not come from initial login and renew was set
          </cas:authenticationFailure>
         </cas:serviceResponse>';
    }
    elseif ($format == 'json') {
      // TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate a response for incorrect service.
   *
   * @param int $validation_type
   *   The Type of validation request.
   * @param string $format
   *   Either XML or JSON.
   * @param Ticket $ticket
   *   The ticket object for context.
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateTicketWrongServiceResponse($validation_type, $format, $ticket) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Failure();
    }
    if ($format == 'xml') {
      $response_text =
        '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
          <cas:authenticationFailure code="INVALID_SERVICE">
           Provided service did not match ticket service
          </cas:authenticationFailure>
         </cas:serviceResponse>';
    }
    elseif ($format == 'json') {
      // TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate a response for expired ticket.
   *
   * @param int $validation_type
   *   The Type of validation request.
   * @param string $format
   *   Either XML or JSON.
   * @param Ticket $ticket
   *   The ticket object for context.
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateTicketExpiredResponse($validation_type, $format, $ticket) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Failure();
    }
    if ($format == 'xml') {
      $response_text =
        '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
          <cas:authenticationFailure code="INVALID_TICKET">
           Ticket is expired
          </cas:authenticationFailure>
         </cas:serviceResponse>';
    }
    elseif ($format == 'json') {
      // TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate a response for missing ticket.
   *
   * @param int $validation_type
   *   The Type of validation request.
   * @param string $format
   *   Either XML or JSON
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateTicketMissingResponse($validation_type, $format) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Failure();
    }
    if ($format == 'xml') {
      $response_text =
        '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
          <cas:authenticationFailure code="INVALID_TICKET">
           Ticket not present in ticket store
          </cas:authenticationFailure>
         </cas:serviceResponse>';
    }
    elseif ($format == 'json') {
      // TODO
    }

    return Response::create($response_text, 200);
  }


  /**
   * Generate a response for ticket type.
   *
   * @param int $validation_type
   *   The type of validation request.
   * @param string $format
   *   Either XML or JSON.
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateTicketTypeResponse($validation_type, $format) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Failure();
    }
    if ($format == 'xml') {
      $response_text =
        '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
          <cas:authenticationFailure code="INVALID_TICKET_SPEC">
           Ticket was of the incorrect type
          </cas:authenticationFailure>
         </cas:serviceResponse>';
    }
    elseif ($format == 'json') {
      // TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate a response for missing parameters.
   *
   * @param int $validation_type
   *   The type of validation request.
   * @param string $format
   *   Either XML or JSON.
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateMissingParametersResponse($validation_type, $format) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Failure();
    }
    if ($format == 'xml') {
      $response_text =
        '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
          <cas:authenticationFailure code="INVALID_REQUEST">
            Missing required request parameters
          </cas:authenticationFailure>
         </cas:serviceResponse>';
    }
    elseif ($format == 'json') {
      //TODO
      $response_text = '';
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate a response for invalide proxy callback.
   *
   * @param int $validation_type
   *   The type of validation request.
   * @param string $format
   *   Either XML or JSON.
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateTicketInvalidProxyCallbackResponse($validation_type, $format) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Failure();
    }
    if ($format == 'xml') {
      $response_text =
        '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
          <cas:authenticationFailure code="INVALID_PROXY_CALLBACK">
            The credentials specified for proxy authentication do not meet security requirements.
          </cas:authenticationFailure>
         </cas:serviceResponse>';
    }
    elseif ($format == 'json') {
      //TODO
      $response_text = '';
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate the generic Cas protocol version 1 not valid response.
   *
   * @return Response
   *   A Response object with the failure.
   */
  private function generateVersion1Failure() {
    return Response::create("no\n", 200);
  }

  /**
   * Generate a ticket validation success message.
   *
   * @return Response
   *   A Response object with the success message and optional attribute blocks.
   */
  private function generateTicketValidationSuccess($validation_type, $format, $ticket, $pgtIou) {
    if ($validation_type == self::CAS_PROTOCOL_1) {
      return $this->generateVersion1Success($ticket);
    }
    $account = $this->loadUser($ticket->getUid());

    $event = new CASAttributesAlterEvent($account, $ticket);

    $this->eventDispatcher->dispatch(CASAttributesAlterEvent::CAS_ATTRIBUTES_ALTER_EVENT, $event);

    $attributes = $event->getAttributes();
    if ($format == 'xml') {
      $response_text = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:authenticationSuccess>
          <cas:user>" . $ticket->getUser() . "</cas:user>";
      if (!empty($attributes)) {
        $response_text .= "<cas:attributes>\n";
        foreach ($attributes as $key => $value) {
          if (is_array($value)) {
            foreach ($value as $arrayvalue) {
              $response_text .= "<cas:$key>" . $arrayvalue . "</cas:$key>";
            }
          }
          else {
            $response_text .= "<cas:$key>" . $value . "</cas:$key>";
          }
        }
        $response_text .= "</cas:attributes>\n";
      }

      if ($pgtIou) {
        $response_text .= "<cas:proxyGrantingTicket>$pgtIou</cas:proxyGrantingTicket>";
      }
      $response_text .= "</cas:authenticationSuccess>\n</cas:serviceResponse>";

    }
    elseif ($format == 'json') {
      //TODO
    }

    return Response::create($response_text, 200);
  }

  /**
   * Generate a proxy ticket validation success message.
   *
   * @param string $format
   *   Response text format; XML or JSON
   * @param Ticket $ticket
   *   The ticket that was validated.
   * @param string $pgtIou
   *   The pgtIou, if applicable.
   *
   * @return Response
   *   A Response object with the success message, with optional attribute blocks.
   */
  private function generateProxyTicketValidationSuccess($format, $ticket, $pgtIou) {
    $attributes = $this->configHelper->getAttributesForService($ticket->getService());

    $account = $this->loadUser($ticket->getUid());
    $event = new CASAttributesAlterEvent($account, $ticket);
    $this->eventDispatcher->dispatch(CASAttributesAlterEvent::CAS_ATTRIBUTES_ALTER_EVENT, $event);

    if ($format == 'xml') {
      $response_text = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:authenticationSuccess>
          <cas:user>" . $ticket->getUser() . "</cas:user>";
      if (!empty($attributes)) {
        $response_text .= "<cas:attributes>\n";
        foreach ($attributes as $key => $value) {
          if (is_array($value)) {
            foreach ($value as $arrayvalue) {
              $response_text .= "<cas:$key>" . $arrayvalue . "</cas:$key>";
            }
          }
          else {
            $response_text .= "<cas:$key>" . $value . "</cas:$key>";
          }
        }
        $response_text .= "</cas:attributes>\n";
      }

      if ($pgtIou) {
        $response_text .= "<cas:proxyGrantingTicket>$pgtIou</cas:proxyGrantingTicket>";
      }

      $response_text .= "<cas:proxies>";
      foreach ($ticket->getProxyChain() as $pgt_url) {
        $response_text .= "<cas:proxy>$pgt_url</cas:proxy>";
      }
      $response_text .= "</cas:proxies>";

      $response_text .= "</cas:authenticationSuccess>\n</cas:serviceResponse>";

    }
    elseif ($format == 'json') {
      //TODO
    }

    return Response::create($response_text, 200);


  }


  /**
   * Verify the proxy callback url and order a proxy granting ticket issued.
   *
   * @param string $pgtUrl
   *   The supplied callback url to be verified.
   * @param Ticket $ticket
   *   The ticket that was used for this request.
   *
   * @return string|bool
   *   A pgtIou string to pass along in the response, or FALSE on failure.
   */
  protected function proxyCallback($pgtUrl, Ticket $ticket) {
    $url = urldecode($pgtUrl);
    if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
      return FALSE;
    }
    // Verify identity of callback url.
    try {
      $this->httpClient->get($url, ['verify' => TRUE]);
    }
    catch (TransferException $e) {
      return FALSE;
    }
    // Order a proxy granting ticket.
    if ($ticket instanceof ProxyTicket) {
      $chain = array_reverse($ticket->getProxyChain());
      array_push($chain, $url);
      $proxy_chain = array_reverse($chain);
    }
    else {
      $proxy_chain = [$url];
    }

    $pgtIou = 'PGTIOU-';
    $pgtIou .= Crypt::randomBytesBase64(32);

    $pgt = $this->ticketFactory->createProxyGrantingTicket($proxy_chain);
    $pgtId = $pgt->getId();

    // Send a GET request with pgtId and pgtIou. Verify response code.
    if (!empty(parse_url($url, PHP_URL_QUERY))) {
      $full_url = $url .= "&pgtIou=$pgtIou&pgtId=$pgtId";
    }
    else {
      $full_url = $url .= "?pgtIou=$pgtIou&pgtId=$pgtId";
    }
    try {
      $this->httpClient->get($full_url, ['verify' => TRUE]);
    }
    catch (TransferException $e) {
      // If verification failed, delete proxy granting ticket.
      $this->ticketStore->deleteProxyGrantingTicket($pgt);
      return FALSE;
    }

    return $pgtIou;
  }

  /**
   * Generate the generic Cas protocol version 1 valid response.
   *
   * @param Ticket $ticket
   *   The ticket for this request.
   *
   * @return Response
   *   A Response object with the success and username.
   */
  private function generateVersion1Success($ticket) {
    return Response::create("yes\n" . $ticket->getUser() . "\n", 200);
  }

  /**
   * Load a user by uid.
   *
   * @param string $uid
   *   The uid to load.
   *
   * @return \Drupal\user\Entity\User
   *   The user object.
   */
  private function loadUser($uid) {
    return $this->entityTypeManager->getStorage('user')->load($uid);
  }

}
