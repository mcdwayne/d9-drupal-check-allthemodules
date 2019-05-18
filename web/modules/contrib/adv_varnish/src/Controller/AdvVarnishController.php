<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Controller\AdvVarnishController.
 */

namespace Drupal\adv_varnish\Controller;
use Drupal\adv_varnish\Response\ESIResponse;
use Drupal\adv_varnish\VarnishConfiguratorInterface;
use Drupal\adv_varnish\VarnishInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Main Varnish controller.
 *
 * Middleware stack controller to serve Cacheable response.
 */
class AdvVarnishController {

  /**
   * @var VarnishInterface
   */
  public $varnishHandler;

  /**
   * @var string
   *   Unique id for current response.
   */
  protected $uniqueId;

  /**
   * @var bool
   */
  protected $needsReload;

  /**
   * @var CacheableResponseInterface
   */
  protected $response;

  /**
   * @var Request
   */
  protected $request;

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var VarnishConfiguratorInterface
   */
  protected $configuration;

  /**
   * @var UserInterface
   */
  protected $account;

  /**
   * Class constructor.
   *
   * @param VarnishInterface $varnishHandler
   *   Varnish handler object.
   *
   */
  public function __construct(VarnishInterface $varnishHandler, VarnishConfiguratorInterface $configuration, RequestStack $request, ModuleHandlerInterface $module_handler, AccountProxyInterface $account) {
    $this->varnishHandler = $varnishHandler;
    $this->account = $account;
    $this->configuration = $configuration;
    $this->uniqueId = $this->uniqueId();
    $this->request = $request->getCurrentRequest();
    $this->moduleHandler = $module_handler;
  }

  /**
   * Response event handler.
   *
   * @param FilterResponseEvent $event
   *
   * Process CacheableResponse.
   */
  public function handleResponseEvent(FilterResponseEvent $event) {

    $this->response = $event->getResponse();

    if (!($this->response instanceof CacheableResponseInterface)) {
      return;
    }

    // Check if we on MasterRequest, also we never should cache POST requests.
    if (!$event->isMasterRequest() || !empty($_POST)) {
      $account = \Drupal::currentUser();
      $clear_on_post = $this->configuration->get('userblocks.clear_on_post');

      // If page is has a POST we need to flush user_block related cache.
      if (!empty($_POST) && $account->isAuthenticated() && $clear_on_post) {
        $this->purgeUserBlocks();
      }

      return;
    }

    // Checking Varnish settings and define if we should work further.
    if (!$this->cachingEnabled()) {
      return;
    }

    $this->cookieUpdate();

    // Reload page with updated cookies if needed.
    $needs_update = $this->needsReload ?: FALSE;
    if ($needs_update) {
      $this->reload();
    }

    // Get affected entities.
    $params = \Drupal::routeMatch()->getParameters()->all();
    $entities = array_filter($params, function($param) {
      return ($param instanceof EntityInterface);
    });

    if ($this->response instanceof ESIResponse) {
      if ($entity = $this->response->getEntity()) {
        $entities[] = $entity;
      }
    }

    // Get entity specific settings
    $cache_settings = $this->getCacheSettings($entities);

    // Allow other modules to interfere.
    $this->moduleHandler->alter('adv_varnish_page_ttl', $cache_settings);
    $cache_settings['cache_control'] = $this->configuration->get('cache_control');
    $this->setResponseHeaders($cache_settings);
  }

  /**
   * Set varnish specific response headers.
   */
  protected function setResponseHeaders($cache_settings) {

    $debug_mode = $this->configuration->get('general.debug');

    if ($debug_mode) {
      $this->response->headers->set(ADV_VARNISH_HEADER_CACHE_DEBUG, '1');
    }

    $this->response->headers->set(ADV_VARNISH_HEADER_GRACE, $cache_settings['grace']);
    $this->response->headers->set(ADV_VARNISH_HEADER_RNDPAGE, $this->uniqueId());
    $this->response->headers->set(ADV_VARNISH_HEADER_CACHE_TAG, implode(';', $cache_settings['tags']) . ';');
    $this->response->headers->set(ADV_VARNISH_X_TTL, $cache_settings['ttl']);

    $cache_control = $this->account->isAnonymous()
      ? $cache_settings['cache_control']['anonymous']
      : $cache_settings['cache_control']['logged'];

    $cache_control_values = explode(',', $cache_control);

    foreach ($cache_control_values as $value) {
      $value = explode('=', $value);
      $key = array_shift($value);
      $val = array_shift($value);
      $val = $val ?: TRUE;
      $this->response->headers->addCacheControlDirective($key, $val);
    }

    // Set this response to public as it cacheable so no private directive
    // should be present, also we need set a no-store header to prevent browser
    // from caching ESI blocks.
    $this->response->headers->addCacheControlDirective('no-store');
    $this->response->setPublic();

    // Set Etag to allow varnish deflate process.
    $this->response->setEtag(time());

  }

