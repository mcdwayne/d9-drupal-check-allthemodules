<?php

namespace Drupal\strava\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Url;
use Drupal\strava\Api\Strava;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class StravaController extends ControllerBase {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Strava
   *
   * @var \Drupal\strava\Api\Strava
   */
  private $strava;

  /**
   * Constructs the StravaController.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   */
  public function __construct(RequestStack $request_stack, LoggerChannelFactory $logger_factory) {
    $this->requestStack = $request_stack;
    $this->loggerFactory = $logger_factory;

    $this->strava = new Strava();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('logger.factory')
    );
  }

  /**
   * Display Strava application details.
   */
  public function applicationPage() {
    $build = [
      '#title' => 'Strava',
    ];

    $build['strava_login'] = \Drupal::service('plugin.manager.block')
      ->createInstance('strava_login_block')
      ->build();

    // Add administrative menu items for enabled submodules.
    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters('admin');
    $parameters->setRoot('strava');
    $parameters->setTopLevelOnly();
    $menu_subtree = $menu_tree->load('admin', $parameters);
    $link = $menu_subtree['strava']->link;
    if ($link && $content = \Drupal::service('system.manager')
        ->getAdminBlock($link)) {
      $build['entity_management_title'] = [
        '#markup' => '<h2>' . t('Strava entity management') . '</h2>',
      ];
      $build['entity_management_items'] = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    }

    return $build;
  }

  /**
   * The Strava login callback.
   *
   * @param Url $url
   *   Optional: Url where app should redirect after access token is
   *   successfully fetched.
   *
   * @return mixed
   */
  public function stravaLoginCallback($url = NULL) {

    // Get the code _GET parameter from the current url.
    $code = $this->requestStack->getCurrentRequest()->get('code');

    // Check if a code has been found in the _GET paramater.
    if (isset($code)) {
      try {
        // Try to get an access token from the API.
        $token = $this->strava->getAccessToken($code);
        $this->strava->storeAccessToken();

        // Check if a token was set.
        if (isset($token)) {
          // Get the athlete object that is returned by Strava.
          $athlete = $token->getValues()['athlete'];
          $athlete_name = $athlete['firstname'] . ' ' . $athlete['lastname'];
          $athlete_url = 'https://www.strava.com/athletes/' . $athlete['id'];
          $message = 'Authenticated as Strava user #' . $athlete['id'] . ', ' . Link::fromTextAndUrl($athlete_name, Url::fromUri($athlete_url))
              ->toString();

          // Log the successful authorization.
          $this->loggerFactory
            ->get('strava')
            ->notice($message);

          // Also try to create an athlete entity if the submodule is enabled.
          if (\Drupal::moduleHandler()->moduleExists('strava_athletes')) {
            /** @var \Drupal\strava_athletes\AthleteUserHandler $athlete_user_handler */
            $athlete_user_handler = \Drupal::service('strava.athlete_user_handler');
            $athlete_user_handler->setStravaDetails($athlete);
            $athlete_user_handler->connect();
          }

          if (is_null($url)) {
            // Display the authorized athlete's basic info.
            return ['#markup' => '<p><a href="' . $athlete_url . '"><img src="' . $athlete['profile'] . '" alt="profile"></a></br>' . $message . '</p>'];
          }
          else {
            // Redirect to the referring url.
            return new RedirectResponse($url->toString());
          }
        }
        else {
          // Log an error.
          $this->loggerFactory
            ->get('strava')
            ->error('Couldn\'t retrieve an access token from Strava.');

          $build = ['#markup' => '<em>Couldn\'t retrieve an access token from Strava.</em>'];
        }
      }
      catch (\Exception $e) {
        // Log the returned exception message.
        $this->loggerFactory
          ->get('strava')
          ->error($e->getMessage());
      }
    }
    else {
      try {
        // Display the strava login block.
        $build['login_block'] = \Drupal::service('plugin.manager.block')
          ->createInstance('strava_login_block')
          ->build();
      }
      catch (\Exception $e) {
        // Log the returned exception message.
        $this->loggerFactory
          ->get('strava')
          ->error($e->getMessage());
      }
    }

    return $build;
  }

}
