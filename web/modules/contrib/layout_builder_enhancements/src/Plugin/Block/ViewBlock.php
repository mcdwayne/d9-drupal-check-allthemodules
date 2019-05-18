<?php

namespace Drupal\layout_builder_enhancements\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\ResultRow;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Custom block.
 *
 * @Block(
 *   id = "layout_builder_enhancements_view_block",
 *   admin_label = @Translation("View block"),
 *   category = @Translation("View Block"),
 *   deriver = "Drupal\layout_builder_enhancements\Plugin\Derivative\ViewBlock"
 * )
 */
class ViewBlock extends BlockBase implements ContainerFactoryPluginInterface, BlockPluginInterface {

  protected $manager;
  protected $entityTypeManager;
  protected $displayRepository;
  protected $inPreview = FALSE;
  protected $offset = -1;
  protected $renderer;
  protected $viewsService;
  protected $resultCount = 0;
  protected $view;


  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $manager,
    EntityDisplayRepositoryInterface $display_repository,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->manager = $manager->getStorage('view');
    $this->entityTypeManager = $manager;
    $this->displayRepository = $display_repository;
    $this->renderer = $renderer;
    $this->view = $this->manager->load($this->getViewId());
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
      $container->get('entity_display.repository'),
      $container->get('renderer')
    );
  }

  /**
   * Set if this block is in preview.
   */
  public function inPreview($in_preview) {
    $this->inPreview = $in_preview;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->offset == -1) {
      return [];
    }
    $view = $this->getView();

    if (!$view && $this->inPreview) {
      return [
        '#markup' => $this->t('View @config do not exist anymore', ['@config' => $this->getDerivativeId()])
      ];
    }
    if (!$view) {
      return [];
    }

    if (!$this->configuration['show_all']) {
      $view->setItemsPerPage(1);
      if (!$this->configuration['respect_pager']) {
        $view->setCurrentPage(0);
      }
    }
    $view->execute();
    $rows = count($view->result);

    $build = [
      '#cache' => [
        'tags' => [],
      ],
    ];

    $view->getDisplay()->getCacheMetaData()->applyTo($build);
    $this->renderer->addCacheableDependency($build, $this->view);
    $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], $view->getCacheTags());

    if ($this->configuration['show_all'] && $rows > 0) {
      if ($this->configuration['show_header']) {
        $build['header'] = $view->display_handler->renderArea('header');
      }
      foreach ($view->result as $row) {
        $entity = $this->getRowEntity($row);
        $build['result'][] = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())
          ->view($entity, isset($this->configuration['view_mode'][$entity->getEntityTypeId()]) ? $this->configuration['view_mode'][$entity->getEntityTypeId()] : 'default');
      }
      if ($this->configuration['show_footer']) {
        $build['footer'] = $view->display_handler->renderArea('footer');
      }
      if ($this->configuration['show_pager']) {
        $build['pager'] = $view->renderPager($view->getExposedInput());
      }
    }
    elseif ($rows > 0) {
      if (!$this->configuration['respect_pager']) {
        $view->setCurrentPage(0);
      }
      $view->execute();
      $entity = $this->getRowEntity($view->result[0]);
      if ($entity == NULL && $this->inPreview) {
        $build = ['#markup' => $this->t('No content for row.')];
      } elseif ($entity != NULL) {
        $build['result'] = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())
          ->view($entity, isset($this->configuration['view_mode'][$entity->getEntityTypeId()]) ? $this->configuration['view_mode'][$entity->getEntityTypeId()] : 'default');
      }
    }
    elseif ($this->inPreview) {
      $build = ['#markup' => $this->t('No content for row.')];
    }
    $this->resultCount = $rows;
    return $build;
  }


  /**
   * Extracts the entity from a views Row.
   *
   * @param \Drupal\views\ResultRow $row
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getRowEntity(ResultRow $row) {
    if (is_a($row,'\Drupal\search_api\Plugin\views\ResultRow')) {
      $adapter = $row->_item->getOriginalObject();
      if (!$adapter) {
        return NULL;
      }
      return $adapter->getEntity();
    }
    return $row->_entity ?? NULL;
  }

  /**
   * Retrieve consumend rows.
   */
  public function getResultCount() {
    return $this->resultCount;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'view_mode' => 'default',
      'show_footer' => FALSE,
      'show_pager' => FALSE,
      'show_header' => FALSE,
      'respect_pager' => FALSE,
    ];
  }

  /**
   * Get a view.
   */
  public function getView() {
    $block_id = $this->getDerivativeId();
    $viewId = explode(':', $block_id)[0];
    $displayId = explode(':', $block_id)[1];
    $view = Views::getView($viewId);
    if (!$view) {
      return NULL;
    }
    $view->setDisplay($displayId);
    $view->setOffset($this->offset);
    return $view;
  }

  /**
   * Set offset.
   */
  public function setOffset($offset) {
    $this->offset = $offset;
  }

  /**
   * Get offset.
   */
  protected function getOffset() {
    return $this->offset;
  }

  /**
   * Get display id.
   */
  public function getDisplayId() {
    $block_id = $this->getDerivativeId();
    return explode(':', $block_id)[1];
  }

  /**
   * Get view id.
   */
  public function getViewId() {
    $block_id = $this->getDerivativeId();
    return explode(':', $block_id)[0];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $view = $this->getView();
    if (!$view) {
      return ['#markup' => $this->t('View does not exist')];
    }

    if (is_a($view->getQuery(), '\Drupal\search_api\Plugin\views\query\SearchApiQuery')) {
      $target_type = $view->getQuery()->getEntityTypes();
      foreach ($target_type as $type) {
        $form['view_mode'][$type] = [
          '#type' => 'select',
          '#options' => $this->displayRepository->getViewModeOptions($type),
          '#tree' => TRUE,
          '#title' => $this->t('View mode for @type', ['@type' => $type]),
          '#description' => $this->t('Who do you want to say hello to?'),
          '#default_value' => isset($config['view_mode']) ? $config['view_mode'] : '',
        ];
      }
    }
    else {
      $form['view_mode'][$view->getBaseEntityType()->id()] = [
        '#type' => 'select',
        '#options' => $this->displayRepository->getViewModeOptions($view->getBaseEntityType()->id()),
        '#tree' => TRUE,
        '#title' => $this->t('View mode'),
        '#description' => $this->t('Who do you want to say hello to?'),
        '#default_value' => isset($config['view_mode']) ? $config['view_mode'] : '',
      ];
    }

    $form['respect_pager'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Should respect pager'),
      '#default_value' => isset($config['respect_pager']) ? $config['respect_pager'] : '',
      '#states' => [
        'visible' => [
          ':input[name="settings[show_all]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['show_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all remaining rows'),
      '#default_value' => isset($config['show_all']) ? $config['show_all'] : '',
    ];

    $form['show_pager'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show pager'),
      '#default_value' => isset($config['show_pager']) ? $config['show_pager'] : '',
      '#states' => [
        'visible' => [
          ':input[name="settings[show_all]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['show_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show header areas'),
      '#default_value' => isset($config['show_header']) ? $config['show_header'] : '',
      '#states' => [
        'visible' => [
          ':input[name="settings[show_all]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['show_footer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show footer areas'),
      '#default_value' => isset($config['show_footer']) ? $config['show_footer'] : '',
      '#states' => [
        'visible' => [
          ':input[name="settings[show_all]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['view_mode'] = $values['view_mode'];
    $this->configuration['show_all'] = $values['show_all'];
    $this->configuration['show_pager'] = $values['show_pager'];
    $this->configuration['show_header'] = $values['show_header'];
    $this->configuration['show_footer'] = $values['show_footer'];
    $this->configuration['respect_pager'] = $values['respect_pager'];
  }

}
