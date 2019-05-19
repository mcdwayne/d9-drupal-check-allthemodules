<?php

namespace Drupal\simple_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to view a specific simple content.
 *
 * @Block(
 *   admin_label =  @Translation("Existing simple content"),
 *   category = @Translation("Simple content"),
 *   id = "existing_simple_content",
 * )
 */
class ExistingSimpleContentBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The simple content entity.
   *
   * @var \Drupal\simple_content\Entity\SimpleContentInterface
   */
  protected $simpleContent;

  /**
   * Constructs a new EntityView.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => 'default',
      'simple_content_id' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['simple_content_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'simple_content',
      '#selection_handler' => 'default:simple_content',
      '#selection_settings' => [],
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => $this->getEntity(),
      '#size' => '60',
      '#placeholder' => $this->t('Select simple content'),
    ];

    $options = $this->entityManager->getViewModeOptions('simple_content');

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('View mode'),
      '#default_value' => $this->configuration['view_mode'],
      '#access' => count($options) > 1
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['simple_content_id'] = $form_state->getValue('simple_content_id');
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($entity = $this->getEntity()) {
      $view_builder = $this->entityManager->getViewBuilder($entity->getEntityTypeId());
      $build = $view_builder->view($entity, $this->configuration['view_mode']);

      CacheableMetadata::createFromObject($entity)
        ->applyTo($build);
    }

    return $build;
  }

  /**
   * Get the entity.
   *
   * @return \Drupal\simple_content\Entity\SimpleContentInterface
   */
  protected function getEntity() {
    if (!isset($this->simpleContent)) {
      try {
        if ($this->configuration['simple_content_id']) {
          $load = $this->entityManager->getStorage('simple_content')->load($this->configuration['simple_content_id']);
          if ($load) {
            $this->simpleContent = $load;
          }
        }
      }
      catch (\Exception $ignored) {}
    }

    return $this->simpleContent;
  }

}
