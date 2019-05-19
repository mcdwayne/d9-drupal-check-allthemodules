<?php

namespace Drupal\social_hub\Plugin\SocialHub\PlatformIntegration;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\social_hub\PlatformIntegrationPluginBase;
use Drupal\social_hub\PlatformInterface;
use Drupal\social_hub\Utils\ChainedLibrariesResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the social_platform.
 *
 * @PlatformIntegration(
 *   id = "share",
 *   label = @Translation("Share"),
 *   description = @Translation("Allow platforms to be used to share content.")
 * )
 *
 * @internal
 *   Plugin classes are internal.
 *
 * @phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar
 * @phpcs:disable Drupal.Commenting.PostStatementComment.Found
 */
class Share extends PlatformIntegrationPluginBase {

  const SHARING_MODE_URL = 'url';

  const SHARING_MODE_EMBED = 'embed';

  const SCRIPT_TYPE_NONE = '_none';

  const SCRIPT_TYPE_INLINE = 'inline';

  const SCRIPT_TYPE_LIBRARY = 'library';

  const SCRIPT_TYPE_EXTERNAL = 'external';

  /**
   * The chain-resolver for libraries.
   *
   * @var \Drupal\social_hub\Utils\ChainedLibrariesResolverInterface
   */
  private $librariesResolver;

  /**
   * Debug mode flag.
   *
   * @var bool
   */
  private $debug;

