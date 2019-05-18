<?php

namespace Drupal\jsonapi_aliases\StackMiddleware;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\jsonapi_aliases\KeyValueStore\Store;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Core\Url;

/**
 * Sets the 'api_json' format & the internal JSON API route
 * on all requests to JSON API-managed routes.
 *
 */
class PathAndFormatSetter implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * A custom store for looking up node bundle & uuid.
   *
   * @var \Drupal\jsonapi_aliases\KeyValueStore\Store
   */
  protected $store;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configuration;

  /**
   * @var array|mixed|null
   */
  protected $pathPrefix;

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /* Constructs a PathAndFormatSetter object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\jsonapi_aliases\KeyValueStore\Store $store
   *   The mapping key value store.
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    AliasManagerInterface $alias_manager,
    Store $store,
    \Drupal\Core\Config\ConfigFactory $configFactory
  ) {
    $this->httpKernel = $http_kernel;
    $this->aliasManager = $alias_manager;
    $this->store = $store;
    $this->configuration = $configFactory;
    $this->pathPrefix = $configFactory->get('jsonapi_aliases.settings')->get('path_prefix');
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE
  ) {
    // Make sure we're only transforming appropriate requests.
    if ($this->isJsonApiRequest($request)) {
      $request = $this->transform($request);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Checks whether the current request is a JSON API request.
   *
   * Inspects:
   * - possible conflict with default JSON API urls
   * - request path
   * - 'Accept' request header value.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   Whether the current request is a JSON API request.
   */
  protected function isJsonApiRequest(Request $request) {

    return
      // Don't touch "original" JSON-API-route requests, only handle requests on
      // configured path prefix routes and check if the 'Accept' header includes
      // the-JSON API MIME type.
      strpos($request->getPathInfo(), '/jsonapi/') === FALSE && $this->pathPrefixApplies($request) && $this->hasJsonApiAcceptHeader($request);

  }

  /**
   * Modifies the request to act as an alias to a JSON-API route request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object
   *
   * @return \Symfony\Component\HttpFoundation\Request|static
   *   The transformed request object
   */
  protected function transform(Request $request) {

    // Get path prefix from configuration.
    $path_prefix = '/' . $this->pathPrefix;

    // Remove "/api" from request alias.
    $alias = substr($request->getPathInfo(), strlen($path_prefix));

    // Set the request format, just in case.
    // $request->setRequestFormat('api_json');

    // The path alias manager resolves the alias to an internal path
    $path = $this->aliasManager->getPathByAlias($alias);

    // Handle special case of front page url
    if ($path === '/') {
      $path = $this->configuration->get('system.site')->get('page.front');
    }

    // Lookup bundle & uuid for node id in our custom key-value store.
    if ($info = $this->store->get($path)) {

      // Build the default JSON-API path
      $path = $this->buildJsonApiPath($info);

      // Replace immutable request with modified clone.
      $request = $this->duplicateRequest($request, $path_prefix, $alias, $path);
    }

    return $request;
  }

  /**
   * Check for path prefix.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object
   *
   * @return bool
   */
  protected function pathPrefixApplies($request) {
    return strpos($request->getPathInfo(), '/' . $this->pathPrefix) === 0;
  }

  /**
   * Check for JSON API accept header.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return bool
   */
  protected function hasJsonApiAcceptHeader($request) {
    return count(array_filter($request->getAcceptableContentTypes(),
      function ($accept) {
        return strpos($accept, 'application/vnd.api+json') === 0;
      }));
  }

  /**
   * Duplicate request
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The original request
   * @param string $path_prefix
   *   The configured path prefix
   * @param string $alias
   *   The resolved path alias
   * @param $path
   *   The resolved internal path
   *
   * @return \Symfony\Component\HttpFoundation\Request $request
   *   The duplicated and modified request
   */
  protected function duplicateRequest(
    Request $request,
    $path_prefix,
    $alias,
    $path
  ) {
    // Replace REQUEST_URI in request server parameters.
    $server_parameter_bag = $request->server->all();
    $server_parameter_bag['REQUEST_URI'] = str_replace($path_prefix . $alias,
      $path,
      $request->getRequestUri());

    // Clone immutable request with new server paramters.
    return $request->duplicate(null, null, null, null, null,
      $server_parameter_bag);
  }

  /**
   * Build a default JSON-API path from provided entity information
   *
   * @param array $info
   *   A list of relevant entity properties
   *
   * @return string
   *   The
   */
  protected function buildJsonApiPath($info) {
    if (isset($info['bundle']) && $info['bundle'] !== null) {
      return '/jsonapi/' . $info['type'] . '/' . $info['bundle'] . '/' . $info['uuid'];
    } else {
      return '/jsonapi/' . $info['type'] . '/' . $info['uuid'];
    }
  }

}
