<?php

namespace Drupal\yandex_oauth\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\yandex_oauth\YandexOAuthTokensInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides AuthController class.
 */
class AuthController extends ControllerBase {

  /**
   * Path where the library resides relative to Drupal root.
   *
   * This can be either an empty string indicating that the library is in root
   * vendor directory, or a path to the module.
   *
   * @var string
   */
  protected $libDir = '';

  /**
   * The session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The yandex_oauth service.
   *
   * @var \Drupal\yandex_oauth\YandexOAuthTokensInterface
   */
  protected $yandexOauth;

  /**
   * Constructs a new AuthController.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session service.
   * @param \Drupal\yandex_oauth\YandexOAuthTokensInterface $yandex_oauth
   *   The yandex_oauth service.
   */
  public function __construct(SessionInterface $session, YandexOAuthTokensInterface $yandex_oauth) {
    $this->session = $session;
    $this->yandexOauth = $yandex_oauth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('session'), $container->get('yandex_oauth'));
  }

  /**
   * Checks access for this controller methods based on module's configuration.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed if application settings are not empty.
   */
  public function access(AccountInterface $account) {
    $id = $this->config('yandex_oauth.settings')->get('id');
    $secret = $this->config('yandex_oauth.settings')->get('secret');

    return AccessResult::allowedIf(!empty($id) && !empty($secret));
  }

  /**
   * Setup tasks.
   *
   * These tasks cannot be run in class constructor, because the class is
   * constructed not only in our specific routes, but, for example, just to
   * check access to routes, etc.
   */
  protected function setup() {
    $path_to_module = drupal_get_path('module', 'yandex_oauth') . '/';
    $loader = $path_to_module . 'vendor/autoload.php';

    if (file_exists($loader)) {
      // Lib installed in module's vendor dir.
      require_once $loader;
      $this->libDir = $path_to_module;
    }
    else {
      $reflector = new \ReflectionClass('Hybrid_Auth');
      $this->libDir = str_replace([
        '\\',
        'vendor/hybridauth/hybridauth/hybridauth/Hybrid/Auth.php',
      ], ['/', ''], $reflector->getFileName());
    }

    $this->session->start();
  }

  /**
   * Authenticates current user with their Yandex account.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response object.
   */
  public function auth(Request $request) {
    $this->setup();

    $config = [
      'base_url' => Url::fromRoute('yandex_oauth.endpoint')->setAbsolute()->toString(),
      'providers' => [
        'Yandex' => [
          'enabled' => TRUE,
          'keys' => [
            'id' => $this->config('yandex_oauth.settings')->get('id'),
            'secret' => $this->config('yandex_oauth.settings')->get('secret'),
          ],
          'wrapper' => [
            'path' => $this->libDir . 'vendor/hybridauth/hybridauth/additional-providers/hybridauth-yandex/Providers/Yandex.php',
            'class' => 'Hybrid_Providers_Yandex',
          ],
          'hauth_return_to' => Url::createFromRequest($request)->setAbsolute()->toString(),
        ],
      ],
    ];

    try {
      $hybridauth = new \Hybrid_Auth($config);
      /** @var \Hybrid_Provider_Model $adapter */
      $adapter = $hybridauth->authenticate('Yandex');
      $token_info = $hybridauth->getAdapter('Yandex')->getAccessToken();

      $result = $this->yandexOauth->save(
        $adapter->getUserProfile()->displayName,
        $this->currentUser()->id(),
        $token_info['access_token'],
        $token_info['expires_at']
      );

      if ($result) {
        drupal_set_message($this->t('Authenticated successfully.'));
      }
    }
    catch (\Exception $e) {
      watchdog_exception('yandex_oauth', $e);
      drupal_set_message(Html::escape($e->getMessage()), 'error');
    }

    return $this->redirect('<front>');
  }

  /**
   * Library endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response object.
   */
  public function endpoint() {
    $this->setup();

    try {
      \Hybrid_Endpoint::process();
    }
    catch (\Exception $e) {
      watchdog_exception('yandex_oauth', $e);
      drupal_set_message(Html::escape($e->getMessage()), 'error');
    }

    return $this->redirect('<front>');
  }

}
