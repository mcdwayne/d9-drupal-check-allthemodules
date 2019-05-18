<?php

namespace Drupal\paragraphs_collection\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs_collection\GridLayoutDiscoveryInterface;
use Drupal\paragraphs_collection\StyleDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The controller for overviews of Paragraphs Collection's discoverable items.
 *
 * The grid layout and style behavior plugins use pluggable YAML files which
 * define the concrete layouts and styles that can be then used by specific
 * Paragraph entities. There are plugin-specific classes for the discovery of
 * these files. Paragraphs Types can restrict the available items. This
 * controller creates overview pages for these items.
 *
 * @see \Drupal\paragraphs_collection\Plugin\paragraphs\Behavior\ParagraphsGridLayoutPlugin
 * @see \Drupal\paragraphs_collection\Plugin\paragraphs\Behavior\ParagraphsStylePlugin
 * @see \Drupal\paragraphs_collection\GridLayoutDiscoveryInterface
 * @see \Drupal\paragraphs_collection\StyleDiscoveryInterface
 */
class OverviewController extends ControllerBase {

  /**
   * The discovery service for grid layout files.
   *
   * @var \Drupal\paragraphs_collection\GridLayoutDiscoveryInterface
   */
  protected $gridLayoutDiscovery;

  /**
   * The discovery service for style files.
   *
   * @var \Drupal\paragraphs_collection\StyleDiscoveryInterface
   */
  protected $styleDiscovery;

  /**
   * A nested array of Paragraphs Type objects.
   *
   * A nested array. The first level is keyed by grid layout machine names. The
   * second level is keyed Paragraphs Type IDs. The second-level values are
   * Paragraphs Type objects that allow the respective grid layout. Grid layouts
   * are ordered by name.
   *
   * Example:
   * @code
   * [
   *   '1_2_1_column_layout' => [
   *     'grid_pt' => $grid_paragraphs_type_object,
   *   ]
   * ]
   * @endcode
   *
   * @var array
   */
  protected $paragraphsTypesGroupedByGridLayouts;

  /**
   * A nested array of Paragraphs Type objects.
   *
   * A nested array. The first level is keyed by style machine names. The second
   * level is keyed Paragraphs Type IDs. The second-level values are Paragraphs
   * Type objects that allow the respective grid layout. Styles are ordered by
   * name.
   *
   * Example:
   * @code
   * [
   *   'blue_style' => [
   *     'style_pt' => $style_paragraphs_type_object,
   *   ]
   * ]
   * @endcode
   *
   * @var array
   */
  protected $paragraphsTypesGroupedByStyles;