  /**
   * Constructs Share instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current matched route.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\social_hub\Utils\ChainedLibrariesResolverInterface $libraries_resolver
   *   The chain-resolver for libraries.
   * @param bool $debug
   *   Debug mode flag.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentRouteMatch $route_match,
    AccountInterface $current_user,
    Token $token,
    ChainedLibrariesResolverInterface $libraries_resolver,
    bool $debug = FALSE) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match, $current_user, $token);
    $this->librariesResolver = $libraries_resolver;
    $this->debug = $debug;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('token'),
      $container->get('social_hub.chained_libraries_resolver'),
      $container->getParameter('twig.config')['debug'] ?? FALSE
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'sharing_mode' => self::SHARING_MODE_URL,
      self::SHARING_MODE_URL => NULL,
      self::SHARING_MODE_EMBED => NULL,
      'script_type' => self::SCRIPT_TYPE_NONE,
      self::SCRIPT_TYPE_INLINE => NULL,
      self::SCRIPT_TYPE_LIBRARY => NULL,
      self::SCRIPT_TYPE_EXTERNAL => [
        'url' => NULL,
        'attributes' => [
          'async' => TRUE,
          'minified' => FALSE,
        ],
        'preprocess' => FALSE,
        'browsers' => NULL,
      ],
    ];

    return $defaults + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['sharing_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sharing mode'),
      '#description' => $this->t('Choose how the share is going to happen.'), // NOSONAR
      '#options' => [
        self::SHARING_MODE_URL => $this->t('URL'),
        self::SHARING_MODE_EMBED => $this->t('Embed'),
      ],
      '#default_value' => $this->configuration['sharing_mode'] ?? self::SHARING_MODE_URL,
      '#required' => TRUE,
    ];

    $form[self::SHARING_MODE_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Typically an URL from which share to or from.'), // NOSONAR
      '#default_value' => $this->configuration[self::SHARING_MODE_URL] ?? NULL,
      '#field_suffix' => [
        '#theme' => 'token_tree_link',
        '#text' => $this->t('Tokens'),
        '#token_types' => 'all',
        '#theme_wrappers' => ['container'],
      ],
      '#states' => [
        'visible' => [
          'input[name*="sharing_mode"]' => ['checked' => TRUE, 'value' => self::SHARING_MODE_URL],
        ],
        'required' => [
          'input[name*="sharing_mode"]' => ['checked' => TRUE, 'value' => self::SHARING_MODE_URL],
        ],
      ],
    ];

    $form[self::SHARING_MODE_EMBED] = [
      '#type' => 'textarea',
      '#title' => $this->t('Embed'),
      '#description' => $this->t('Typically an iframe code to embed in other web pages. In order to this work properly you must select `Library` for script type and later pick up the `social_hub/share_embed` library.'), // NOSONAR
      '#default_value' => $this->configuration[self::SHARING_MODE_EMBED] ?? NULL,
      '#field_suffix' => [
        '#theme' => 'token_tree_link',
        '#text' => $this->t('Tokens'),
        '#token_types' => 'all',
        '#theme_wrappers' => ['container'],
      ],
      '#states' => [
        'visible' => [
          'input[name*="sharing_mode"]' => ['checked' => TRUE, 'value' => self::SHARING_MODE_EMBED],
        ],
        'required' => [
          'input[name*="sharing_mode"]' => ['checked' => TRUE, 'value' => self::SHARING_MODE_EMBED],
        ],
      ],
    ];

    $form['script_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Script type'),
      '#description' => $this->t('If this integration requires an JS script select the proper method to attach that script. Options marked with * are not implemented yet.'), // NOSONAR
      '#options' => [
        self::SCRIPT_TYPE_NONE => $this->t('None'),
        self::SCRIPT_TYPE_INLINE => $this->t('Inline'),
        self::SCRIPT_TYPE_LIBRARY => $this->t('Library*'),
        self::SCRIPT_TYPE_EXTERNAL => $this->t('External*'),
      ],
      '#default_value' => $this->configuration['script_type'] ?? self::SCRIPT_TYPE_NONE,
    ];

    $form[self::SCRIPT_TYPE_INLINE] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inline script'),
      '#description' => $this->t('Enter here the script code without the script tag (it will be added by Drupal). Keep in mind that inline scripts cannot depend on libraries since we cannot assure they will be loaded when the script is being parsed by the browser.'), // NOSONAR
      '#default_value' => $this->configuration[self::SCRIPT_TYPE_INLINE] ?? NULL,
      '#field_suffix' => [
        '#theme' => 'token_tree_link',
        '#text' => $this->t('Tokens'),
        '#token_types' => 'all',
      ],
      '#states' => [
        'visible' => [
          'select[name*="script_type"]' => ['value' => self::SCRIPT_TYPE_INLINE],
        ],
        'required' => [
          'select[name*="script_type"]' => ['value' => self::SCRIPT_TYPE_INLINE],
        ],
      ],
    ];

    $form[self::SCRIPT_TYPE_LIBRARY] = [
      '#type' => 'select',
      '#title' => $this->t('Installed libraries'),
      '#description' => $this->t('Select one the libraries defined in *.libraries.yml of installed modules.'), // NOSONAR
      '#default_value' => $this->configuration[self::SCRIPT_TYPE_LIBRARY] ?? '',
      '#options' => $this->getInstalledLibraries(),
      '#empty_value' => '',
      '#states' => [
        'visible' => [
          'select[name*="script_type"]' => ['value' => self::SCRIPT_TYPE_LIBRARY],
        ],
        'required' => [
          'select[name*="script_type"]' => ['value' => self::SCRIPT_TYPE_LIBRARY],
        ],
      ],
    ];

    $form += $this->buildExternalSectionForm();
    $form += $this->buildLinkSectionForm();

    return $form;
  }

  /**
   * Get the installed libraries.
   *
   * @return array
   *   An array of libraries keyed by library id.
   */
  private function getInstalledLibraries() {
    $options = [];

    foreach ($this->librariesResolver->resolve() as $extension) {
      $options[$extension['name']] = array_combine(array_keys($extension['libraries']), array_keys($extension['libraries']));
    }

    return $options;
  }

  /**
   * Build 'external' form section.
   *
   * @return array
   *   The form section render array.
   */
  private function buildExternalSectionForm() {
    $form = [
      self::SCRIPT_TYPE_EXTERNAL => [
        '#type' => 'fieldset',
        '#title' => $this->t('External'),
        '#states' => [
          'visible' => [
            'select[name*="script_type"]' => ['value' => self::SCRIPT_TYPE_EXTERNAL],
          ],
        ],
        '#tree' => TRUE,
      ],
    ];

    $form[self::SCRIPT_TYPE_EXTERNAL]['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('The script external URL.'),
      '#default_value' => $this->configuration[self::SCRIPT_TYPE_EXTERNAL]['url'] ?? NULL,
      '#states' => [
        'required' => [
          'select[name*="script_type"]' => ['value' => self::SCRIPT_TYPE_EXTERNAL],
        ],
      ],
    ];

    $form[self::SCRIPT_TYPE_EXTERNAL]['attributes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Attributes'),
      '#tree' => TRUE,
    ];

