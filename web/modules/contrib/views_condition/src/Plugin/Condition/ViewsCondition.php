<?php

namespace Drupal\views_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Condition\ConditionInterface;

/**
 * Provides a 'Views' condition to enable on a views page.
 *
 * @Condition(
 *   id = "views_condition",
 *   label = @Translation("Views"),
 *   module = "views_condition"
 * )
 */
class ViewsCondition extends ConditionPluginBase implements ConditionInterface, ContainerFactoryPluginInterface {

  /**
   * Used to check if we're on a views page.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $routeMatch;

  /**
   * Entity storage to load all view entities.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  private $entityStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('view'),
      $container->get('current_route_match'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a new ViewsCondition object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   Entity storage object to get all view entities.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   Route match object.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityStorageInterface $entity_storage, CurrentRouteMatch $route_match, array $configuration, $plugin_id, $plugin_definition) {
    $this->routeMatch = $route_match;
    $this->entityStorage = $entity_storage;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Legacy support to change the configuration values.
    if (isset($this->configuration['view_pages'])) {
      $this->configuration['application'] = $this->configuration['view_pages'] ? 'all_pages' : '';
      unset($this->configuration['view_pages']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['application'] = [
      '#type' => 'radios',
      '#title' => $this->t('Views Condition'),
      '#default_value' => $this->configuration['application'],
      '#options' => [
        '' => $this->t('Not Restricted'),
        'all_pages' => $this->t('All View Pages'),
        'specific_views' => $this->t('Specific View Pages'),
      ],
      '#attributes' => [
        'class' => ['views-condition-application'],
      ],
    ];

    $views = $this->entityStorage->loadMultiple();
    /** @var \Drupal\views\Entity\View $view */
    foreach ($views as $view) {
      $displays = $view->get('display');
      // Don't include the master display, and skip this view if thats the only
      // display in the view.
      unset($displays['default']);
      foreach ($displays as $display_id => $display) {
        if ($display['display_plugin'] != 'page' || (isset($display['display_options']['enabled']) && !$display['display_options']['enabled'])) {
          unset($displays[$display_id]);
        }
      }

      if (!$view->status() || empty($displays)) {
        continue;
      }

      $form['views'][$view->id()] = [
        '#type' => 'details',
        '#title' => $view->label(),
        '#open' => FALSE,
        '#states' => [
          'visible' => [
            'input[name*="application"]' => ['value' => 'specific_views'],
          ],
        ],
      ];

      foreach ($displays as $display) {
        $default_value = !empty($this->configuration['views'][$view->id()][$display['id']]) ? $this->configuration['views'][$view->id()][$display['id']] : 0;
        $form['views'][$view->id()][$display['id']] = [
          '#type' => 'checkbox',
          '#title' => $display['display_title'],
          '#default_value' => $default_value,
        ];
      }
    }

    $form['#attached']['library'][] = 'views_condition/views_condition';
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if ($form_state->getValue('application') != 'specific_views') {
      $form_state->setValue('views', []);
      return;
    }

    $views_chosen = $form_state->getValue('views');
    foreach ($views_chosen as $view_id => &$displays) {
      $displays = array_filter($displays);
      if (empty($displays)) {
        unset($views_chosen[$view_id]);
      }
    }
    if (!$views_chosen) {
      $form_state->setError($form['application'], $this->t('No views selected for condition.'));
    }
    $form_state->setValue('views', $views_chosen);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['application'] = $form_state->getValue('application');
    $this->configuration['views'] = $form_state->getValue('views');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'application' => '',
      'views' => [],
    ];
    return $config + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Simple condition if specified to apply to all pages.
    if ($this->configuration['application'] == 'all_pages') {
      return (bool) $this->routeMatch->getParameter('view_id');
    }

    $view_id = $this->routeMatch->getParameter('view_id');
    $display_id = $this->routeMatch->getParameter('display_id');

    return !empty($this->configuration['views'][$view_id][$display_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->configuration['application'] == 'all_pages') {
      return t('Applied to all view pages.');
    }

    $summary = [];
    /** @var \Drupal\views\Entity\View $view */
    foreach ($this->entityStorage->loadMultiple(array_keys($this->configuration['views'])) as $view) {
      $display_summary = [];
      $displays = $view->get('display');
      $chosen_displays = $this->configuration['views'][$view->id()];

      foreach (array_keys($chosen_displays) as $display_id) {
        if (isset($displays[$display_id])) {
          $display_summary[] = $displays[$display_id]['display_title'];
        }
      }

      $summary[] = $view->label() . ': ' . implode(', ', $display_summary);
    }

    return t('View Pages - @views', ['@views' => implode("; ", $summary)]);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['module'][] = 'views';
    foreach (array_keys($this->configuration['views']) as $view_id) {
      $dependencies['config'][] = "views.view.$view_id";
    }
    return $dependencies;
  }

}
