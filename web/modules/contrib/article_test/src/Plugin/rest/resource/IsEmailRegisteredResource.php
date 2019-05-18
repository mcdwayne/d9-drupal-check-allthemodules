<?php

namespace Drupal\role_paywall_article_test\Plugin\rest\resource;

/**
 * @file
 * REST API implementation to forward AJAX requests to SSO news factory.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Session\AccountInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\role_paywall_article_test\ArticleTestManager;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides isEmailRegistered endpoint.
 *
 * @RestResource(
 *   id = "role_paywall_article_test_connector",
 *   label = @Translation("isEmailRegistered connector"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "canonical" = "/role_paywall/isemailregistered",
 *     "https://www.drupal.org/link-relations/create" = "/role_paywall/isemailregistered"
 *   }
 * )
 */
class IsEmailRegisteredResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The article test manager.
   *
   * @var \Drupal\role_paywall_article_test\ArticleTestManager
   */
  protected $articleTestManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountInterface $current_user,
    ArticleTestManager $article_test_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger);

    $this->currentUser = $current_user;
    $this->articleTestManager = $article_test_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user'),
      $container->get('role_paywall_article_test.manager')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Non implemented response.
   */
  public function get($text) {
    $response = ['message' => 'No GET method implemented'];
    return new ResourceResponse($response);
  }

  /**
   * Responds to entity POST requests.
   *
   * Handles 3 cases when an email is submitted:
   *  1- Register new user with new email and grant access to the test.
   *  2- The user already exists and have tests available.
   *  3- The user already exists and don't have tests available.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Response containing if the email have avialable article tests.
   */
  public function post(array $data) {
    $user = user_load_by_mail(XSS::filter($data['email']));
    $response = [
      'status' => 200,
      'message' => '',
    ];

    // Handles case 3.
    if ($user !== FALSE && !$this->articleTestManager->hasUserAccessToNextTest(user)) {
      $response = [
        'status' => 401,
        'message' => $this->t('No more tests available for you right now'),
      ];
    }
    // Handles case 1 and 2.
    else {
      // Create new user for case 1.
      if ($user === FALSE) {
        // Strips everything after the '@' and remove illegal characters.
        $user_name = trim(preg_replace('/[^\x{80}-\x{F7} a-zA-Z0-9@_.\'-]/', '', preg_replace('/@.*$/', '', $data['email'])));
        $new_user = User::Create([
          'name' => $user_name,
          'email' => $data['email'],
        ]);
        $new_user->activate();
        try {
          $new_user->save();
          $user = $new_user;
        }
        catch (\Exception $e) {
          $response = [
            'status' => 401,
            'message' => $this->t('An unexpected error while storing your email.'),
          ];
        }
      }
      if ($user) {
        $node = node_load($data['article_nid']);
        $this->articleTestManager->grantArticleTestAccess($user, $node);
        user_login_finalize($user);
      }
    }

    return new ResourceResponse($response);
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Non implemented response.
   */
  public function patch($arg) {
    $response = ['message' => 'No PATCH method implemented'];
    return new ResourceResponse($response);
  }

}
