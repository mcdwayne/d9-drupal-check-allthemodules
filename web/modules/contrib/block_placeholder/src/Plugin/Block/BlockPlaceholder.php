<?php

namespace Drupal\block_placeholder\Plugin\Block;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_placeholder\BlockPlaceholderManagerInterface;
use Drupal\block_placeholder\Entity\BlockPlaceholderInterface;
use Drupal\block_placeholder\Entity\BlockPlaceholderReference;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define block placeholder block configuration.
 *
 * @Block(
 *   id = "block_placeholder",
 *   admin_label = @Translation("Block Placeholder"),
 *   category = @Translation("Block Placeholder")
 * )
 */
class BlockPlaceholder extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var BlockPlaceholderManagerInterface
   */
  protected $blockPlaceholderManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    BlockPlaceholderManagerInterface $block_placeholder_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->blockPlaceholderManager = $block_placeholder_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('block_placeholder.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_placeholder' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['block_placeholder'] = [
      '#type' => 'select',
      '#title' => $this->t('Block Placeholder'),
      '#description' => $this->t('Select the block placeholder.'),
      '#required' => TRUE,
      '#options' => $this->getBlockPlaceholderOptions(),
      '#default_value' => $this->configuration['block_placeholder'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->hasBlockPlaceholder()) {
      return [];
    }
    /** @var BlockPlaceholderInterface $entity */
    $entity = $this->getBlockPlaceholderEntity();

    if (!$entity instanceof BlockPlaceholderInterface) {
      return [];
    }
    $elements = [];
    $cache_tags = [];

    /** @var BlockContentInterface $reference */
    foreach ($entity->loadReferences() as $reference_id => $reference) {
      if (!$reference instanceof BlockContentInterface) {
        continue;
      }
      $entity_bundle = $reference->bundle();
      $entity_type = $reference->getEntityTypeId();

      $weight = $reference->block_placeholder_weight->value;
      $cache_tags = Cache::mergeTags($cache_tags, $reference->getCacheTags());

      if (isset($elements[$weight])) {
        $weight = $this->increaseWeight($elements, $weight);
      }
      $renderer = $this->entityTypeManager
        ->getViewBuilder($reference->getEntityTypeId());

      $elements[$weight] = [
        '#theme' => 'block_placeholder',
        '#placeholder' => $entity,
        '#attributes' => [
          'class' => $this->formatHtmlClasses([
            'block-placeholder',
            "block-placeholder-{$entity_type}-{$entity_bundle}",
            "block-placeholder-item-{$reference_id}",
          ])
        ],
        '#content_attributes' => [
          'class' => $this->getBlockPlaceholderContentClasses($reference),
        ],
        'content' => $renderer->view($reference),
      ];
    }
    ksort($elements, SORT_NUMERIC);

    // Re-key the array index after the sort order has been applied.
    $elements = array_values($elements);
    $elements['#has_reference'] = FALSE;

    if (!empty($cache_tags)) {
      $elements += [
        '#cache' => [
          'tags' => $cache_tags
        ],
        '#has_reference' => TRUE,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $config = $this->getConfiguration();
    if (!isset($config['block_placeholder'])) {
      return [];
    }
    $block_placeholder = $config['block_placeholder'];

    return [
      'config' => [
        'block_placeholder.placeholder_reference.' . $block_placeholder
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['block_placeholder'] = $form_state->getValue('block_placeholder');
  }

  /**
   * Get block placeholder content classes.
   *
   * @param \Drupal\block_content\BlockContentInterface $block
   *   The block entity instance.
   *
   * @return array
   *   An array of formatted placeholder classes.
   */
  protected function getBlockPlaceholderContentClasses(BlockContentInterface $block) {
    if ($block->block_placeholder_class->isEmpty()) {
      return [];
    }

    return $this->formatHtmlClasses($block->block_placeholder_class->value);
  }

  /**
   * Format HTML classes.
   *
   * @param $classes
   *   Either a string of multiple classes or an array.
   * @param string $delimiter
   *   A delimiter on which to separate multiple classes.
   *
   * @return array
   *   An array of formatted HTML classes.
   */
  protected function formatHtmlClasses($classes, $delimiter = ',') {
    if (!is_array($classes)) {
      $classes = explode($delimiter, $classes);
    }

    return array_map(
      '\Drupal\Component\Utility\Html::getClass',
      array_map('trim', $classes)
    );
  }

  /**
   * Increase weight if there is a collision.
   *
   * @param array $elements
   *   An array of elements that have been defined.
   * @param $weight
   *   The current weight that's already been defined.
   *
   * @return mixed
   */
  protected function increaseWeight(array $elements, $weight) {
    do {
      $new_weight = ++$weight;
    } while(isset($elements[$new_weight]));

    return $new_weight;
  }

  /**
   * Block placeholder has been defined.
   *
   * @return bool
   */
  protected function hasBlockPlaceholder() {
    return isset($this->getConfiguration()['block_placeholder']);
  }

  /**
   * Get block placeholder entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getBlockPlaceholderEntity() {
    if (!$this->hasBlockPlaceholder()) {
      throw new \RuntimeException(
        'Block placeholder has not been configured.'
      );
    }
    $entity_id = $this->configuration['block_placeholder'];

    return $this->blockPlaceholderManager->load($entity_id);
  }

  /**
   * Get block placeholder options.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getBlockPlaceholderOptions() {
    $options = [];

    foreach ($this->blockPlaceholderManager->loadMultiple() as $name => $reference) {
      if (!$reference instanceof BlockPlaceholderReference) {
        continue;
      }

      $options[$name] = $reference->label();
    }

    return $options;
  }
}
