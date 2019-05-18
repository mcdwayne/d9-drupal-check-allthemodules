<?php

namespace Drupal\context_active_trail\Plugin\ContextReaction;

use Drupal\context\ContextInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a reaction that sets the active trail.
 *
 * @ContextReaction(
 *   id = "active_trail",
 *   label = @Translation("Active trail")
 * )
 */
class ActiveTrail extends ContextReactionPluginBase implements ContainerFactoryPluginInterface {
  /**
   * The menu link selector.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuLinkSelector;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_link_selector
   *   The menu link selector.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuParentFormSelectorInterface $menu_link_selector) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkSelector = $menu_link_selector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.parent_form_selector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Lets you set the active trail');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, ContextInterface $context = NULL) {
    $trail = $this->configuration['trail'];
    $form['trail'] = $this->menuLinkSelector->parentSelectElement($trail);
    $form['breadcrumbs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override breadcrumbs'),
      '#description' => $this->t('Override breadcrumbs based on the modified active trail.'),
      '#default_value' => $this->configuration['breadcrumbs'],
    ];
    $form['breadcrumb_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show current page title at end'),
      '#description' => $this->t('If your site has a module dedicated to handling breadcrumbs already, ensure this setting is disabled.'),
      '#default_value' => $this->configuration['breadcrumb_title'],
      '#states' => [
        'visible' => [
          ':input[name="reactions[active_trail][breadcrumbs]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::getConfiguration() + [
      'trail' => 'main:',
      'breadcrumbs' => TRUE,
      'breadcrumb_title' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    Cache::invalidateTags(['context_active_trail']);
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Get the link plugin ID.
   *
   * @return string
   *   The link ID.
   */
  public function getLinkId() {
    if (!isset($this->configuration['trail'])) {
      return NULL;
    }

    list(, $link_id) = explode(':', $this->configuration['trail'], 2);
    return $link_id;
  }

  /**
   * Whether or not to set the breadcrumbs.
   */
  public function setsBreadcrumbs() {
    return !empty($this->configuration['breadcrumbs']);
  }

}
