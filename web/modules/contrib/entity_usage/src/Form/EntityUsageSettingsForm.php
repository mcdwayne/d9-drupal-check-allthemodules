<?php

namespace Drupal\entity_usage\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to configure entity_usage settings.
 */
class EntityUsageSettingsForm extends ConfigFormBase {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The Cache Render.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, RouteBuilderInterface $router_builder, CacheBackendInterface $cache_render) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->routerBuilder = $router_builder;
    $this->cacheRender = $cache_render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('router.builder'),
      $container->get('cache.render')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_usage_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entity_usage.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_usage.settings');
    $configured_types = $config->get('local_task_enabled_entity_types') ?: [];

    $form['local_task_enabled_entity_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Local tasks'),
      '#description' => $this->t('Check in which entity types there should be a tab (local task) linking to the usage page.'),
      '#tree' => TRUE,
    ];

    // Get all applicable entity types.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (($entity_type instanceof ContentEntityTypeInterface) && $entity_type->hasLinkTemplate('canonical')) {
        $form['local_task_enabled_entity_types'][$entity_type_id] = [
          '#type' => 'checkbox',
          '#title' => $entity_type->getLabel(),
          '#default_value' => in_array($entity_type_id, $configured_types),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('entity_usage.settings');

    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'local_task_enabled_entity_types') {
        $enabled_entity_types = [];
        foreach ($value as $entity_type_id => $enabled) {
          if ($enabled) {
            $enabled_entity_types[] = $entity_type_id;
          }
        }
        $value = $enabled_entity_types;
      }
      $config->set($key, $value);
    }
    $config->save();

    $this->routerBuilder->rebuild();
    $this->cacheRender->invalidateAll();

    parent::submitForm($form, $form_state);
  }

}
