<?php

namespace Drupal\link_click_count\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class LinkClickCountController.
 *
 * @package Drupal\link_click_count\Controller
 */
class LinkClickCountController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs an LinkClickCountController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct(RequestStack $request, Connection $database, AccountProxyInterface $current_user) {
    $this->requestStack = $request;
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * Handle the save of the click happened on link.
   */
  public function saveClicks() {
    $request = $this->requestStack->getCurrentRequest()->query;
    $url = $request->get('url');
    $nid = $request->get('nid');
    if (!empty($url) && !empty($nid)) {
      $this->database->insert('link_click_count')->fields([
        'url' => $url,
        'nid' => $nid,
        'uid' => $this->currentUser->id(),
        'date' => time(),
      ])->execute();
      $isExternal = UrlHelper::isExternal($url);
      if($isExternal) {
        return (new TrustedRedirectResponse($url))->send();
      }
      else {
        return (new RedirectResponse($url))->send();
      }
    }
    throw new AccessDeniedHttpException();
  }

}
