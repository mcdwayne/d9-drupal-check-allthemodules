<?php

namespace Drupal\gridstack_ui\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\gridstack\Entity\GridStack;
use Drupal\blazy\Form\BlazyAdminInterface;
use Drupal\gridstack\GridStackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends base form for gridstack instance configuration form.
 */
class GridStackForm extends EntityForm {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The GridStack manager service.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a GridStackForm object.
   */
  public function __construct(FileSystemInterface $file_system, Messenger $messenger, BlazyAdminInterface $blazy_admin, GridStackManagerInterface $manager) {
    $this->fileSystem = $file_system;
    $this->messenger  = $messenger;
    $this->blazyAdmin = $blazy_admin;
    $this->manager    = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('messenger'),
      $container->get('blazy.admin'),
      $container->get('gridstack.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Change page title for the duplicate operation.
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate gridstack optionset</em>: @label', ['@label' => $this->entity->label()]);
      $this->entity = $this->entity->createDuplicate();
    }

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit gridstack optionset</em>: @label', ['@label' => $this->entity->label()]);
    }

    $entity     = $this->entity;
    $tooltip    = ['class' => ['is-tooltip']];
    $default    = GridStack::load('default');
    $is_default = $entity->id() == 'default';
    $options    = $entity->getOptions();
    $settings   = $entity->getOptions('settings');
    $grids      = $entity->getEndBreakpointGrids();
    $manager    = $this->manager;
    $admin_css  = $manager->configLoad('admin_css', 'blazy.settings');
    $framework  = $manager->configLoad('framework', 'gridstack.settings');
    $is_dev     = $manager->configLoad('dev', 'gridstack.settings');

    $classes = ['gridstack', 'slick', 'optionset', 'gridstack--ui'];
    foreach ($classes as $class) {
      $form['#attributes']['class'][] = 'form--' . $class;
    }

    if (!$entity->isNew()) {
      $form['#attributes']['class'][] = 'form--optionset--' . str_replace('_', '-', $entity->id());
    }

    $form['#attached']['library'][] = $is_dev ? 'gridstack/dev' : 'gridstack/all';
    $form['#attached']['library'][] = 'gridstack/admin';
    $form['#attached']['drupalSettings']['gridstack'] = $default->getOptions('settings');

    if ($admin_css) {
      $form['#attached']['library'][] = 'blazy/admin';
    }

    // Load all grids to get live preview going.
    foreach (range(1, 11) as $key) {
      $form['#attached']['library'][] = 'gridstack/gridstack.' . $key;
    }

    $active_settings    = $entity->getOptions('settings');
    $framework_settings = $default->getOptions('settings');

    $framework_settings['cellHeight']           = 60;
    $framework_settings['isNested']             = TRUE;
    $framework_settings['minWidth']             = 1;
    $framework_settings['verticalMargin']       = 15;
    $framework_settings['width']                = 12;
    $framework_settings['disableOneColumnMode'] = TRUE;

    $framework_enabled = FALSE;
    if (!empty($framework)) {
      $form['#attributes']['class'][] = 'form--framework';

      $use_framework = $entity->getOption('use_framework');
      if (!empty($use_framework)) {
        if (strpos($framework, 'bootstrap') !== FALSE) {
          $form['#attributes']['class'][] = 'form--bs';
        }
        $form['#attributes']['class'][] = 'form--' . $framework;
        $active_settings = $framework_settings;
        $framework_enabled = TRUE;
      }
    }

    if (!$framework_enabled) {
      $form['#attributes']['class'][] = 'form--gridstack-js';
    }

    $foundation = $framework_enabled && $framework == 'foundation';
    $icon_breakpoint = ($foundation || $framework == 'bootstrap3') ? 'lg' : 'xl';

