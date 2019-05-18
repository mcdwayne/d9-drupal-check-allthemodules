<?php

namespace Drupal\lunr\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Lunr search' block.
 *
 * @Block(
 *   id = "lunr_search",
 *   admin_label = @Translation("Lunr search form"),
 *   category = @Translation("Forms")
 * )
 */
class LunrSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a LunrSearchBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $lunr_searches = $this->entityTypeManager->getStorage('lunr_search')->loadMultiple();

    $form['lunr_search_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Lunr search'),
      '#description' => $this->t('The Lunr search for this block.'),
      '#options' => [],
      '#required' => TRUE,
      '#default_value' => $this->configuration['lunr_search_id'],
    ];

    foreach ($lunr_searches as $id => $lunr_search) {
      $form['lunr_search_id']['#options'][$id] = $lunr_search->label();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['lunr_search_id'] = $form_state->getValue('lunr_search_id');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if (empty($this->configuration['lunr_search_id'])) {
      return $build;
    }

    $lunr_search = $this->entityTypeManager->getStorage('lunr_search')->load($this->configuration['lunr_search_id']);

    if (!$lunr_search) {
      return $build;
    }

    $build['form'] = [
      '#type' => 'form',
      '#id' => Html::getUniqueId('lunr-search-block-form'),
      '#method' => 'GET',
      '#attributes' => [
        'class' => [
          'lunr-search-block-form',
        ],
        'action' => Url::fromRoute('lunr_search.' . $this->configuration['lunr_search_id'])->toString(),
      ],
    ];

    $id = Html::getUniqueId('search');
    $build['form']['input'] = [
      '#type' => 'search',
      '#title' => $this->t('Keywords'),
      '#title_display' => 'invisible',
      '#id' => $id,
      '#name' => 'search',
    ];

    $build['form']['actions'] = ['#type' => 'actions'];
    $build['form']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#name' => '',
    ];

    CacheableMetadata::createFromObject($lunr_search)->applyTo($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    if (!empty($this->configuration['lunr_search_id'])) {
      $lunr_search = $this->entityTypeManager->getStorage('lunr_search')->load($this->configuration['lunr_search_id']);
      if ($lunr_search) {
        $dependencies[$lunr_search->getConfigDependencyKey()][] = $lunr_search->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

}
