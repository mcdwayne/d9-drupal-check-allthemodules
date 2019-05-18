<?php

namespace Drupal\ad_entity\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines blocks for displaying Advertisement.
 *
 * @Block(
 *   id = "ad_display",
 *   admin_label = @Translation("Display for Advertisement"),
 *   category = @Translation("Display for Advertisement"),
 *   deriver = "Drupal\ad_entity\Plugin\Derivative\AdDisplayBlock"
 * )
 */
class AdDisplayBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The storage of Display configs for Advertisement.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $adDisplayStorage;

  /**
   * The view builder for Display configs for Advertisement.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $adDisplayViewBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $type_manager->getStorage('ad_display'),
      $type_manager->getViewBuilder('ad_display')
    );
  }

  /**
   * AdDisplayBlock constructor.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $ad_display_storage
   *   The storage of Display configs for Advertisement.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $ad_display_view_builder
   *   The view builder for Display configs for Advertisement.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $ad_display_storage, EntityViewBuilderInterface $ad_display_view_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->adDisplayStorage = $ad_display_storage;
    $this->adDisplayViewBuilder = $ad_display_view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'module' => ['ad_entity'],
      'config' => ['ad_entity.display.' . $this->getDerivativeId()],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $id = $this->getDerivativeId();
    if ($ad_display = $this->adDisplayStorage->load($id)) {
      return Cache::mergeMaxAges(parent::getCacheMaxAge(), $ad_display->getCacheMaxAge());
    }
    return parent::getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $id = $this->getDerivativeId();
    if ($ad_display = $this->adDisplayStorage->load($id)) {
      return Cache::mergeContexts(parent::getCacheContexts(), $ad_display->getCacheContexts());
    }
    return parent::getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $id = $this->getDerivativeId();
    if ($ad_display = $this->adDisplayStorage->load($id)) {
      return Cache::mergeTags(parent::getCacheTags(), $ad_display->getCacheTags());
    }
    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $id = $this->getDerivativeId();

    if ($ad_display = $this->adDisplayStorage->load($id)) {
      $url = $ad_display->toUrl('edit-form')->toString();
      $label = $ad_display->label();
      $form['display_info'] = [
        '#markup' => $this->t('<strong>Using display config: <a href=":url" target="_blank">@label</a></strong>', [':url' => $url, '@label' => $label]),
        '#weight' => 10,
      ];
    }
    else {
      drupal_set_message($this->t('Stale reference: Failed to load corresponding Display configuration id @id.', ['@id' => $id]), 'error');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $id = $this->getDerivativeId();
    $build = [];
    if ($ad_display = $this->adDisplayStorage->load($id)) {
      if ($ad_display->access('view')) {
        $view = $this->adDisplayViewBuilder->view($ad_display, 'default');
        $build[$id] = $view;
      }
    }
    return $build;
  }

}
