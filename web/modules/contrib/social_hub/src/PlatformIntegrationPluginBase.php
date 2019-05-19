<?php

namespace Drupal\social_hub;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for platform integration plugins.
 *
 * @phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar
 * @phpcs:disable Drupal.Commenting.PostStatementComment.Found
 */
abstract class PlatformIntegrationPluginBase extends PluginBase implements
    PlatformIntegrationPluginInterface,
    ContainerFactoryPluginInterface {

  use PluginWithFormsTrait;

  const LINK_TYPE_ICON = 'icon';

  const LINK_TYPE_TEXT = 'text';

  // @TODO Make icons set configurable.
  const FONTAWESOME_SEARCH_URL = 'https://fontawesome.com/icons?d=gallery&s=brands&q=';

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The render metadata.
   *
   * @var \Drupal\Core\Render\BubbleableMetadata
   */
  protected $metadata;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs PlatformPluginBase instance.
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentRouteMatch $route_match,
    AccountInterface $current_user,
    Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->token = $token;
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
      $container->get('token')
    );
  }

  /**
   * Prepare context.
   *
   * @param array $context
   *   The context array.
   *
   * @return array
   *   The context array.
   */
  protected function prepareContext(array $context = []) {
    if (!isset($context['entity']) ||
      !($context['entity'] instanceof EntityInterface)) {
      $route = $this->routeMatch->getRouteObject();

      if ($route !== NULL) {
        $parameters = $route->getOption('parameters');

        if (!empty($parameters)) {
          // Determine if the current route represents an entity.
          foreach ($parameters as $name => $options) {
            if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
              $entity = $this->routeMatch->getParameter($name);
              if ($entity instanceof ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
                $context[$entity->getEntityTypeId()] = $entity;
              }
            }
          }
        }
      }
    }

    if (!isset($context['user']) ||
      !($context['user'] instanceof AccountInterface)) {
      $context['user'] = $this->currentUser;
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $context = []) {
    $this->prepareContext($context);

    return [
      '#markup' => '<!-- The ' . static::class . '::build() is not implemented. Called from ' . $context['platform']->id() . ' platform. -->', // NOSONAR
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) ($this->configuration['label'] ?? $this->pluginDefinition['label']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->pluginId,
      'label' => $this->pluginDefinition['label'],
      'link' => [
        'type' => self::LINK_TYPE_ICON,
        self::LINK_TYPE_ICON => NULL,
        self::LINK_TYPE_TEXT => NULL,
        'title' => NULL,
        'classes' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#type' => 'details',
      '#title' => $this->getPluginDefinition()['label'],
      '#description' => $this->getPluginDefinition()['description'] ?? NULL,
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->cleanValues($form, $form_state);
    $configuration = NestedArray::getValue($form_state->getValues(), $form['#parents']);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Build link settings section form.
   *
   * @return array
   *   The form section array.
   */
  protected function buildLinkSectionForm() {
    $form = [
      'link' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Link settings'),
        '#tree' => TRUE,
      ],
    ];

    $form['link']['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link type'),
      '#description' => $this->t('Choose if you want to display a plain text link or use an icon.'), // NOSONAR
      '#options' => [
        self::LINK_TYPE_ICON => $this->t('Icon'),
        self::LINK_TYPE_TEXT => $this->t('Text'),
      ],
      '#default_value' => $this->configuration['link']['type'] ?? self::LINK_TYPE_ICON,
      '#required' => TRUE,
    ];

    $icons_url = Url::fromUri(self::FONTAWESOME_SEARCH_URL, [
      'absolute' => TRUE,
      'external' => TRUE,
    ])->toString();
    $form['link']['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('For more information on available icons visit <a href="@link" target="_blank" aria-label="Go to icon vendor website" title="Go to icon vendor website">vendor website</a>.', [// NOSONAR
        '@link' => $icons_url,
      ]), // NOSONAR
      '#default_value' => $this->configuration['link']['icon'] ?? NULL,
      '#states' => [
        'visible' => [
          'input[name*="type"]' => ['checked' => TRUE, 'value' => self::LINK_TYPE_ICON],
        ],
        'required' => [
          'input[name*="type"]' => ['checked' => TRUE, 'value' => self::LINK_TYPE_ICON],
        ],
      ],
    ];

    $form['link']['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#description' => $this->t('The text used when rendering the link. Leave it empty to rely on CSS classes to render the link.'), // NOSONAR
      '#default_value' => $this->configuration['link']['text'] ?? NULL,
      '#field_suffix' => [
        '#theme' => 'token_tree_link',
        '#text' => $this->t('Tokens'),
        '#token_types' => 'all',
        '#theme_wrappers' => ['container'],
      ],
      '#states' => [
        'visible' => [
          'input[name*="type"]' => ['checked' => TRUE, 'value' => self::LINK_TYPE_TEXT],
        ],
        'required' => [
          'input[name*="type"]' => ['checked' => TRUE, 'value' => self::LINK_TYPE_TEXT],
        ],
      ],
    ];

    $form['link']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The text used for the title attribute which is used by screen readers. This setting should set for full accessibility support.'), // NOSONAR
      '#default_value' => $this->configuration['link']['title'] ?? NULL,
      '#field_suffix' => [
        '#theme' => 'token_tree_link',
        '#text' => $this->t('Tokens'),
        '#token_types' => 'all',
        '#theme_wrappers' => ['container'],
      ],
    ];

    $form['link']['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS classes'),
      '#description' => $this->t('A list of space-separated CSS classes to apply to the link element. E.g. "class-1 class-2".'), // NOSONAR
      '#default_value' => $this->configuration['link']['classes'] ?? NULL,
    ];
    return $form;
  }

  /**
   * Build icon render array.
   *
   * @return array
   *   The icon render array.
   */
  protected function buildIcon() {
    if ($this->configuration['link']['type'] !== self::LINK_TYPE_ICON) {
      return [];
    }

    return [
      '#markup' => Markup::create("<i class=\"fab {$this->configuration['icon']}\"></i>"),
    ];
  }

  /**
   * Clean submitted values.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function cleanValues(array $form, FormStateInterface $form_state) {
    $configuration = NestedArray::getValue($form_state->getValues(), $form['#parents']);

    if ($configuration['link']['type'] === self::LINK_TYPE_TEXT) {
      unset($configuration['link'][self::LINK_TYPE_ICON]);
    }
    else {
      unset($configuration['link'][self::LINK_TYPE_TEXT]);
    }

    $values = $form_state->getValues();
    NestedArray::setValue($values, $form['#parents'], $configuration + $this->defaultConfiguration());
    $form_state->setValues($values);
  }

}
