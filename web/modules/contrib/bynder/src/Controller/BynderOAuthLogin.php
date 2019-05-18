<?php

namespace Drupal\bynder\Controller;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Exception\UnableToConnectException;
use Drupal\bynder\Plugin\EntityBrowser\Widget\BynderSearch;
use Drupal\bynder\Plugin\EntityBrowser\Widget\BynderUpload;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Controller for oAuth login.
 */
class BynderOAuthLogin extends ControllerBase {

  /**
   * The Bynder API service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   */
  protected $bynder;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a BynderOAuthLogin class instance.
   *
   * @param \Drupal\bynder\BynderApiInterface $bynder
   *   The Bynder API service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   */
  public function __construct(BynderApiInterface $bynder, LoggerChannelFactoryInterface $logger) {
    $this->bynder = $bynder;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bynder_api'),
      $container->get('logger.factory')
    );
  }

  /**
   * The oAuth login controller.
   */
  public function oAuth(Request $request) {
    try {
      if ($request->query->get('oauth_token')) {
        $this->bynder->finishOAuthTokenRetrieval($request);
        return [
          '#markup' => '<script>window.close()</script>',
          '#allowed_tags' => ['script'],
        ];
      }
      else {
        $url = $this->bynder->initiateOAuthTokenRetrieval();
        $response = new TrustedRedirectResponse($url->toString(), SymfonyResponse::HTTP_SEE_OTHER);
        $response->setMaxAge(-1);
        return $response;
      }
    }
    catch (GuzzleException $e) {
      (new UnableToConnectException())->displayMessage();;
      $this->logger->get('bynder')->error('Bynder OAuth login failed: @message', ['@message' => $e->getMessage()]);

      return [];
    }
  }

  /**
   * Checks access to oAuth login.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result.
   */
  public function access(AccountInterface $account) {
    if ($this->bynder->hasAccessToken()) {
      return AccessResult::forbidden();
    }

    $browsers = $this->entityTypeManager()->getStorage('entity_browser')->loadMultiple();
    /** @var \Drupal\entity_browser\Entity\EntityBrowser $browser */
    foreach ($browsers as $browser) {
      if ($account->hasPermission('access ' . $browser->id() . ' entity browser pages')) {
        foreach ($browser->getWidgets() as $widget) {
          if ($widget instanceof BynderSearch || $widget instanceof BynderUpload) {
            return AccessResult::allowed();
          }
        }
      }
    }

    return AccessResult::forbidden();
  }

}