  /**
   * Constructs a \Drupal\paragraphs_collection\Controller\OverviewController object.
   *
   * @param \Drupal\paragraphs_collection\GridLayoutDiscoveryInterface $grid_layout_discovery
   *   The discovery service for grid layout files.
   * @param \Drupal\paragraphs_collection\StyleDiscoveryInterface $style_discovery
   *   The discovery service for style files.
   */
  public function __construct(GridLayoutDiscoveryInterface $grid_layout_discovery, StyleDiscoveryInterface $style_discovery) {
    $this->gridLayoutDiscovery = $grid_layout_discovery;
    $this->styleDiscovery = $style_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('paragraphs_collection.grid_layout_discovery'),
      $container->get('paragraphs_collection.style_discovery')
    );
  }

  /**
   * Lists grid layouts with the Paragraphs Types that allow them.
   *
   * @return array
   *   A nested array. The first level is keyed by grid layout machine names.
   *   The second level is keyed Paragraphs Type IDs. The second-level values
   *   are Paragraphs Type objects that allow the respective grid layout. Grid
   *   layouts are ordered by name.
   *   Example:
   *   @code
   *   [
   *     '1_2_1_column_layout' => [
   *       'grid_pt' => $grid_paragraphs_type_object,
   *     ]
   *   ]
   *   @endcode
   */
  public function getParagraphsTypesGroupedByGridLayouts() {
    if (isset($this->paragraphsTypesGroupedByGridLayouts)) {
      return $this->paragraphsTypesGroupedByGridLayouts;
    }

    $paragraph_type_ids = \Drupal::entityQuery('paragraphs_type')->execute();
    $paragraphs_types = ParagraphsType::loadMultiple($paragraph_type_ids);

    // Find all enabled grid layouts for each Paragraphs Type.
    // An empty array as the second-level value means that all grid layouts are
    // enabled for that Paragraphs type.
    $grid_layouts_grouped_by_paragraphs_types = [];
    foreach ($paragraphs_types as $paragraph_type_id => $paragraphs_type) {
      /** @var ParagraphsType $paragraphs_type */
      $configuration = $paragraphs_type->getBehaviorPlugin('grid_layout')->getConfiguration();
      if (isset($configuration['enabled']) && $configuration['enabled']) {
        $grid_layouts_grouped_by_paragraphs_types[$paragraph_type_id] = [];
        foreach ($configuration['available_grid_layouts'] as $key => $value) {
          if ($value) {
            $grid_layouts_grouped_by_paragraphs_types[$paragraph_type_id][] = $key;
          }
        }
      }
    }

    // Get all grid layouts ordered by title.
    $layouts = $this->gridLayoutDiscovery->getGridLayouts();
    uasort($layouts, function ($layout1, $layout2) {
      return strcasecmp($layout1['title'], $layout2['title']);
    });

    // Group Paragraphs Types by grid layouts.
    $paragraphs_types_grouped_by_grid_layouts = [];
    foreach ($layouts as $layout_id => $layout) {
      $paragraphs_types_grouped_by_grid_layouts[$layout_id] = [];
      foreach ($grid_layouts_grouped_by_paragraphs_types as $paragraphs_type_id => $enabled_layouts) {
        if ($enabled_layouts == [] || in_array($layout_id, $enabled_layouts)) {
          $paragraphs_types_grouped_by_grid_layouts[$layout_id][$paragraphs_type_id] = $paragraphs_types[$paragraphs_type_id];
        }
      }
    }

    return $this->paragraphsTypesGroupedByGridLayouts = $paragraphs_types_grouped_by_grid_layouts;
  }

  /**
   * Lists styles with the Paragraphs Types that allow them.
   *
   * @return array
   *   A nested array. The first level is keyed by style machine names. The
   *   second level is keyed Paragraphs Type IDs. The second-level values are
   *   Paragraphs Type objects that allow the respective grid layout. Styles
   *   are ordered by name.
   *   Example:
   *   @code
   *   [
   *     'blue_style' => [
   *       'style_pt' => $style_paragraphs_type_object,
   *     ]
   *   ]
   *   @endcode
   */
  public function getParagraphsTypesGroupedByStyles() {
    if (isset($this->paragraphsTypesGroupedByStyles)) {
      return $this->paragraphsTypesGroupedByStyles;
    }

    $paragraph_type_ids = \Drupal::entityQuery('paragraphs_type')->execute();
    $paragraphs_types = ParagraphsType::loadMultiple($paragraph_type_ids);

    // Find the used style group for each Paragraphs Type.
    // An as empty string as the second-level value means that the Paragraphs
    // Type uses all style groups.
    $styles_grouped_by_paragraphs_types = [];
    foreach ($paragraphs_types as $paragraph_type_id => $paragraphs_type) {
      /** @var ParagraphsType $paragraphs_type */
      $configuration = $paragraphs_type->getBehaviorPlugin('style')->getConfiguration();
      if (isset($configuration['enabled']) && $configuration['enabled']) {
        $styles_grouped_by_paragraphs_types[$paragraph_type_id] = array_keys($configuration['groups']);
      }
    }

    //Get all styles ordered by title.
    $styles = $this->styleDiscovery->getStyles();
    uasort($styles, function ($style1, $style2) {
      return strcasecmp($style1['title'], $style2['title']);
    });

    // Group Paragraphs Types by styles.
    $paragraphs_types_grouped_by_styles = [];
    foreach ($styles as $style_id => $style) {
      $paragraphs_types_grouped_by_styles[$style_id] = [];
      foreach ($styles_grouped_by_paragraphs_types as $paragraphs_type_id => $used_style_groups) {
        $enabled_styles = [];
        foreach ($used_style_groups as $used_style_group) {
          $enabled_styles += $this->styleDiscovery->getStyleOptions($used_style_group);
        }
        if (in_array($style_id, array_keys($enabled_styles))) {
          $paragraphs_types_grouped_by_styles[$style_id][$paragraphs_type_id] = $paragraphs_types[$paragraphs_type_id];
        }
      }
    }

    return $this->paragraphsTypesGroupedByStyles = $paragraphs_types_grouped_by_styles;
  }

  /**
   * Generates an overview page of available layouts for the grid layout plugin.
   *
   * @return array
   *   The output render array.
   */
  public function layouts() {
    $filters = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => ['table-filter', 'js-show', 'form--inline'],
        'data-table' => '.paragraphs-collection-overview-table',
      ],
      '#title' => $this->t('Filter'),
    ];
    $filters['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Grid layout label or ID'),
      '#size' => 40,
      '#attributes' => [
        'class' => ['table-filter-text'],
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the style label or ID to filter by.'),
      ],
    ];

    $header = [
      'label' => $this->t('Grid layout'),
      'details' => $this->t('Details'),
      'use' => $this->t('Used in'),
    ];

    $layouts = $this->gridLayoutDiscovery->getGridLayouts();

    $rows = [];
    foreach ($this->getParagraphsTypesGroupedByGridLayouts() as $layout_id => $value) {
      $layout = $layouts[$layout_id];

      $paragraphs_type_link_list = [];
      foreach ($value as $paragraphs_type_id => $paragraphs_type) {
        /** @var ParagraphsType $paragraphs_type */

        if($paragraphs_type_link_list != []) {
          $paragraphs_type_link_list[] = ['#plain_text' => ', '];
        }

        $paragraphs_type_link_list[] = [
          '#type' => 'link',
          '#title' => $paragraphs_type->label(),
          '#url' => $paragraphs_type->toUrl(),
          '#attributes' => [
            'class' => ['table-filter-paragraphs-type-source'],
          ],
        ];
      }

      $row['label'] = [
        '#type' => 'container',
        '#plain_text' => $layout['title'],
        '#attributes' => ['class' => ['table-filter-text-source']],
      ];
      $row['details'] = [
        '#type' => 'details',
        '#title' => !empty($layout['description']) ? $layout['description'] : $this->t('Description not available.'),
        '#open' => FALSE,
        '#attributes' => ['class' => ['overview-details']],
      ];
      $row['details']['id'] = [
        '#type' => 'item',
        '#title' => $this->t('ID'),
        '#prefix' => '<span class="container-inline">',
        '#suffix' => '</span>',
        'item' => [
          '#type' => 'container',
          '#plain_text' => $layout_id,
          '#attributes' => ['class' => ['table-filter-text-source']],
        ],
      ];
      $row['use'] = $paragraphs_type_link_list;

      $rows[] = $row;
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['paragraphs-collection-overview-table'],
      ],
    ];
    $table += $rows;

    $build['filters'] = $filters;
    $build['table'] = $table;
    $build['#attached']['library'] = ['paragraphs_collection/overview'];

    return $build;
  }

  /**
   * Generates an overview page of available styles for the styles plugin.
   *
   * @return array
   *   The output render array.
   */
  public function styles() {
    $grouped_styles = $this->getParagraphsTypesGroupedByStyles();
    return $this->formBuilder()->getForm('Drupal\paragraphs_collection\Form\StylesOverviewForm', $grouped_styles);
  }

}
