<?php

namespace Drupal\homebox\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HomeboxForm.
 */
class HomeboxSettingsForm extends EntityForm {

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * The Homebox storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme containing the blocks.
   *
   * @var string
   */
  protected $theme;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Manager block plugin.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Get layout instance.
   *
   * @param string $layout_id
   *   Layout id.
   * @param array $layout_settings
   *   An array of configuration relevant to the layout.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return mixed|object
   *   Layout instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getLayout($layout_id, array $layout_settings, FormStateInterface $form_state) {
    if (!$layout_plugin = $form_state->get('layout_plugin')) {
      $layout_plugin = $this->layoutPluginManager->createInstance($layout_id, $layout_settings);
      $form_state->set('layout_plugin', $layout_plugin);
    }

    return $layout_plugin;
  }

  /**
   * HomeboxSettingsForm constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManager $layoutPluginManager
   *   Layout plugin manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage class.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Manager block plugin.
   */
  public function __construct(LayoutPluginManager $layoutPluginManager, EntityStorageInterface $entity_storage, ThemeManagerInterface $theme_manager, Request $request, BlockManagerInterface $block_manager) {
    $this->layoutPluginManager = $layoutPluginManager;
    $this->storage = $entity_storage;
    $this->themeManager = $theme_manager;
    $this->request = $request;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    /* @var \Drupal\Core\Layout\LayoutPluginManager $layout_plugin_manager */
    $layout_plugin_manager = $container->get('plugin.manager.core.layout');
    /* @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = $container->get('theme.manager');
    /* @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $container->get('request_stack')->getCurrentRequest();
    /* @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $container->get('plugin.manager.block');
    return new static(
      $layout_plugin_manager,
      $entity_manager->getStorage('block'),
      $theme_manager,
      $request,
      $block_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'block/drupal.block';
    $form['#attached']['library'][] = 'block/drupal.block.admin';
    $form['#attributes']['class'][] = 'clearfix';

    // Build the form tree.
    $form['blocks'] = $this->buildBlocksForm();

    $form['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save blocks'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Build block edit form.
   *
   * @return array
   *   Block form array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildBlocksForm() {
    // Build blocks first for each region.
    $blocks = [];
    /* @var \Drupal\homebox\Entity\HomeboxInterface $homebox */
    $homebox = $this->entity;
    $homebox_blocks = $homebox->getBlocks();

    if ($this->request->query->has('block-delete')) {
      $delete = $this->request->query->get('block-delete');
      foreach ($homebox_blocks as $id => $block) {
        if ($block['id'] == $delete) {
          unset($homebox_blocks[$id]);
          break;
        }
      }
      $homebox->setBlocks($homebox_blocks);
      $homebox->save();
    }

    if ($this->request->query->has('block-disable')) {
      $disable = $this->request->query->get('block-disable');
      foreach ($homebox_blocks as $id => $block) {
        if ($block['id'] == $disable) {
          $homebox_blocks[$id]['status'] = FALSE;
          break;
        }
      }
      $homebox->setBlocks($homebox_blocks);
      $homebox->save();
    }

    if ($this->request->query->has('block-enable')) {
      $enable = $this->request->query->get('block-enable');
      foreach ($homebox_blocks as $id => $block) {
        if ($block['id'] == $enable) {
          $homebox_blocks[$id]['status'] = TRUE;
          break;
        }
      }
      $homebox->setBlocks($homebox_blocks);
      $homebox->save();
    }

    foreach ($homebox_blocks as $block) {
      $definition = $this->blockManager->getDefinition($block['id']);
      $blocks[$block['region']][$block['id']] = [
        'label' => $definition['admin_label'],
        'entity_id' => $block['id'],
        'entity' => NULL,
        'category' => $definition['category'],
        'status' => $block['status'],
      ];
      if (isset($block['weight'])) {
        $blocks[$block['region']][$block['id']]['weight'] = $block['weight'];
      }
      if (isset($block['title'])) {
        $blocks[$block['region']][$block['id']]['title_override'] = $block['title'];
      }
    }

    $placement = FALSE;
    if ($this->request->query->has('block-placement')) {
      $placement = $this->request->query->get('block-placement');
      $region = $this->request->query->get('region');
      foreach ($homebox_blocks as $id => $block) {
        if ($block['id'] == $placement) {
          unset($homebox_blocks[$id]);
          break;
        }
      }
      $homebox_blocks[] = [
        'id' => $placement,
        'weight' => 0,
        'region' => $region,
        'status' => TRUE,
      ];
      $homebox->setBlocks($homebox_blocks);
      $homebox->save();
      $form['#attached']['drupalSettings']['blockPlacement'] = $placement;
      $definition = $this->blockManager->getDefinition($placement);
      $blocks[$region][$placement] = [
        'label' => $definition['admin_label'],
        'entity_id' => $placement,
        'weight' => 0,
        'entity' => NULL,
        'category' => $definition['category'],
        'status' => TRUE,
      ];
    }

    $form = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Custom title'),
        $this->t('Category'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
        // @todo Need to hide status column.
        // $this->t('Status'),
      ],
      '#attributes' => [
        'id' => 'blocks',
      ],
    ];

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($blocks) / 2);

    // @todo remove duplicated code
    $layoutInstance = $this->layoutPluginManager->createInstance($homebox->getRegions(), []);
    /** @var \Drupal\Core\Layout\LayoutDefinition $plugin_definition */
    $plugin_definition = $layoutInstance->getPluginDefinition();

    // Loop over each region and build blocks.
    $regions = $plugin_definition->getRegions();

    $region_list = [];
    foreach ($regions as $name => $label) {
      $region_list[$name] = $label['label'];
    }

    foreach ($regions as $region => $title) {
      $form['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      ];
      $form['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      ];

      $form['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region],
          'no_striping' => TRUE,
        ],
      ];

      $form['region-' . $region]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'region-title__action'],
          ],
        ],
        '#prefix' => $region,
        '#type' => 'link',
        '#title' => $this->t('Place block <span class="visually-hidden">in the @region region</span>', ['@region' => $title['label']]),
        '#url' => Url::fromRoute('homebox.block.admin_library', ['homebox' => $homebox->id()], ['query' => ['region' => $region]]),
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];

      $form['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            empty($blocks[$region]) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $form['region-' . $region . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (isset($blocks[$region])) {
        foreach ($blocks[$region] as $info) {
          $block_id = $info['entity_id'];

          $form[$block_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          $form[$block_id]['#attributes']['class'][] = $info['status'] ? 'block-enabled' : 'block-disabled';
          if ($placement && $placement == Html::getClass($block_id)) {
            $form[$block_id]['#attributes']['class'][] = 'color-success';
            $form[$block_id]['#attributes']['class'][] = 'js-block-placed';
          }
          $form[$block_id]['info'] = [
            '#plain_text' => $info['status'] ? $info['label'] : $this->t('@label (disabled)', ['@label' => $info['label']]),
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          $form[$block_id]['title_override'] = [
            '#type' => 'textfield',
            '#maxlength' => 55,
          ];

          if (isset($info['title_override'])) {
            $form[$block_id]['title_override']['#default_value'] = $info['title_override'];
          }

          $form[$block_id]['type'] = [
            '#markup' => $info['category'],
          ];
          $form[$block_id]['region-theme']['region'] = [
            '#type' => 'select',
            '#default_value' => $region,
            '#required' => TRUE,
            '#title' => $this->t('Region for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#options' => $region_list,
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
            '#parents' => ['blocks', $block_id, 'region'],
          ];
          $form[$block_id]['region-theme']['theme'] = [
            '#type' => 'hidden',
            '#value' => $this->getThemeName(),
            '#parents' => ['blocks', $block_id, 'theme'],
          ];
          $form[$block_id]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $info['weight'],
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];
          $form[$block_id]['operations'] = $this->buildOperations($block_id, $info['status']);
          $form[$block_id]['status'] = [
            '#type' => 'hidden',
            '#value' => strval($info['status']),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-status', 'block-status-' . $region],
            ],
          ];
        }
      }
    }
    return $form;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param int $block_id
   *   The block on which the linked operations will be performed.
   * @param bool $status
   *   Status of block.
   *
   * @return array
   *   A renderable array of operation links.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::buildRow()
   */
  public function buildOperations($block_id, $status) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($block_id, $status),
    ];

    return $build;
  }

  /**
   * Gets this list's default operations.
   *
   * @param int $block_id
   *   The entity the operations are for.
   * @param bool $status
   *   Status of block.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  public function getOperations($block_id, $status) {
    $operations['delete'] = [
      'title' => t('Delete'),
      'weight' => -10,
      'url' => Url::fromRoute(
        'homebox.settings_form',
        ['homebox' => $this->entity->id()],
        ['query' => ['block-delete' => $block_id]]
      ),
    ];

    if (!$status) {
      $operations['enable'] = [
        'title' => t('Enable'),
        'weight' => -15,
        'url' => Url::fromRoute(
          'homebox.settings_form',
          ['homebox' => $this->entity->id()],
          ['query' => ['block-enable' => $block_id]]
        ),
      ];
    }
    else {
      $operations['disable'] = [
        'title' => t('Disable'),
        'weight' => 40,
        'url' => Url::fromRoute(
          'homebox.settings_form',
          ['homebox' => $this->entity->id()],
          ['query' => ['block-disable' => $block_id]]
        ),
      ];
    }

    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\homebox\Entity\HomeboxInterface $homebox */
    $homebox = $this->entity;
    $blocks = [];
    $form_blocks = $form_state->getValue('blocks');
    if ($form_blocks != '') {
      foreach ($form_blocks as $block_id => $block) {
        $blocks[] = [
          'id' => $block_id,
          'title' => $block['title_override'],
          'region' => $block['region'],
          'weight' => $block['weight'],
          'status' => (bool) $block['status'],
        ];
      }
    }
    $homebox->setBlocks($blocks);
    $homebox->save();

    $form_state->setRedirectUrl($homebox->toUrl('collection'));
  }

  /**
   * Gets the name of the theme used for this block listing.
   *
   * @return string
   *   The name of the theme.
   */
  protected function getThemeName() {
    // If no theme was specified, use the current theme.
    if (!$this->theme) {
      $this->theme = $this->themeManager->getActiveTheme()->getName();
    }
    return $this->theme;
  }

}
