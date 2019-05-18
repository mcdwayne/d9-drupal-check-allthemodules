<?php

namespace Drupal\api_tokens;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Serialization\Json;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for the API token plugins.
 */
abstract class ApiTokenBase extends PluginBase implements ApiTokenPluginInterface {
  use RefinableCacheableDependencyTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The API tokens logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The API token string.
   *
   * @var string
   */
  protected $token;

  /**
   * The API token parameters string.
   *
   * @var string
   */
  protected $paramString;

  /**
   * The API token parameters.
   *
   * @var array
   */
  protected $params;

  /**
   * The API token parameters hash.
   *
   * @var string
   */
  protected $hash;

  /**
   * The API token build method reflection object.
   *
   * @var \ReflectionMethod|null
   */
  protected $reflector;

  /**
   * The API token render context.
   *
   * @var string[]
   */
  protected static $context = [];

  /**
   * Constructs an ApiTokenBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Psr\Log\LoggerInterface $logger
   *   The API tokens logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, RendererInterface $renderer, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->logger = $logger;
    $this->paramString = isset($configuration['params']) ? $configuration['params'] : '';
    $this->token = "[api:$plugin_id$this->paramString/]";
    $this->params = $this->paramString ? Json::decode($this->paramString) : [];
    $this->hash = $this->params ? hash('crc32b', serialize($this->params)) : '';
    $this->reflector = method_exists($this, 'build') ? new \ReflectionMethod($this, 'build') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('logger.factory')->get('api_tokens')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function provider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function token() {
    return $this->token;
  }

  /**
   * {@inheritdoc}
   */
  public function paramString() {
    return $this->paramString;
  }

  /**
   * {@inheritdoc}
   */
  public function params() {
    return $this->params;
  }

  /**
   * {@inheritdoc}
   */
  public function hash() {
    return $this->hash;
  }

  /**
   * {@inheritdoc}
   */
  public function reflector() {
    return $this->reflector;
  }

  /**
   * {@inheritdoc}
   */
  public function validateToken() {
    if (!$this->reflector) {
      $this->logger->warning($this->t('ApiToken plugin "@label" has no "build" method.', [
        '@label' => $this->label(),
      ]));
      return FALSE;
    }

    if (!is_array($this->params)) {
      $this->logger->warning($this->t('API token "@token" has invalid parameters format.', [
        '@token' => $this->token,
      ]));
      return FALSE;
    }

    if ($this->reflector->getNumberOfRequiredParameters() > count($this->params)) {
      $this->logger->warning($this->t('API token "@token" has not enough parameters.', [
        '@token' => $this->token,
      ]));
      return FALSE;
    }

    $params = [];
    foreach ($this->reflector->getParameters() as $index => $param) {
      $use_default = $param->isOptional() && !isset($this->params[$index]);
      $params[$param->getName()] = $use_default ? $param->getDefaultValue() : $this->params[$index];
    }
    $provided_count = count($this->params);
    $defined_count = count($params);
    if ($provided_count > $defined_count) {
      for ($index = $defined_count; $index < $provided_count; ++$index) {
        $params[$index] = $this->params[$index];
      }
    }
    if (!$this->validate($params)) {
      $this->logger->warning($this->t('API token "@token" has invalid parameters.', [
        '@token' => $this->token,
      ]));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates the API token parameters.
   *
   * This validation must be context-independent. For example, if some parameter
   * is expected to be an entity ID, check only whether it is a valid entity ID,
   * but don't check the entity existence/access (these must be checked in the
   * build method).
   *
   * @param array $params
   *   An array of named API token parameters. If an API token has more
   *   parameters then it is defined in build method, extra parameters will be
   *   named by parameter index. For example, if we have the API token
   *   [api:example[123, ["option1", "option2"], "extra1", "extra2"]/],
   *   and plugin's build method argument definition is: ...($id, $options),
   *   the $params will be:
   *   @code
   *   [
   *     'id' => 123,
   *     'options' => ['option1', 'option2'],
   *     '2' => 'extra1',
   *     '3' => 'extra2',
   *   ];
   *   @endcode
   *
   * @return bool
   *
   * @see \Drupal\api_tokens\ApiTokenPluginInterface::validateToken();
   */
  public function validate(array $params) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    static $recursion = FALSE;
    if ($recursion) {
      return [];
    }
    $key = "$this->pluginId:$this->hash";
    if (in_array($key, self::$context)) {
      $recursion = TRUE;
      $this->logger->warning($this->t('Recursion detected while rendering @token API token.', [
        '@token' => $this->token,
      ]));
      return [];
    }
    array_push(self::$context, $key);
    $build = call_user_func_array([$this, 'build'], $this->params);
    $this->moduleHandler->alter('api_token_build', $build, $this);
    $this->renderer->renderPlain($build);
    array_pop(self::$context);
    if ($recursion) {
      self::$context || $recursion = FALSE;
      return [];
    }
    $this->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
    $build = [
      '#markup' => $build['#markup'],
      '#attached' => $build['#attached'],
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $this->getCacheTags(),
        'max-age' => $this->getCacheMaxAge(),
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function fallback() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder() {
    $placeholder = [
      '#lazy_builder' => [
        self::class . '::lazyBuilder',
        [$this->pluginId, $this->paramString, $this->validateToken()],
      ],
      '#cache' => [
        'keys' => !self::$context ? ['api_token', $this->pluginId, $this->hash] : NULL,
      ],
    ];

    return $placeholder;
  }

  /**
   * {@inheritdoc}
   */
  public static function lazyBuilder($id, $params, $valid) {
    $plugin = \Drupal::service('plugin.manager.api_token')->createInstance($id, [
      'params' => $params,
    ]);

    return $valid ? $plugin->process() : $plugin->fallback();
  }

}