    $form['#attributes']['data-icon'] = $icon_breakpoint;

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $entity->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the GridStack optionset."),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--first has-tooltip clearfix">',
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $entity->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\gridstack\Entity\GridStack::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => !$entity->isNew(),
      '#suffix'        => '</div>',
    ];

    $form['screenshot'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'gridstack-screenshot',
        'id' => 'gridstack-screenshot',
      ],
    ];

    $form['canvas'] = [
      '#markup' => '<canvas id="gridstack-canvas"></canvas>',
      '#allowed_tags' => ['canvas'],
    ];

    $data_config           = $this->jsonify($active_settings, TRUE);
    $data_config_framework = $this->jsonify($framework_settings, TRUE);

    $js_settings = [
      'lazy'       => '',
      'blazy'      => FALSE,
      'background' => FALSE,
      'breakpoint' => 'lg',
      'optionset'  => $entity->id(),
      '_admin'     => TRUE,
      'use_js'     => FALSE,
    ];

    // Dummy template.
    $image_style_box = [
      '#type'         => 'select',
      '#options'      => image_style_options(TRUE),
      '#empty_option' => $this->t('- IMG style -'),
      '#attributes'   => [
        'class' => [
          'form-select--image-style',
          'form-select--original',
        ],
        'data-imageid' => '',
        'id' => '',
      ],
      '#wrapper_attributes' => ['class' => ['form-item--image-style']],
    ];

    $form['template'] = [
      '#type'        => 'container',
      '#attributes'  => ['id' => 'gridstack-template', 'class' => ['visually-hidden']],
      '#dummies'     => TRUE,
      '#theme'       => ['gridstack_ui_dummy'],
      '#image_style' => $image_style_box,
    ];

    // Preview template.
    $settings['root']          = TRUE;
    $settings['display']       = 'main';
    $settings['storage']       = '';
    $settings['use_framework'] = FALSE;
    $settings['config']        = $data_config;

    $entity->gridsJsonToArray($settings);

    $js_settings = array_merge($js_settings, $settings);

    $form['json'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => ['class' => ['gridstack-json', 'visually-hidden']],
    ];

    $form['json']['grids'] = [
      '#type' => 'container',
    ];

    $form['json']['breakpoints'] = [
      '#type'          => 'hidden',
      '#default_value' => $entity->getJson('breakpoints'),
    ];

    $form['json']['settings'] = [
      '#type'          => 'hidden',
      '#default_value' => $entity->getJson('settings'),
    ];

    $form['options'] = [
      '#type'          => 'container',
      '#tree'          => TRUE,
      '#title'         => $this->t('Options'),
      '#title_display' => 'invisible',
      '#attributes'    => ['class' => ['details--settings', 'has-tooltip']],
      '#access'        => $entity->id() == 'default' ? FALSE : TRUE,
    ];

    $form['options']['icon'] = [
      '#type' => 'hidden',
      '#default_value' => isset($options['icon']) ? $options['icon'] : '',
      '#attributes' => [
        'id' => 'gridstack-icon',
        'data-url' => $entity->getIconUrl(TRUE),
      ],
    ];

    $form['options']['use_framework'] = [
      '#type'               => 'checkbox',
      '#title'              => $this->t('Use static <span>@framework</span> grid', ['@framework' => $framework]),
      '#default_value'      => $entity->getOption('use_framework'),
      '#description'        => $this->t("Check to enable static grid framework. Must enable the support <a href=':url'>here</a> first. This requires basic comprehension on how a static grid like Bootstrap, or Foundation, works. And GridStack preview limitation. Basically on how columns and rows build up the grid system. Failing to understand it may result in broken grids (this is no issue if using GridStack JS, unchecked).<br>If checked: <ul><li>No GridStack JS/ CSS assets are loaded at front-end.</li><li>Must have a theme, or module, that loads Bootstrap/ Foundation CSS for you.</li><li>No fixed height, just auto height. Repeat, height is ignored.</li><li>Boxes are floating like normal CSS floats, no longer absolutely positioned. The previews may trick you, bear with it.</li><li>No longer a fixed-height magazine layout. Just a normal floating CSS grid layout.</li><li>This layout is only available for core <strong>Layout Builder, DS, Panelizer, or Widget</strong> modules. Leave it unchecked if not using them, else useless for GridStack alone.</li></ul>Clone an existing Default Bootstrap/ Foundation optionset to begin with. Limitation: pull, push classes are not supported, yet.", [':url' => Url::fromRoute('gridstack.settings')->toString()]),
      '#wrapper_attributes' => ['class' => ['use-framework', 'form-item--tooltip-bottom']],
      '#disabled'           => $framework_enabled,
    ];

    $form['options']['type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Type'),
      '#options'       => [
        'full'    => $this->t('Full'),
        'partial' => $this->t('Partial'),
        'page'    => $this->t('Page'),
      ],
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => isset($options['type']) ? $options['type'] : '',
      '#description'   => $this->t('TODO! Non-functional yet. Defines how the layout will be used in a page. This will also be used for layout Category. Can be one of: <ul><li><strong>full</strong>: The layout is used for an entire page, not just the main content.</li><li><strong>page</strong>: The layout is used for just the main page response (i.e., whatever is returned by the controller).</li><li><strong>partial:</strong> A partial layout that can be used for sub-regions -- roughly analogous to Mini Panels in Drupal 7.</li></ul>'),
      '#wrapper_attributes' => ['class' => ['visually-hidden']],
    ];

    if ($admin_css) {
      $form['options']['use_framework']['#field_suffix'] = '&nbsp;';
      $form['options']['use_framework']['#title_display'] = 'before';
    }

    // Main JS options.
    $form['options']['settings'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => FALSE,
      '#title'       => $this->t('GridStack JS Settings'),
      '#description' => $this->t('This only affects GridStack JS, not Bootstrap/ Foundation.'),
      '#attributes'  => ['class' => ['form-wrapper--gridstack-settings']],
    ];

    $form['options']['settings']['auto'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Auto'),
      '#description' => $this->t("If unchecked, gridstack will not initialize existing items, means broken."),
    ];

    $form['options']['settings']['cellHeight'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Cell height'),
      '#description' => $this->t("One cell height. <strong>0</strong> means the library will not generate styles for rows. Everything must be defined in CSS files. <strong>auto (-1)</strong> means height will be calculated from cell width. Default 60. Be aware, auto has issues with responsive displays. Put <strong>-1</strong> if you want <strong>auto</strong> as this is an integer type."),
    ];

    $form['options']['settings']['float'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Float'),
      '#description' => $this->t("Enable floating widgets. See http://troolee.github.io/gridstack.js/demo/float.html. Default FALSE."),
    ];

    $form['options']['settings']['minWidth'] = [
      '#type'         => 'textfield',
      '#title'        => $this->t('Min width'),
      '#field_suffix' => 'px',
      '#description'  => $this->t('If window width is less, grid will be shown in one-column mode, with added class: <strong>gridstack--disabled</strong>. Recommended the same as or less than XS below, if provided. Default 768.'),
    ];

    $form['options']['settings']['width'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Amount of columns'),
      '#options'       => $this->getColumnOptions(),
      '#attributes'    => [
        'class'       => ['form-select--column'],
        'data-target' => '.gridstack--' . $icon_breakpoint,
      ],
      '#description'  => $this->t('The amount of columns. <strong>Important!</strong> This desktop column is overridden and ignored by LG below if provided.'),
    ];

    $form['options']['settings']['height'] = [
      '#type'         => 'textfield',
      '#title'        => $this->t('Maximum rows'),
      '#field_suffix' => 'px',
      '#description'  => $this->t("Maximum rows amount. Default is <strong>0</strong> which means no maximum rows."),
    ];

    $form['options']['settings']['rtl'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('RTL'),
      '#description' => $this->t("If <strong>true</strong> turns grid to RTL. Possible values are <strong>true</strong>, <strong>false</strong>, <strong>auto</strong> -- default. See http://troolee.github.io/gridstack.js/demo/rtl.html."),
    ];

    $form['options']['settings']['verticalMargin'] = [
      '#type'         => 'textfield',
      '#title'        => $this->t('Vertical margin'),
      '#field_suffix' => 'px',
      '#description'  => $this->t("Vertical gap size. Default 20."),
    ];

    $form['options']['settings']['noMargin'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('No horizontal margin'),
      '#description' => $this->t('If checked, be sure to put 0 for Vertical margin to avoid improper spaces.'),
    ];

    $form['options']['settings']['staticGrid'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Static grid'),
      '#description' => $this->t("Makes grid static, not movable, not resizable, at front end. You don't even need jQueryUI draggable/ resizable. A CSS class <strong>grid-stack-static</strong> is also added to the container."),
      '#prefix'      => '<h2 class="form__title">' . $this->t('jQuery UI related options. <small>It does not affect Admin preview. Manage global options <a href=":url" target="_blank">here</a>.</small>', [':url' => Url::fromRoute('gridstack.settings')->toString()]) . '</h2>',
    ];

    // Admin UI related options.
    $form['options']['settings']['draggable'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Draggable'),
      '#description' => $this->t('Allows to override jQuery UI draggable options. Uncheck this to have static grids at front end.'),
    ];

    $form['options']['settings']['resizable'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Resizable'),
      '#description' => $this->t('Allows to override jQuery UI resizable options. Uncheck this to have static grids at front end.'),
    ];

    $form['options']['settings']['disableDrag'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Disable drag'),
      '#description' => $this->t('Disallows dragging of widgets. Check this to have static grids at front end.'),
    ];

    $form['options']['settings']['disableResize'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Disable resize'),
      '#description' => $this->t('Disallows resizing of widgets. Check this to have static grids at front end.'),
    ];

    $form['options']['settings']['alwaysShowResizeHandle'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Show resize handle'),
      '#description' => $this->t('Uncheck this to have static grids at front end.'),
    ];

    $available_breakpoints = $entity::getConstantBreakpoints();

    // @todo remove temp fix for the weird broken xs grid.
    if ($framework_enabled) {
      array_unshift($available_breakpoints, 'xxs');
    }

    // Foundation only supports SM, MD, LG, not XL.
    if ($foundation || $framework == 'bootstrap3') {
      array_pop($available_breakpoints);
    }

    if ($is_default) {
      $available_breakpoints = ['lg'];
    }

    $definition['settings']    = $options;
    $definition['breakpoints'] = $available_breakpoints;
    $breakpoint_elements       = $this->blazyAdmin->breakpointElements($definition);

    $form['options']['breakpoints'] = [
      '#type'        => 'vertical_tabs',
      '#tree'        => TRUE,
      '#group'       => 'breakpoints',
      '#parents'     => ['breakpoints'],
      '#default_tab' => 'edit-options-breakpoints-' . $icon_breakpoint,
      '#prefix'      => '<h2 class="form__title form__title--responsive">' . $this->t('Responsive multi-breakpoint grids<small><span class="visible-js">XS is expected for disabled state defined by <strong>Min width</strong>.<br>Image styles will be forced uniformly, if provided. The column will be updated at the given breakpoint.</span><br>Be sure to follow the natural order keyed by index if trouble<span class="visible-js"> with multiple breakpoint image styles</span>. <br><strong>Note!</strong> If using static Bootstrap/ Foundation grid, this is a normal min-width, or mobile first layout.</small>') . '</h2>',
    ];

    $js_settings['use_framework'] = $entity->getOption('use_framework');
    $js_settings['is_framework']  = !empty($js_settings['use_framework']);
    $js_settings['_admin']        = TRUE;
    $js_settings['optionset']     = $entity->isNew() ? 'default' : $entity->id();

    foreach ($breakpoint_elements as $column => $elements) {
      $storage        = 'edit-options-breakpoints-' . $column . '-grids';
      $nested_storage = 'edit-options-breakpoints-' . $column . '-nested';
      $columns        = isset($options['breakpoints']) && isset($options['breakpoints'][$column]) ? $options['breakpoints'][$column] : [];
      $current_column = isset($columns['column']) ? $columns['column'] : 12;
      $current_width  = isset($columns['width']) ? $columns['width'] : '';

      $current_column === -1 ? 'auto' : $current_column;
      if ($framework_enabled) {
        $current_column = 12;
      }

      // Details.
      $form[$column]['#type']    = $is_default ? 'container' : 'details';
      $form[$column]['#tree']    = TRUE;
      $form[$column]['#open']    = TRUE;
      $form[$column]['#group']   = 'breakpoints';
      $form[$column]['#parents'] = ['options', 'breakpoints', $column];
      $form[$column]['#title']   = $column;

      // @todo temp fix remove.
      if ($column == 'xxs') {
        $form[$column]['#attributes']['class'][] = 'visually-hidden';
      }

      // Settings.
      $js_settings['breakpoint']       = $column;
      $js_settings['display']          = 'responsive';
      $js_settings['storage']          = $storage;
      $js_settings['nested_storage']   = $nested_storage;
      $js_settings['icon_breakpoint']  = $icon_breakpoint;
      $js_settings['breakpoint_width'] = $current_width;

      $entity->gridsJsonToArray($js_settings);

      // Fallback for totally empty before any string inserted.
      $main_grids        = $entity->getEndBreakpointGrids();
      $nested_grids      = $entity->getEndBreakpointGrids('nested');
      $column_grids_json = empty($columns['grids']) ? Json::encode($main_grids) : $columns['grids'];
      $column_grids      = is_string($column_grids_json) ? Json::decode($column_grids_json) : $column_grids_json;
      $nested_check      = array_filter($nested_grids);
      $nested_grids_end  = empty($nested_check) ? '' : Json::encode($nested_grids);
      $nested_grids_json = empty($columns['nested']) ? $nested_grids_end : $columns['nested'];

      // Fallback for not so empty when json grids deleted leaving to string.
      if (empty($column_grids)) {
        $column_grids_json = Json::encode($grids);
        $column_grids = $grids;
      }

      // Preview.
      $preview = [];
      if (in_array($column, ['xs']) && !$framework_enabled) {
        $lg = $this->t('<small>Grids and image styles are managed at the topmost display.</small>');
        $sm = $this->t('<small>Grids are in one column mode here.</small>');
        $small = $column == 'xs' ? $sm : $lg;

        $preview = [
          '#markup' => '<h3 class="form__title form__title--preview">' . $column . $small . '</h3>',
          '#allowed_tags' => ['h3', 'small'],
        ];
      }
      else {
        $preview['#theme'] = 'gridstack_ui_admin';
        $preview['#items'] = [];
        $preview['#settings'] = $js_settings;
        $preview['#weight'] = -10;
        $preview['#content_attributes'] = [
          'class' => [
            'gridstack--root',
            'gridstack--' . $column,
            $js_settings['icon_breakpoint'] == $js_settings['breakpoint'] ? 'gridstack--main' : 'gridstack--sub',
          ],
          'data-breakpoint'       => $column,
          'data-config'           => $data_config,
          'data-config-framework' => $data_config_framework,
          'data-framework'        => $framework_enabled ? 1 : 0,
          'data-index'            => 1,
          'data-preview-grids'    => $column_grids_json,
          'data-nested-grids'     => $nested_grids_json,
          'data-storage'          => $storage,
          'data-nested-storage'   => $nested_storage,
          'data-current-column'   => $current_column,
          'data-responsive-width' => $current_width,
        ];
      }

      // Build preview.
      $form[$column]['preview'] = $preview;

      // Breakpoint elements.
      foreach ($elements as $key => $element) {
        if ($key == 'breakpoint') {
          continue;
        }
        // Elements.
        $form[$column][$key] = $element;
        $form[$column][$key]['#title_display'] = 'before';

        $form[$column][$key]['#wrapper_attributes']['class'][] = 'form-item--' . str_replace('_', '-', $key);
        $form[$column]['breakpoint']['#wrapper_attributes']['class'][] = 'visually-hidden';

        // Overrides base.
        $form[$column]['width']['#weight'] = 10;
        $form[$column]['width']['#title'] = $this->t('@breakpoint width', ['@breakpoint' => $column]);
        $form[$column]['width']['#field_suffix'] = 'px';
        $form[$column]['width']['#attributes']['data-target'] = '.gridstack--' . $column;
        $form[$column]['width']['#description'] = $this->t('<ul><li>For GridStack JS, the minimum value must be <strong>&gt;= Min width</strong> above.</li><li>For static Bootstrap/Foundation, it only affects this very admin page for preview. The actual breakpoint is already defined by their CSS. Adjust it for correct preview. EM to PX conversion is based on 16px base font-size:<br>Bootstrap 3:<br><strong>SM >= 768px, MD >= 992px, LG >= 1200px</strong><br>Bootstrap 4: <br><strong>XS < 576px, SM >= 576px, MD >= 768px, LG >= 992px, XL >= 1200px</strong><br>Foundation:<br><strong>SM >= 0px, MD >= 641px (40.063em), LG >= 1025px (64.063em)</strong></li></ul>Leave it empty to disable/ ignore this breakpoint. <br><strong>Tips:</strong> Temporarily increase this to 768+ if trouble to expand shrinked boxes.');
        $form[$column]['image_style']['#description'] = $this->t('This will use uniform image style as a fallback if provided. To refine each image style for each box, use the image style within each box.');

        $form[$column]['image_style']['#weight'] = 10;
        $default_value = isset($columns[$key]) ? $columns[$key] : '';
        $form[$column][$key]['#default_value'] = $default_value;

        $form[$column]['column'] = [
          '#type'          => 'select',
          '#title'         => $this->t('Column'),
          '#options'       => $this->getColumnOptions(),
          '#empty_option'  => $this->t('- None -'),
          '#default_value' => $current_column,
          '#weight'        => 11,
          '#attributes'    => [
            'class' => ['form-select--column'],
            'data-target' => '.gridstack--' . $column,
          ],
          '#description'   => $this->t('The minimum column for this breakpoint. Try changing this if some grid/box is accidentally hidden to bring them back into the viewport. <br><strong>Important!</strong> This must be 12 if using static Bootstrap/ Foundation.'),
          '#wrapper_attributes' => ['class' => ['form-item--column']],
        ];

        if ($column == $icon_breakpoint) {
          $form[$column]['column']['#description'] .= ' ' . $this->t('Once provided, this will override the <strong>Amount of columns</strong> option above. Update the <strong>Amount of columns</strong> to match this new value if confused.');
        }

        // The actual grid elements which store the settings.
        $form[$column]['grids'] = [
          '#type'          => 'hidden',
          '#default_value' => $column_grids_json,
          '#weight'        => 20,
          '#wrapper_attributes' => ['class' => ['visually-hidden']],
        ];

        $form[$column]['nested'] = [
          '#type'          => 'hidden',
          '#default_value' => empty($columns['nested']) ? '' : $columns['nested'],
          '#weight'        => 20,
          '#wrapper_attributes' => ['class' => ['visually-hidden']],
        ];

        if ($is_default) {
          $form[$column][$key]['#wrapper_attributes'] = ['class' => ['visually-hidden']];
          $form[$column][$key]['#description'] = '';
          $form[$column]['column']['#type'] = 'hidden';
          $form[$column]['column']['#default_value'] = 12;
          $form[$column]['width']['#default_value'] = 1200;
        }
      }
    }

    $excludes = ['container', 'details', 'item', 'hidden', 'submit'];
    foreach ($default->getOptions('settings') as $name => $value) {
      if (!isset($form['options']['settings'][$name])) {
        continue;
      }

      if (in_array($form['options']['settings'][$name]['#type'], $excludes) && !isset($form['options']['settings'][$name])) {
        continue;
      }
      if ($admin_css) {
        if ($form['options']['settings'][$name]['#type'] == 'checkbox') {
          $form['options']['settings'][$name]['#field_suffix'] = '&nbsp;';
          $form['options']['settings'][$name]['#title_display'] = 'before';
        }
      }
      if (!isset($form['options']['settings'][$name]['#default_value'])) {
        $form['options']['settings'][$name]['#default_value'] = isset($settings[$name]) ? $settings[$name] : $value;
      }
    }
    return $form;
  }

  /**
   * Returns the supported columns.
   */
  public function getColumnOptions() {
    $range = range(1, 12);
    return array_combine($range, $range);
  }

  /**
   * Convert the config into a JSON object to reduce logic at frontend.
   */
  public function jsonify($options = [], $preview = FALSE) {
    if (empty($options)) {
      return '';
    }

    $json       = [];
    $default    = GridStack::load('default')->getOptions('settings');
    $cellHeight = $options['cellHeight'];
    $excludes   = [
      'disableDrag',
      'disableResize',
      'draggable',
      'resizable',
      'staticGrid',
    ];

    if (!empty($options)) {
      foreach ($options as $name => $value) {
        if (isset($options[$name]) && !is_array($options[$name])) {
          if (isset($options['noMargin'])) {
            unset($options['noMargin']);
          }

          if (isset($options['width']) && $options['width'] == 12) {
            unset($options['width']);
          }

          if (!in_array($name, ['cellHeight', 'rtl']) && isset($default[$name])) {
            $cast = gettype($default[$name]);
            settype($options[$name], $cast);
          }

          $json[$name] = $options[$name];

          $json['cellHeight'] = ($cellHeight == -1) ? 'auto' : (int) $cellHeight;

          if (empty($options['rtl'])) {
            unset($json['rtl']);
          }
        }

        // Be sure frontend options do not break admin preview.
        if ($preview && in_array($name, $excludes)) {
          unset($json[$name]);
        }
      }
    }

    return Json::encode($json);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Grids contain the current grid node and probably nested grids.
    $framework = $form_state->getValue(['options', 'use_framework']);

    // Remove old options.grids for options.breakpoints.lg.grids, etc.
    // @todo: Remove BC.
    if ($form_state->hasValue(['options', 'grids'])) {
      $form_state->unsetValue(['options', 'grids']);
    }

    if (!$form_state->hasValue(['json', 'grids', 'nested'])) {
      $form_state->unsetValue(['json', 'grids', 'nested']);
    }

    // Columns.
    $settings = $form_state->getValue(['options', 'settings']);
    $options_breakpoints = $form_state->getValue(['options', 'breakpoints']);

    // Validate breakpoint form.
    if (!empty($options_breakpoints)) {
      $this->validateBreakpointForm($form, $form_state);
    }

    // Remove JS settings for static grid layout like Bootstrap/ Foundation.
    if (!empty($framework)) {
      $settings = [];
      $form_state->setValue(['options', 'settings'], []);
    }

    // Map settings into JSON.
    $form_state->setValue(['json', 'settings'], empty($settings) ? '' : $this->jsonify($settings));

    // JS only breakpoints.
    // Only reasonable for GridStack, not Bootstrap, or other static grid.
    // JSON breakpoints to reduce frontend logic for responsive JS.
    $json_breakpoints = [];
    if (!empty($options_breakpoints)) {
      foreach ($options_breakpoints as $breakpoints) {
        foreach ($breakpoints as $k => $value) {
          if (empty($breakpoints['width'])) {
            continue;
          }

          // Respect 0 value for future mobile first when Blazy supports it.
          if ($k != 'image_style' && !empty($breakpoints['column'])) {
            $json_breakpoints[$breakpoints['width']] = empty($framework) ? (int) $breakpoints['column'] : 12;
          }
        }
      }
    }

    // Append the desktop version as well to reduce JS logic.
    $form_state->setValue(['json', 'breakpoints'], empty($json_breakpoints) ? '' : Json::encode($json_breakpoints));

    // Remove unused settings.
    $form_state->unsetValue(['template', 'image_style']);

    // Build icon.
    if ($form_state->hasValue(['options', 'icon'])) {
      $id = $form_state->getValue('name');
      $icon = $form_state->getValue(['options', 'icon']);

      if (strpos($icon, 'data:image') !== FALSE) {
        $destination = 'public://gridstack';
        $paths['id'] = $id;
        $paths['target'] = $destination . '/';

        // Compatibility for 8.7+.
        if (method_exists($this->fileSystem, 'prepareDirectory')) {
          $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
        }
        elseif (function_exists('file_prepare_directory')) {
          file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
        }

        $this->saveImage($icon, $paths);

        // Update data URI into file URI.
        if (!empty($paths['uri'])) {
          if (strpos($paths['uri'], 'data:,') !== FALSE) {
            $paths['uri'] = '';
          }

          $form_state->setValue(['options', 'icon'], $paths['uri']);
        }
      }
    }
  }

  /**
   * Validate breakpoint form.
   */
  protected function validateBreakpointForm(array &$form, FormStateInterface &$form_state) {
    $options_breakpoints = $form_state->getValue(['options', 'breakpoints']);
    $framework = $form_state->getValue(['options', 'use_framework']);

    foreach ($options_breakpoints as $key => $breakpoints) {
      if ($key == 'xxs') {
        $form_state->unsetValue($key);
      }
      foreach ($breakpoints as $k => $value) {
        // Static grids only expect 12 columns, not dynamic ones.
        if (!empty($framework)) {
          $breakpoints['column'] = 12;

          if ($k == 'column') {
            $value = 12;
          }
        }

        // Respect 0 value for future mobile first when Blazy supports it.
        if (!empty($breakpoints['column'])) {
          $form_state->setValue(['options', 'breakpoints', $key, $k], $value);
        }

        if (!empty($framework)) {
          $image_style = ['options', 'breakpoints', $key, 'image_style'];
          $form_state->unsetValue($image_style);
        }

        // Remove breakpoint grids if no width provided.
        if (empty($breakpoints['width'])) {
          $form_state->unsetValue(['options', 'breakpoints', $key]);
        }
      }

      // Clean out stuffs, either stored somewhere else, or no use.
      $nested = $form_state->getValue([
        'options',
        'breakpoints',
        $key,
        'nested',
      ]);

      $nested = Json::decode($nested);
      $nested = empty($nested) ? '' : array_filter($nested);

      $grids = $form_state->getValue([
        'options',
        'breakpoints',
        $key,
        'grids',
      ]);

      $grids = Json::decode($grids);
      $grids = empty($grids) ? '' : array_filter($grids);

      if (empty($nested) || empty($grids)) {
        $form_state->unsetValue(['options', 'breakpoints', $key, 'nested']);
      }

      $form_state->unsetValue(['options', 'breakpoints', $key, 'breakpoint']);
    }
  }

  /**
   * Saves the icon based on the current grid display.
   *
   * Taken and simplified from color.module _color_render_images(), and s.o.
   */
  public function saveImage($data, &$paths) {
    if (empty($data) || strpos($data, ',') === FALSE) {
      return;
    }

    $name = $paths['id'] . '.png';
    $uri = $paths['target'] . $name;
    $url = file_create_url($uri);
    $real_path = $this->fileSystem->realpath($uri);

    // Remove "data:image/png;base64," part.
    $file_data = substr($data, strpos($data, ',') + 1);
    $file_contents = base64_decode($file_data);

    if (empty($file_contents)) {
      return;
    }

    $image = imagecreatefromstring($file_contents);

    // Gets dimensions.
    $width = imagesx($image);
    $height = imagesy($image);

    // Prepare target buffer.
    $target = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($target, 255, 255, 255);
    imagefilledrectangle($target, 0, 0, $width, $height, $white);
    imagecopy($target, $image, 0, 0, 0, 0, $width, $height);
    imagealphablending($target, TRUE);
    imagepng($target, $real_path);

    // Clean up target buffer.
    imagedestroy($target);

    // Store image.
    $paths['uri'] = $uri;
    $paths['url'] = file_url_transform_relative($url);

    // Compatibility for 8.7+.
    if (method_exists($this->fileSystem, 'saveData')) {
      $this->fileSystem->saveData($file_contents, $uri, FileSystemInterface::EXISTS_REPLACE);
    }
    elseif (function_exists('file_unmanaged_save_data')) {
      file_unmanaged_save_data($file_contents, $uri, FILE_EXISTS_REPLACE);
    }

    // Set standard file permissions for webserver-generated files.
    $this->fileSystem->chmod($real_path);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $entity = $this->entity;

    // Prevent leading and trailing spaces in gridstack names.
    $entity->set('label', trim($entity->label()));
    $entity->set('id', $entity->id());

    $enable = $entity->id() == 'default' ? FALSE : TRUE;
    $entity->setStatus($enable);

    $status        = $entity->save();
    $label         = $entity->label();
    $edit_link     = $entity->toLink($this->t('Edit'), 'edit-form')->toString();
    $config_prefix = $entity->getEntityType()->getConfigPrefix();
    $message       = ['@config_prefix' => $config_prefix, '%label' => $label];

    $notice = [
      '@config_prefix' => $config_prefix,
      '%label' => $label,
      'link' => $edit_link,
    ];

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      // @todo #2278383.
      $this->messenger->addMessage($this->t('@config_prefix %label has been updated.', $message));
      $this->logger('gridstack')->notice('@config_prefix %label has been updated.', $notice);
    }
    else {
      // If we created a new entity.
      $this->messenger->addMessage($this->t('@config_prefix %label has been added.', $message));
      $this->logger('gridstack')->notice('@config_prefix %label has been added.', $notice);
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