  /**
   * Reload page with updated cookies.
   */
  protected function reload() {

    // Setting cookie will prevent varnish from caching this.
    setcookie('time', time(), NULL, '/');

    $path = \Drupal::service('path.current')->getPath();
    $response = new RedirectResponse($path);
    $response->send();
    return;
  }


  /**
   * Generated unique id based on time.
   *
   * @return string
   *   Unique id.
   */
  protected function uniqueId() {
    $id = uniqid(time(), TRUE);
    return substr(md5($id), 5, 10);
  }

  /**
   * Updates cookie if required.
   */
  protected function cookieUpdate() {
    // Cookies may be disabled for resource files,
    // so no need to redirect in such a case.
    if ($this->redirectForbidden()) {
      return;
    }

    $account = \Drupal::currentUser();

    // If user should bypass varnish we must set per user bin.
    if ($account->hasPermission('bypass advanced varnish cache')) {
      $bin = 'u' . $account->id();
    }
    elseif ($account->id() > 0) {
      $roles = $account->getRoles();
      sort($roles);
      $bin = implode('__', $roles);
    }
    else {
      // Bin for anonym user.
      $bin = '0';
    }
    $cookie_inf = $bin;

    $noise = $this->configuration->get('general.noise') ?: '';

    // Allow other modules to interfere.
    \Drupal::moduleHandler()->alter('adv_varnish_user_cache_bin', $cookie_inf, $account);

    // Hash bin (PER_ROLE-PER_PAGE).
    $cookie_bin = hash('sha256', $cookie_inf . $noise) . '-' . hash('sha256', $noise);

    // Update cookies if did not match.
    if (empty($_COOKIE[ADV_VARNISH_COOKIE_BIN]) || ($_COOKIE[ADV_VARNISH_COOKIE_BIN] != $cookie_bin)) {

      // Update cookies.
      $params = session_get_cookie_params();
      $expire = $params['lifetime'] ? (REQUEST_TIME + $params['lifetime']) : 0;
      setcookie(ADV_VARNISH_COOKIE_BIN, $cookie_bin, $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);
      setcookie(ADV_VARNISH_COOKIE_INF, $cookie_inf, $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);

      // Mark this page as required reload as ESI request
      // from this page will be sent with old cookie info.
      $this->needsReload = TRUE;
    }
    elseif (!empty($_GET['reload'])) {
      // Front asks us to do reload.
      $this->needsReload = TRUE;
    }
  }

  /**
   * Check if redirect enabled.
   *
   * Check if this page is allowed to redirect,
   * be default resource files should not be redirected.
   */
  public function redirectForbidden($path = '') {

    if (!empty($_SESSION['adv_varnish__redirect_forbidden'])) {
      return TRUE;
    }
    elseif ($this->configuration->get('redirect_forbidden')) {
      return TRUE;
    }
    elseif ($this->configuration->get('redirect_forbidden_no_cookie') && empty($_COOKIE)) {
      // This one is important as search engines don't have cookie support
      // and we don't want them to enter infinite loop.
      // Also images may have their cookies be stripped at Varnish level.
      return TRUE;
    }

    // Get current path as default.
    $current_path = \Drupal::service('path.current')->getPath();

    // By default ecxlude resource path.
    $path_to_exclude = [
      PublicStream::basePath(),
      PrivateStream::basePath(),
      file_directory_temp(),
    ];
    $path_to_exclude = array_filter($path_to_exclude, 'trim');

    // Allow other modules to interfere.
    \Drupal::moduleHandler()->alter('adv_varnish_redirect_forbidden', $path_to_exclude, $path);

    // Check against excluded path.
    $forbidden = FALSE;
    foreach ($path_to_exclude as $exclude) {
      if (strpos($current_path, $exclude) === 0) {
        $forbidden = TRUE;
      }
    }

    return $forbidden;
  }