    $form[self::SCRIPT_TYPE_EXTERNAL]['attributes']['async'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load asynchronously'),
      '#description' => $this->t("Check 'Yes' if you want the script to be loaded after all other script are loaded."),
      '#options' => [
        $this->t('No'),
        $this->t('Yes'),
      ],
      '#default_value' => (bool) $this->configuration[self::SCRIPT_TYPE_EXTERNAL]['attributes']['async'],
    ];

    $form[self::SCRIPT_TYPE_EXTERNAL]['attributes']['minified'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Minified'),
      '#description' => $this->t("Check 'Yes' if the script is already minified by the external source."),
      '#options' => [
        $this->t('No'),
        $this->t('Yes'),
      ],
      '#default_value' => (bool) $this->configuration[self::SCRIPT_TYPE_EXTERNAL]['attributes']['minified'],
    ];

    $form[self::SCRIPT_TYPE_EXTERNAL]['preprocess'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preprocess'),
      '#description' => $this->t("Check 'Yes' you want Drupal to preprocess this script before embed it on the page."),
      '#options' => [
        $this->t('No'),
        $this->t('Yes'),
      ],
      '#default_value' => (bool) $this->configuration[self::SCRIPT_TYPE_EXTERNAL]['preprocess'],
    ];

    $form[self::SCRIPT_TYPE_EXTERNAL]['browsers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Supported browsers'),
      '#description' => $this->t('Type key/values text separated by colons and commas. E.g.: IE:lte IE 9,!IE:false'),
      '#default_value' => $this->configuration[self::SCRIPT_TYPE_EXTERNAL]['browsers'] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Force libraries cache to be rebuild
    Cache::invalidateTags(['library_info']);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanValues(array $form, FormStateInterface $form_state) {
    parent::cleanValues($form, $form_state);
    $configuration = NestedArray::getValue($form_state->getValues(), $form['#parents']);

    if ($configuration['sharing_mode'] === self::SHARING_MODE_URL) {
      unset($configuration[self::SHARING_MODE_EMBED]);
    }
    else {
      unset($configuration[self::SHARING_MODE_URL]);
    }

    if ($configuration['sharing_mode'] === self::SHARING_MODE_URL) {
      unset($configuration[self::SHARING_MODE_EMBED]);
    }
    else {
      unset($configuration[self::SHARING_MODE_URL]);
    }

    switch ($configuration['script_type']) {
      case self::SCRIPT_TYPE_INLINE:
        unset($configuration[self::SCRIPT_TYPE_EXTERNAL], $configuration[self::SCRIPT_TYPE_LIBRARY]);
        break;

      case self::SCRIPT_TYPE_LIBRARY:
        unset($configuration[self::SCRIPT_TYPE_EXTERNAL], $configuration[self::SCRIPT_TYPE_INLINE]);
        break;

      case self::SCRIPT_TYPE_EXTERNAL:
        unset($configuration[self::SCRIPT_TYPE_INLINE], $configuration[self::SCRIPT_TYPE_LIBRARY]);
        break;

      default:
        unset(
          $configuration[self::SCRIPT_TYPE_INLINE],
          $configuration[self::SCRIPT_TYPE_EXTERNAL],
          $configuration[self::SCRIPT_TYPE_LIBRARY]
        );
    }

    $values = $form_state->getValues();
    NestedArray::setValue($values, $form['#parents'], $configuration + $this->defaultConfiguration());
    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $context = []) {
    $context = $this->prepareContext($context);
    /** @var \Drupal\social_hub\PlatformInterface $platform */
    $platform = $context['platform'] ?? NULL;
    $this->metadata = new BubbleableMetadata();
    $url = $this->getUrl($context);
    $url_object = $url->toString(TRUE);
    $url_string = $url_object->getGeneratedUrl();
    $this->metadata->addCacheableDependency($url);

    $build = [
      '#theme' => $this->getPluginId(),
      '#url' => $url_string,
      '#link_type' => $this->configuration['link']['type'],
      '#sharing_mode' => self::SHARING_MODE_EMBED,
      '#attributes' => [
        'id' => Html::getUniqueId($platform->id() . '_' . $this->getPluginId()),
        'class' => [
          Html::getClass($platform->id()) . '_' . Html::getClass($this->getPluginId()),
          Html::getClass($platform->id()) . '_' . Html::getClass($this->getPluginId() . '__' . $this->configuration['sharing_mode']),
        ],
        // Store the URL also in an attribute to make it cross-theme function.
        'data-social-hub' => \GuzzleHttp\json_encode([
          'platform' => $platform->id(),
          'plugin' => $this->getPluginId(),
          'sharingMode' => $this->configuration['sharing_mode'],
          'url' => $url_string,
        ], JSON_FORCE_OBJECT),
      ],
    ];

    if ($this->configuration['sharing_mode'] === self::SHARING_MODE_EMBED) {
      $build += [
        '#extras' => [
          'embed_value' => $this->configuration[self::SHARING_MODE_EMBED],
          'embed_attributes' => [
            'class' => [
              'element-invisible',
              Html::getClass($platform->id()) . '_' . Html::getClass($this->getPluginId()),
              Html::getClass($platform->id()) . '_' . Html::getClass($this->getPluginId() . '__' . $this->configuration['sharing_mode']),
              Html::getClass($platform->id()) . '_' . Html::getClass($this->getPluginId()),
              Html::getClass($platform->id()) . '_' . Html::getClass($this->getPluginId() . '__' . $this->configuration['sharing_mode']) . '__code', // NOSONAR
            ],
            'data-referenced-by' => $build['#attributes']['id'],
          ],
        ],
      ];
    }

    if ($this->configuration['sharing_mode'] === self::SHARING_MODE_URL) {
      $build['#attributes']['target'] = '_blank';
    }

    if ($this->configuration['link']['type'] === self::LINK_TYPE_TEXT &&
      !empty($this->configuration['link']['text'])) {
      $build['#text'] = $this->token->replace($this->configuration['link']['text'], $context, [], $this->metadata);
    }

    if ($this->configuration['link']['type'] === self::LINK_TYPE_ICON) {
      $build['#icon'] = $this->configuration['link']['icon'];
      $this->metadata->addAttachments(['library' => ['social_hub/icons']]);
    }

    if (!empty($this->configuration['link']['title'])) {
      $build['#attributes']['title'] = $this->token->replace($this->configuration['link']['title'], $context, [], $this->metadata);
    }

    if (!empty(trim($this->configuration['link']['classes']))) {
      $classes = explode(' ', trim($this->configuration['link']['classes']));
      $build['#attributes']['class'] = array_merge($build['#attributes']['class'], $classes);
    }

    switch ($this->configuration['script_type']) {
      case self::SCRIPT_TYPE_INLINE:
        $build += [
          '#script' => $this->token->replace($this->configuration[self::SCRIPT_TYPE_INLINE], $context, [], $this->metadata),
        ];
        break;

      case self::SCRIPT_TYPE_LIBRARY:
        $this->metadata->addAttachments([
          'library' => [$this->configuration[self::SCRIPT_TYPE_LIBRARY]],
          'drupalSettings' => [
            'socialHub' => [
              'instances' => ["#{$build['#attributes']['id']}"],
              'debug' => $this->debug ?? FALSE,
            ],
          ],
        ]);
        break;

      case self::SCRIPT_TYPE_EXTERNAL:
        if ($platform instanceof PlatformInterface) {
          $this->metadata->addAttachments([
            'library' => ["social_hub/{$platform->id()}_share"],
          ]);
        }
        break;

      default:
    }

    $this->metadata->applyTo($build);

    return $build;
  }

  /**
   * Get valid sharing URL.
   *
   * @param array $context
   *   An array of context data.
   *
   * @return \Drupal\Core\Url
   *   The sharing URL.
   */
  private function getUrl(array $context) {
    if ($this->configuration['sharing_mode'] === self::SHARING_MODE_EMBED) {
      return Url::fromRoute('<nolink>');
    }

    $url = $this->token->replace($this->configuration[self::SHARING_MODE_URL], $context, [], $this->metadata);
    $options = [
      'absolute' => TRUE,
      'external' => TRUE,
      'query' => [],
      'fragment' => parse_url($url, PHP_URL_FRAGMENT),
    ];
    parse_str(parse_url($url, PHP_URL_QUERY), $options['query']);
    $scheme = parse_url($url, PHP_URL_SCHEME) . '://';
    $host = parse_url($url, PHP_URL_HOST);
    $path = parse_url($url, PHP_URL_PATH);
    $uri = $scheme . $host . $path;

    return Url::fromUri($uri, $options);
  }

}
