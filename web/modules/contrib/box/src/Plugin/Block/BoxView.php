<?php

namespace Drupal\box\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to view a specific box.
 *
 * @Block(
 *   id = "box_view",
 *   admin_label = @Translation("Box view"),
 * )
 */
class BoxView extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new BoxView.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'machine_name' => '',
      'view_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name'),
      '#default_value' => $this->configuration['machine_name'],
      '#required' => TRUE,
    ];
    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions('box'),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->configuration['view_mode'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    /** @var \Drupal\box\BoxStorageInterface $box_storage */
    $box_storage = $this->entityTypeManager->getStorage('box');
    $box = $box_storage::loadByMachineName($form_state->getValue('machine_name'));

    // Verify if box with given machine name exists.
    if (empty($box)) {
      drupal_set_message($this->t('Please use valid box machine name.'), 'error');
      // Add block form on Panels page has different structure than Add block form on Block layout page.
      if (isset($form['settings']['machine_name'])) {
        $form_state->setError($form['settings']['machine_name'], $this->t('Please use valid box machine name.'));
      }
      else {
        $form_state->setError($form['machine_name'], $this->t('Please use valid box machine name.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('machine_name', $form_state->getValue('machine_name'));
    $this->setConfigurationValue('view_mode', $form_state->getValue('view_mode'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\box\BoxStorageInterface $box_storage */
    $box_storage = $this->entityTypeManager->getStorage('box');
    $box = $box_storage::loadByMachineName($this->configuration['machine_name']);

    $view_builder = $this->entityTypeManager->getViewBuilder('box');
    $build = $view_builder->view($box, $this->configuration['view_mode']);

    CacheableMetadata::createFromObject($box)
      ->applyTo($build);

    return $build;
  }

}
