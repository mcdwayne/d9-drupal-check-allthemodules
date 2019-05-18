<?php

namespace Drupal\micro_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\micro_sso\MicroSsoHelperInterface;

/**
 * Default controller for the ucms_sso module.
 */
class MicroSsoController extends ControllerBase {

  /**
   * The micro site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The micro SSO helper.
   *
   * @var \Drupal\micro_sso\MicroSsoHelperInterface
   */
  protected $microSsoHelper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\micro_sso\MicroSsoHelperInterface $micro_sso_helper
   *   The micro sso helper service.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The micro site negotiator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MicroSsoHelperInterface $micro_sso_helper, SiteNegotiatorInterface $site_negotiator, EntityTypeManagerInterface $entity_type_manager) {
    $this->microSsoHelper = $micro_sso_helper;
    $this->negotiator = $site_negotiator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('micro_sso.helper'),
      $container->get('micro_site.negotiator'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Check on the master host if the user is authenticated and the request is valid.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Json response.
   */
  public function check(Request $request) {
    $source = $request->get('source');

    if (!$origin = $this->microSsoHelper->getOrigin()) {
      return $this->getResponse(401, 'HTTP Origin is not a micro site.');
    }

    $source_uri = parse_url($source);
    if ($origin != $source_uri['host']) {
      // Return $this->getResponse(401, 'An error occurs with the origin request.');.
    }

    if (!$this->microSsoHelper->userIsAuthenticated()) {
      return $this->getResponse(403, 'User not authenticated on master.');
    }

    $site = $this->negotiator->loadByHostname($origin);
    if ($site instanceof SiteInterface) {
      // Users with permission "administer site entities" can always use the
      // sso login on the site entities. So we check only for users without this
      // permission.
      if (!$this->microSsoHelper->getCurrentUser()->hasPermission('administer site entities')) {
        // We check that the current user logged in on the master hosts is a
        // member of the factory site.
        if (!in_array($this->microSsoHelper->getCurrentUser()->id(), $site->getAllUsersId())
        || !$this->microSsoHelper->getCurrentUser()->hasPermission('use sso login on micro site')) {
          return $this->getResponse(403, 'User not allowed to login.');
        }
      }
    }
    else {
      return $this->getResponse(401, 'HTTP Origin is not a micro site.');
    }

    // User 42 is logged on site A (master).
    // User 42 goes on site B (slave).
    // Site B does an AJAX request on A (this callback).
    // - Token exists: it returns OK, then redirect the client on itself
    //   with session id as token on site B (the next callback).
    // - Token is wrong: it returns NO, a JavaScript cookie is set and it
    //   prevents the user from loggin in for a few minutes.
    return $this->getResponse(200, 'Use SSO login.', NULL, $this->microSsoHelper->writeToken($origin));
  }

  /**
   * Attempt to login the user on the micro site.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function login(Request $request) {
    $token = $request->get('token');
    $destination = $request->get('destination');

    if (empty($token)) {
      return $this->getResponse(401, 'Empty token.');
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $token)) {
      return $this->getResponse(401, 'Token not valid.');
    }

    // Retrieve stored temporary token storage.
    $domain = $request->server->get('HTTP_HOST');
    $cid = $domain . ':' . $token;

    if ($this->microSsoHelper->userIsAuthenticated()) {
      // User is logged in, attempt token removal and exit.
      $this->microSsoHelper->getCacheSso()->delete($cid);
      return $this->getResponse(403, 'User already authenticated.');
    }

    $entry = $this->microSsoHelper->getCacheSso()->get($cid);
    if (!$entry) {
      return $this->getResponse(401, 'No corresponding entry to token found.');
    }
    $record = $entry->data;
    // Always invalidate this token for future use that's a one time thing.
    $this->microSsoHelper->getCacheSso()->delete($cid);

    // Check that this record was for this site, this IP, and with a good validity.
    if ($this->microSsoHelper->getRequest()->getClientIp() !== $record['ip'] || $domain != $record['origin'] || $record['validity'] < $this->microSsoHelper->getRequestTime() || empty($record['uid'])) {
      return $this->getResponse(401, 'Token not validated.');
    }

    $uid = $record['uid'];
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user instanceof UserInterface) {
      return $this->getResponse(401, 'An error occurs with the user.');
    }
    user_login_finalize($user);
    return $this->getResponse(200, 'User logged in.', TRUE, [], $destination);
  }

  /**
   * Helper function to return the Json Response.
   *
   * @param int $status
   *   The status code.
   * @param string $reason
   *   An explicit message about the status code returned.
   * @param bool|NULL $success
   *   A boolean set explicitly to login the user on the micro site.
   * @param array $login
   *   An array with the uri to login, the token and an optional destination.
   * @param string $destination
   *   The optional destination once the user is logged in.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  protected function getResponse($status, $reason, $success = NULL, $login = [], $destination = NULL) {
    $response = new JsonResponse([
      'status' => $status,
      'reason' => $reason,
      'success' => $success,
      'login' => $login,
      'destination' => $destination,
    ]);
    $response->setPrivate()->setMaxAge(0);
    return $response;
  }

}
