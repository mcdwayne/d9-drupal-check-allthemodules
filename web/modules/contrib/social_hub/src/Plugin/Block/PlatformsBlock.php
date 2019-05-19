<?php

namespace Drupal\social_hub\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\social_hub\PlatformIntegrationPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block plugin implementation for platforms.
 *
 * @Block(
 *  id = "social_hub_platforms",
 *  admin_label = @Translation("Platforms"),
 *  category = @Translation("Social")
 * )
 *
 * @internal
 *   Plugin classes are internal.
 *
 * @phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar
 * @phpcs:disable Drupal.Commenting.PostStatementComment.Found
 */
class PlatformsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The platform integration plugin manager.
   *
   * @var \Drupal\social_hub\PlatformIntegrationPluginManager
   */
  private $pluginManager;

  /**
   * The platform entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  private $storage;

  /**
   * The platform entities.
   *
   * @var \Drupal\social_hub\PlatformInterface[]
   */
  private $entities;

  /**
   * Constructs PlatformsBlock instance.
   *
   * @param array $configuration
   *   An array of block configuration.
   * @param string $plugin_id
   *   The block plugin id.
   * @param mixed $plugin_definition
   *   The block plugin definition.
   * @param \Drupal\social_hub\PlatformIntegrationPluginManager $plugin_manager
   *   The platform integrations manager.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage
   *   The platform entities storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PlatformIntegrationPluginManager $plugin_manager,
    ConfigEntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $plugin_manager;
    $this->storage = $storage;
    $this->entities = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.social_hub.platform'),
      $container->get('entity_type.manager')->getStorage('platform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $plugins = $this->pluginManager->getPluginsAsOptions();

    if (empty($plugins)) {
      $form['#markup'] = $this->t('There are no plugins implemented. Please, follow the instructions provided in @link', [
        '@link' => (string) Url::fromRoute('help.page.social_hub')->toString(),
      ]);

      return $form;
    }

    $configuration = $this->getConfiguration();

    $form['plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available plugins'),
      '#descrption' => $this->t('When rendering the block only those platforms implementing the selected plugins will be used for the output.'), // NOSONAR
      '#options' => $plugins,
      '#default_value' => $configuration['plugins'],
      '#required' => TRUE,
    ];

    $form['wrapper_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS classes'),
      '#description' => $this->t('A list of space-separated CSS classes to apply to the block. E.g. "class-1 class-2".'), // NOSONAR
      '#default_value' => $this->configuration['wrapper_classes'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['plugins'] = $form_state->getValue('plugins');
    $this->configuration['wrapper_classes'] = $form_state->getValue('wrapper_classes');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->fetchEntities();
    $build = [];
    $metadata = BubbleableMetadata::createFromObject($this);

    if (!empty(trim($this->configuration['wrapper_classes']))) {
      $classes = explode(' ', trim($this->configuration['wrapper_classes']));
      $build['#attributes']['class'] = $classes;
    }

    foreach ($this->entities as $entity) {
      $item = $entity->build($this->configuration['plugins']);
      $metadata->merge(BubbleableMetadata::createFromRenderArray($item));
      $build[] = $item;
    }

    $metadata->applyTo($build);

    return $build;
  }

  /**
   * Fetch platform entities.
   */
  private function fetchEntities() {
    $results = $this->storage->getQuery()
      ->condition('plugins.*', $this->configuration['plugins'], 'IN')
      ->condition('status', 1)
      ->execute();

    if (!empty($results)) {
      $this->entities = $this->storage->loadMultiple($results);
    }
  }

}