  /**
   * Specific entity cache settings getter.
   */
  public function getCacheSettings($entities) {
    $grace = $this->configuration->get('general.grace');
    $cache_settings['grace'] = $grace;

    $cacheable = $this->response->getCacheableMetadata();
    $cache_settings['tags'] = $cacheable->getCacheTags();

    $cache_settings['ttl'] = '';

    foreach ($entities as $entity) {

      if ($entity instanceof ConfigEntityInterface) {
        $entity_settings = $entity->get('settings');
        //$cache_settings['ttl'] = (!empty($entity_settings['cache']['max_age']))
        //  ? $entity_settings['cache']['max_age']
        //  : '';
      }

      $cache_key_generator = $this->getCacheKeyGenerator($entity);
      $key = $cache_key_generator->generateSettingsKey();
      $cache_settings['ttl'] = !is_numeric($cache_settings['ttl'])
        ? $this->configuration->get($key)['cache_settings']['ttl']
        : $cache_settings['ttl'];
      if ($this->configuration->get($key)['cache_settings']['purge_id']) {
        $cache_settings['tags'][] = $this->configuration->get($key)['cache_settings']['purge_id'];
      }
    }

    if (!is_numeric($cache_settings['ttl'])) {
      $cache_settings['ttl'] = $cacheable->getCacheMaxAge();
    }

    // If no ttl set check for custom rules settings.
    if (empty($cache_settings['ttl'])) {

      // Get current path as default.
      $current_path = \Drupal::service('path.current')->getPath();
      $rules = explode(PHP_EOL, trim($this->configuration->get('custom.rules')));
      foreach ($rules as $line) {
        $conf = explode('|', trim($line));
        if (count($conf) == 3) {

          // Check for match.
          $path_matcher = \Drupal::service('path.matcher');
          $match = $path_matcher->matchPath($current_path, $conf[0]);
          if ($match) {
            $cache_settings['ttl'] = $conf[1];
            $cache_settings['tags'][] = $conf[2];
          }
        }
      }
    }

    // Use general TTL as fallback option.
    $cache_settings['ttl'] = is_numeric($cache_settings['ttl'])
      ? $cache_settings['ttl']
      : $this->configuration->get('general.page_cache_maximum_age');

    return $cache_settings;
  }

  /**
   * Get varnish cache settings key generator instance.
   *
   * @param $entity
   *   EntityInterface
   * @param $options
   *   (array) options array
   *
   * @return \Drupal\adv_varnish\VarnishCacheableEntityInterface
   */
  public function getCacheKeyGenerator(EntityInterface $entity, array $options = []) {
    $plugins = \Drupal::service('plugin.manager.varnish_cacheable_entity')->getDefinitions();
    $type = $entity->getEntityTypeId();
    if (!in_array($type, array_keys($plugins))) {
      $type = 'default';
    }
    return \Drupal::service('plugin.manager.varnish_cacheable_entity')->createInstance($type, ['entity' => $entity, 'options' => $options]);
  }

  /**
   * Define if caching enabled for this page and we can proceed with this request.
   *
   * @return bool.
   *   Result of varnish enable state.
   */
  public static function cachingEnabled() {
    $enabled = TRUE;
    $config = \Drupal::config('adv_varnish.settings');

    // Check if user is authenticated and we can use cache for such users.
    $account = \Drupal::currentUser();
    $authenticated = $account->isAuthenticated();
    $cache_authenticated = $config->get('available.authenticated_users');
    if ($authenticated && !$cache_authenticated) {
      $enabled = FALSE;
    }

    // Check if user has permission to bypass varnish.
    if ($account->hasPermission('bypass advanced varnish cache')) {
      $enabled = FALSE;
    }

    // Check if we in admin theme and if we allow to cache this page.
    $admin_theme_name = \Drupal::config('system.theme')->get('admin');
    $current_theme = \Drupal::theme()->getActiveTheme()->getName();
    $cache_admin_theme = $config->get('available.admin_theme');
    if ($admin_theme_name == $current_theme && !$cache_admin_theme) {
      $enabled = FALSE;
    }

    // Check if we on https and if we can to cache page.
    $https_cache_enabled = $config->get('available.https');
    $https = \Drupal::request()->isSecure();
    if ($https && !$https_cache_enabled) {
      $enabled = FALSE;
    }

    // Check if we acn be on disabled domain.
    $config = explode(PHP_EOL, $config->get('available.exclude'));
    foreach ($config as $line) {
      $rule = explode('|', trim($line));
      if (($rule[0] == '*') || ($_SERVER['SERVER_NAME'] == $rule[0])) {
        if (($rule[1] == '*') || strpos($_SERVER['REQUEST_URI'], $rule[1]) === 0) {
          $enabled = FALSE;
          break;
        }
      }
    }

    return $enabled;
  }

  /**
   * Purge user_blocks.
   */
  public function purgeUserBlocks($tag_names = array(ADV_VARNISH_TAG_USER_BLOCKS), $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
      $tag_names[] = 'user:' . $account->id();
    }
    $this->varnishHandler->purgeTags($tag_names);
  }

}
