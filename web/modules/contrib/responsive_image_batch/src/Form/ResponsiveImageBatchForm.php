<?php

namespace Drupal\responsive_image_batch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\image\Plugin\ImageEffect\ResizeImageEffect;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures settings for this site.
 */
class ResponsiveImageBatchForm extends FormBase {

  /**
   * The breakpoint manager.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface
   */
  protected $breakpointManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * The component label.
   *
   * @var string
   */
  protected $componentLabel;

  /**
   * The component ID.
   *
   * @var string
   */
  protected $componentId;

  /**
   * The base image style.
   *
   * @var string
   */
  protected $baseImageStyle;

  /**
   * Indicates whether width is a required value.
   *
   * @var bool
   */
  protected $widthRequired;

  /**
   * Indicates whether height is a required value.
   *
   * @var bool
   */
  protected $heightRequired;

  /**
   * The responsive image type.
   *
   * @var string
   */
  protected $responsiveImageType;

  /**
   * The breakpoint group.
   *
   * @var array
   */
  protected $breakpointGroup;

  /**
   * The breakpoints.
   *
   * @var array
   */
  protected $breakpoints;

  /**
   * The image style data to construct the matrix table.
   *
   * @var array
   */
  protected $imageStyleData;

  /**
   * The available fallback image styles.
   *
   * @var array
   */
  protected $fallbackImageStyles;

  /**
   * The active fieldset. Either NULL, picture, or sizes.
   *
   * @var string
   */
  protected $activeFieldset;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('breakpoint.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs the responsive image style form.
   *
   * @param \Drupal\breakpoint\BreakpointManagerInterface $breakpoint_manager
   *   The breakpoint manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
      BreakpointManagerInterface $breakpoint_manager,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->breakpointManager = $breakpoint_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_image_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @TODO in the future all these settings can be managed maybe using config entities to edit styles.
    $form = [
      '#tree' => TRUE,
      '#attributes' => [
        'id' => 'responsive-image-batch-wrapper',
      ],
      '#attached' => [
        'library' => ['responsive_image_batch/responsive_image_batch.admin'],
      ],
    ];

    // Component label.
    $form['component_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Component label'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t('Example: "Article teaser" or "Author image". Used to prefix image style names and to name the responsive image style.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::breakpointMappingFormAjax',
        'wrapper' => 'responsive-image-batch-wrapper',
        'disable-refocus' => TRUE,
      ],
    ];
    $form['component_id'] = [
      '#type' => 'machine_name',
      '#default_value' => '',
      '#machine_name' => [
        'exists' => '\Drupal\responsive_image\Entity\ResponsiveImageStyle::load',
        'source' => ['component_label'],
      ],
    ];
    $this->componentLabel = $form_state->getValue(['component_label']);
    $this->componentId = preg_replace('@[^a-z0-9]+@', '-', strtolower($this->componentLabel));

    // Base image style.
    // @todo: which is better: 'Base image style', or 'Clone image style'?
    $form['base_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Base image style'),
      '#options' => image_style_options(FALSE),
      '#empty_option' => $this->t('Select an image style'),
      '#required' => TRUE,
      '#description' => $this->t('Select the image style you would like to use as a base for the responsive image styles. All effects on this image style will be cloned. Width and height will be overridden of effects that define dimensions.'),
      '#ajax' => [
        'callback' => '::breakpointMappingFormAjax',
        'wrapper' => 'responsive-image-batch-wrapper',
      ],
    ];
    $this->baseImageStyle = $form_state->getValue(['base_image_style']);

    // Effects table.
    if (!empty($this->baseImageStyle)) {
      $this->setRequiredDimensions($form_state);
      // Create image effects summary table.
      $form['effects'] = [
        '#type' => 'table',
        '#header' => [$this->t('Effects')],
      ];
      foreach ($this->getEffects() as $key => $effect) {
        $form['effects'][$key]['#weight'] = $effect->getWeight();
        $form['effects'][$key]['effect'] = [
          '#tree' => FALSE,
          'data' => [
            'label' => [
              '#plain_text' => $effect->label(),
            ],
          ],
        ];
        if ($summary = $effect->getSummary()) {
          $summary['#prefix'] = ' ';
          $form['effects'][$key]['effect']['data']['summary'] = $summary;
        }
        if ($effect instanceof ResizeImageEffect) {
          $form['effects'][$key]['effect']['data']['#prefix'] = '<strong>';
          $form['effects'][$key]['effect']['data']['#suffix'] = '</strong>';
        }
      }
      $form['effects_description'] = [
        '#plain_text' => $this->t('Dimension values of effects listed in bold will be overridden.'),
        '#prefix' => '<div class="description">',
        '#suffix' => '</div>',
      ];
    }

    // Responsive image type.
    $form['responsive_image_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive image type'),
      '#default_value' => '',
      '#options' => [
        'picture' => $this->t('Picture'),
        'sizes' => $this->t('Sizes'),
      ],
      '#empty_option' => $this->t('Select a responsive image type'),
      '#required' => TRUE,
      '#description' => $this->t('Select a responsive image type. Select Picture if you want full control over the art direction in every breakpoint. Select Sizes to generate an array of image styles of which the browser chooses the best for the image context.'),
      '#ajax' => [
        'callback' => '::breakpointMappingFormAjax',
        'wrapper' => 'responsive-image-batch-wrapper',
      ],
    ];
    $this->responsiveImageType = $form_state->getValue(['responsive_image_type']);

    // Breakpoint group.
    if ($this->responsiveImageType == 'picture') {
      $form['breakpoint_group'] = [
        '#type' => 'select',
        '#title' => $this->t('Breakpoint group'),
        '#default_value' => '',
        '#options' => $this->breakpointManager->getGroups(),
        '#empty_option' => $this->t('Select a breakpoint group'),
        '#required' => TRUE,
        '#description' => $this->t('Select a breakpoint group from the installed themes and modules.'),
        '#ajax' => [
          'callback' => '::breakpointMappingFormAjax',
          'wrapper' => 'responsive-image-batch-wrapper',
        ],
      ];
      $this->breakpointGroup = $form_state->getValue(['breakpoint_group']);
      $this->breakpoints = $this->breakpointManager->getBreakpointsByGroup($this->breakpointGroup);
    }

    // Fieldset wrapper for table(s).
    $form['image_styles'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => ['responsive-image-batch__fieldset'],
      ],
    ];

    // Set active fieldset type: NULL, picture, or sizes.
    $this->activeFieldset = NULL;
    if ($this->responsiveImageType == 'picture' &&
      !empty($this->componentLabel) &&
      !empty($this->baseImageStyle) &&
      !empty($this->breakpointGroup)) {

      $this->activeFieldset = 'picture';
    }
    elseif ($this->responsiveImageType == 'sizes' &&
      !empty($this->componentLabel) &&
      !empty($this->baseImageStyle)) {

      $this->activeFieldset = 'sizes';
    }

    // Build the form array for the tables.
    if ($this->activeFieldset == 'picture') {
      $form['image_styles']['#attributes']['class'][] = 'responsive-image-batch__fieldset--picture';
      $this->buildPictureTable($form, $form_state);
    }
    elseif ($this->activeFieldset == 'sizes') {
      $form['image_styles']['#attributes']['class'][] = 'responsive-image-batch__fieldset--sizes';
      $form['image_styles']['#attributes']['data-component-id'] = $this->componentId;
      $form['image_styles']['#attributes']['data-component-label'] = $this->componentLabel;
      $this->buildSizesTable($form, $form_state);
    }
    else {
      // Hide fieldset.
      $form['image_styles']['#attributes']['class'][] = 'js-hide';
    }

    // Fallback image style.
    if (!empty($this->fallbackImageStyles) || $this->activeFieldset == 'sizes') {
      $fallback_image_styles = $this->fallbackImageStyles;
      $fallback_image_styles[RESPONSIVE_IMAGE_ORIGINAL_IMAGE] = $this->t('- None (original image) -');
      $fallback_image_styles[RESPONSIVE_IMAGE_EMPTY_IMAGE] = $this->t('- empty image -');
      $form['image_styles']['fallback_image_style'] = [
        '#type' => 'select',
        '#title' => $this->t('Fallback image style'),
        '#options' => $fallback_image_styles,
        '#empty_option' => $this->t('- None -'),
        '#required' => TRUE,
        '#validated' => TRUE,
        '#description' => $this->t('Select the smallest image style you expect to appear in this space. The fallback image style should only appear on the site if an error occurs.'),
        '#attributes' => [
          'class' => ['responsive-image-batch__fallback-image-style'],
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Generate image styles'),
        '#attributes' => [
          'class' => ['button--primary'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Builds the table(s) containing Picture image style configurations.
   *
   * @param array $form
   *   The form array.
   * @param object $form_state
   *   The form state object.
   */
  public function buildPictureTable(&$form, &$form_state) {
    // Image styles grouped by breakpoint.
    $this->resetData();
    $this->setPictureData();

    // Build tables.
    foreach ($this->imageStyleData as $multiplier => $multiplier_styles) {
      $input_table = FALSE;
      if ($multiplier == 1) {
        $input_table = TRUE;
      }
      $table_id = $this->breakpointGroup . '-' . $multiplier;
      // Header row.
      $width_header = ['data' => $this->t('Width')];
      if ($this->widthRequired && $input_table) {
        $width_header['class'] = ['form-required'];
      }
      $height_header = ['data' => $this->t('Height')];
      if ($this->heightRequired && $input_table) {
        $height_header['class'] = ['form-required'];
      }
      // Image style table.
      $form['image_styles'][$table_id] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Image styles: @multiplierx', ['@multiplier' => $multiplier]),
          $width_header,
          $height_header,
          $this->t('Aspect ratio'),
          $this->t('Exclude'),
        ],
        '#empty' => $this->t('No image styles to configure.'),
        '#attributes' => [
          'data-multiplier' => $multiplier,
          'class' => ['responsive-image-batch__picture-image-styles'],
        ],
      ];
      // Table rows and columns.
      foreach ($multiplier_styles as $image_style_id => $image_style) {
        // Excluded?
        $excluded = $form_state->getValue([
          'image_styles',
          $this->getFirstTableId(),
          $image_style_id,
          'exclude',
          'value',
        ]);
        // Add image style name to generated image styles list if not excluded.
        if (!$excluded) {
          $this->fallbackImageStyles[$image_style['name']] = $image_style['label'];
        }
        // Image style row.
        $form['image_styles'][$table_id][$image_style_id]['#attributes'] = [
          'data-breakpoint-id' => $image_style['breakpoint_id'],
          'data-multiplier' => $multiplier,
        ];
        // Data values.
        $form['image_styles'][$table_id][$image_style_id]['data'] = [
          '#type' => 'value',
          '#value' => [
            'image_style_name' => $image_style['name'],
            'image_style_label' => $image_style['label'],
            'breakpoint_id' => $image_style['breakpoint_id'],
            'multiplier_name' => $image_style['multiplier_name'],
          ],
        ];
        // Image style label.
        $form['image_styles'][$table_id][$image_style_id]['data']['label'] = [
          '#plain_text' => $image_style['label'],
        ];
        // Help icon showing media query in a tooltip on hover.
        if (!empty($image_style['media_query']) && $input_table) {
          $form['image_styles'][$table_id][$image_style_id]['data']['tooltip'] = [
            '#markup' => '<span class="responsive_image_batch__tooltip-icon module-link-help" data-tooltip="' . $image_style['media_query'] . '" title="value!"></span>',
          ];
        }
        // Image style width.
        if ($input_table) {
          $form['image_styles'][$table_id][$image_style_id]['width']['value'] = [
            '#type' => 'number',
            '#title' => $this->t('Width'),
            '#title_display' => 'invisible',
            '#size' => 10,
            '#step' => 1,
            '#min' => 0,
            '#disabled' => $excluded,
            '#required' => !$excluded && $this->widthRequired,
            '#default_value' => !$excluded ? $form_state->getValue([
              'image_styles',
              $table_id,
              $image_style_id,
              'width',
              'value',
            ]) : '',
            '#attributes' => ['data-dimension' => 'width'],
          ];
        }
        else {
          $caluculated_dimension = ceil($form_state->getValue([
            'image_styles',
            $this->getFirstTableId(),
            $image_style_id,
            'width',
            'value',
          ]) * $multiplier);
          if ($caluculated_dimension == 0) {
            $caluculated_dimension = '';
          }
          // Hidden field.
          $form['image_styles'][$table_id][$image_style_id]['width']['value'] = [
            '#type' => 'hidden',
            '#default_value' => $caluculated_dimension,
            '#attributes' => [
              'class' => ['responsive-image-batch__width-hidden'],
            ],
          ];
          // Markup.
          $form['image_styles'][$table_id][$image_style_id]['width']['markup'] = [
            '#markup' => !$excluded ? $caluculated_dimension : '',
            '#prefix' => '<span class="responsive-image-batch__width">',
            '#suffix' => '</span>',
          ];
        }
        // Image style height.
        if ($input_table) {
          $form['image_styles'][$table_id][$image_style_id]['height']['value'] = [
            '#type' => 'number',
            '#title' => $this->t('Height'),
            '#title_display' => 'invisible',
            '#size' => 10,
            '#step' => 1,
            '#min' => 0,
            '#disabled' => $excluded,
            '#required' => !$excluded && $this->heightRequired,
            '#default_value' => !$excluded ? $form_state->getValue([
              'image_styles',
              $table_id,
              $image_style_id,
              'height',
              'value',
            ]) : '',
            '#attributes' => ['data-dimension' => 'height'],
          ];
        }
        else {
          $caluculated_dimension = ceil($form_state->getValue([
            'image_styles',
            $this->getFirstTableId(),
            $image_style_id,
            'height',
            'value',
          ]) * $multiplier);
          if ($caluculated_dimension == 0) {
            $caluculated_dimension = '';
          }
          // Hidden field.
          $form['image_styles'][$table_id][$image_style_id]['height']['value'] = [
            '#type' => 'hidden',
            '#default_value' => $caluculated_dimension,
            '#attributes' => [
              'class' => ['responsive-image-batch__height-hidden'],
            ],
          ];
          // Markup.
          $form['image_styles'][$table_id][$image_style_id]['height']['markup'] = [
            '#markup' => !$excluded ? $caluculated_dimension : '',
            '#prefix' => '<span class="responsive-image-batch__height">',
            '#suffix' => '</span>',
          ];
        }
        // Aspect ratio.
        if ($input_table) {
          $form['image_styles'][$table_id][$image_style_id]['aspect_ratio'] = [
            '#type' => 'select',
            '#title' => $this->t('Aspect ratio'),
            '#title_display' => 'invisible',
            '#disabled' => $excluded,
            '#options' => $this->aspectRatioOptions(),
            '#empty_option' => $this->t('- None -'),
          ];
        }
        else {
          $aspect_ratio_options = $this->aspectRatioOptions();
          $aspect_ratio_value = $form_state->getValue([
            'image_styles',
            $this->getFirstTableId(),
            $image_style_id,
            'aspect_ratio',
          ]);
          $aspect_ratio = '';
          if (isset($aspect_ratio_options[$aspect_ratio_value])) {
            $aspect_ratio = $aspect_ratio_options[$aspect_ratio_value];
          }
          $form['image_styles'][$table_id][$image_style_id]['aspect_ratio'] = [
            '#markup' => !$excluded ? $aspect_ratio : '',
            '#prefix' => '<span class="responsive-image-batch__aspect-ratio">',
            '#suffix' => '</span>',
          ];
        }
        // Exclude style per breakpoint.
        if ($input_table) {
          $form['image_styles'][$table_id][$image_style_id]['exclude']['value'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Exclude'),
            '#title_display' => 'invisible',
            '#ajax' => [
              'callback' => '::breakpointMappingFormAjax',
              'wrapper' => 'responsive-image-batch-wrapper',
              'progress' => ['message' => NULL],
            ],
          ];
        }
        else {
          // Hidden field.
          $form['image_styles'][$table_id][$image_style_id]['exclude']['value'] = [
            '#type' => 'hidden',
            '#value' => $excluded,
          ];
          // Markup.
          $form['image_styles'][$table_id][$image_style_id]['exclude']['markup'] = [
            '#markup' => $excluded ? $this->t('Yes') : $this->t('No'),
            '#prefix' => '<span class="responsive-image-batch__exclude">',
            '#suffix' => '</span>',
          ];
        }
      }
    }
  }

  /**
   * Builds the image style configurations for the Sizes type.
   *
   * @param array $form
   *   The form array.
   * @param object $form_state
   *   The form state object.
   */
  public function buildSizesTable(&$form, &$form_state) {
    $this->resetData();

    // Dimensions container.
    $form['image_styles']['dimensions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['responsive-image-batch__sizes-container'],
      ],
    ];

    // Width.
    $form['image_styles']['dimensions']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#size' => 10,
      '#step' => 1,
      '#min' => 0,
      '#required' => TRUE,
      '#default_value' => $form_state->getValue([
        'image_styles',
        'dimensions',
        'width',
      ]),
      '#attributes' => [
        'class' => ['responsive-image-batch__width'],
        'data-dimension' => 'width',
      ],
    ];

    // Height.
    $form['image_styles']['dimensions']['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#size' => 10,
      '#step' => 1,
      '#min' => 0,
      '#required' => $this->heightRequired,
      '#default_value' => $form_state->getValue([
        'image_styles',
        'dimensions',
        'height',
      ]),
      '#attributes' => [
        'class' => ['responsive-image-batch__height'],
        'data-dimension' => 'height',
      ],
    ];

    // Aspect ratio.
    $form['image_styles']['dimensions']['aspect_ratio'] = [
      '#type' => 'select',
      '#title' => $this->t('Aspect ratio'),
      '#options' => $this->aspectRatioOptions(),
      '#empty_option' => $this->t('- None -'),
      '#attributes' => [
        'class' => ['responsive-image-batch__aspect-ratio'],
      ],
    ];

    // Increment container.
    $form['image_styles']['increment'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'responsive-image-batch__sizes-container',
        ],
      ],
    ];

    // Increment value.
    $form['image_styles']['increment']['increment_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Increment value'),
      '#size' => 10,
      '#step' => 1,
      '#min' => 0,
      '#default_value' => $form_state->getValue([
        'image_styles',
        'increment',
        'increment_value',
      ]),
      '#attributes' => [
        'class' => ['responsive-image-batch__increment-value'],
      ],
    ];

    // Increment type.
    $form['image_styles']['increment']['increment_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Increment type'),
      '#options' => [
        'percent' => '%',
        'px' => $this->t('px'),
      ],
      '#default_value' => $form_state->getValue([
        'image_styles',
        'increment',
        'increment_type',
      ]),
      '#attributes' => [
        'class' => ['responsive-image-batch__increment-type'],
      ],
    ];

    // Total increments.
    $form['image_styles']['increment']['total_increments'] = [
      '#type' => 'number',
      '#title' => $this->t('Total increments'),
      '#size' => 10,
      '#step' => 1,
      '#min' => 0,
      '#default_value' => $form_state->getValue([
        'image_styles',
        'increment',
        'total_increments',
      ]),
      '#attributes' => [
        'class' => ['responsive-image-batch__total-increments'],
      ],
    ];

    // Round up.
    $form['image_styles']['increment']['round_up'] = [
      '#type' => 'select',
      '#title' => $this->t('Round up'),
      '#options' => [
        5 => $this->t('5px'),
        10 => $this->t('10px'),
        20 => $this->t('20px'),
        25 => $this->t('25px'),
        50 => $this->t('50px'),
        100 => $this->t('100px'),
      ],
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $form_state->getValue([
        'image_styles',
        'increment',
        'round_up',
      ]),
      '#attributes' => [
        'class' => ['responsive-image-batch__round-up'],
      ],
    ];

    // Image style table.
    $form['image_styles']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Image styles'),
        $this->t('Increase'),
        $this->t('Width'),
        $this->t('Height'),
      ],
      '#empty' => $this->t('No image styles to generate.'),
      '#attributes' => [
        'class' => ['responsive-image-batch__sizes-image-styles'],
      ],
    ];

    // Generate image style data.
    $image_styles = $this->generateSizesImageStyles($form, $form_state);

    // Image style table rows.
    foreach ($image_styles as $image_style_id => $image_style) {
      // Add image style name to generated image styles list.
      $this->fallbackImageStyles[$image_style['name']] = $image_style['label'];
      // Data values.
      $form['image_styles']['table'][$image_style_id]['data'] = [
        '#type' => 'value',
        '#value' => [
          'image_style_name' => $image_style['name'],
          'image_style_label' => $image_style['label'],
        ],
      ];

      // Image style label.
      $form['image_styles']['table'][$image_style_id]['data']['label'] = [
        '#plain_text' => $image_style['label'],
      ];

      // Image style increase.
      $form['image_styles']['table'][$image_style_id]['increase'] = [
        '#plain_text' => $image_style['increase'],
      ];

      // Image style width.
      $form['image_styles']['table'][$image_style_id]['width'] = [
        '#plain_text' => $image_style['width'],
      ];

      // Image style height.
      $form['image_styles']['table'][$image_style_id]['height'] = [
        '#plain_text' => $image_style['height'],
      ];
    }

    // Sizes.
    $sizes = $form_state->getValue(['image_styles', 'sizes']);
    $form['image_styles']['sizes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sizes'),
      '#default_value' => !empty($sizes) ? $sizes : '100vw',
      '#description' => $this->t('Enter the value for the sizes attribute, for example: %example_sizes.', [
        '%example_sizes' => '(min-width:700px) 700px, 100vw',
      ]),
      '#required' => TRUE,
    ];
  }

  /**
   * Returns the first Picture table ID.
   *
   * @return string
   *   first Picture table ID.
   */
  public function getFirstTableId() {
    return $this->breakpointGroup . '-1';
  }

  /**
   * Retreives base image style effects as objects.
   *
   * @return array
   *   returns and array of image effect objects.
   */
  public function getEffects() {
    if (empty($this->baseImageStyle)) {
      return [];
    }
    return $this->entityTypeManager
      ->getStorage('image_style')
      ->load($this->baseImageStyle)
      ->getEffects();
  }

  /**
   * Retreives base image style effects as and array.
   *
   * @return array
   *   returns and array of image effect arrays.
   */
  public function getEffectsArray() {
    if (empty($this->baseImageStyle)) {
      return [];
    }
    return $this->entityTypeManager
      ->getStorage('image_style')
      ->load($this->baseImageStyle)
      ->get('effects');
  }

  /**
   * Finds and stores whether width and/or height are required fields.
   *
   * @param object $form_state
   *   The form state object.
   */
  public function setRequiredDimensions($form_state) {
    $effects = $this->getEffects();
    if (empty($effects)) {
      return;
    }
    $this->widthRequired = FALSE;
    $this->heightRequired = FALSE;
    // Check if width and height are required fields.
    foreach ($effects as $effect) {
      if (method_exists($effect, 'buildConfigurationForm')) {
        // @TODO: pass in a fresh form state object instead?
        $effect_form = $effect->buildConfigurationForm([], $form_state);
        if (isset($effect_form['width']['#required']) && $effect_form['width']['#required'] == TRUE) {
          $this->widthRequired = TRUE;
        }
        if (isset($effect_form['height']['#required']) && $effect_form['width']['#required'] == TRUE) {
          $this->heightRequired = TRUE;
        }
      }
    }
  }

  /**
   * Reset generated data arrays.
   */
  public function resetData() {
    $this->imageStyleData = [];
    $this->fallbackImageStyles = [];
  }

  /**
   * Prepare image style data used to render Picture image style tables.
   */
  public function setPictureData() {
    if (empty($this->breakpoints) || empty($this->componentId)) {
      return;
    }
    $multipliers_sort = [];
    $image_styles_unsorted = [];
    foreach ($this->breakpoints as $breakpoint_id => $breakpoint) {
      foreach ($breakpoint->getMultipliers() as $multiplier_name) {
        $multiplier = preg_replace('/[^0-9.]/', '', $multiplier_name);
        $multipliers_sort[$multiplier] = $multiplier;
        $multiplier_label = '';
        if ($multiplier != '1') {
          $multiplier_label = '-' . $multiplier_name;
        }
        $image_style_label = $this->createPictureImageStyleLabel($breakpoint_id, $multiplier_label);
        $image_style_name = $this->createImageStyleName($image_style_label);
        $image_styles_unsorted[$multiplier][] = [
          'label' => $image_style_label,
          'name' => $image_style_name,
          'weight' => $breakpoint->getWeight(),
          'multiplier_name' => $multiplier_name,
          'breakpoint_id' => $breakpoint_id,
          'media_query' => $breakpoint->getMediaQuery(),
          'width' => 0,
          'height' => 0,
        ];
      }
    }

    // Sort by multipliers.
    sort($multipliers_sort);
    foreach ($multipliers_sort as $multiplier) {
      $this->imageStyleData[$multiplier] = $image_styles_unsorted[$multiplier];
    }
  }

  /**
   * Generate Sizes Image styles based on available form values.
   *
   * @param array $form
   *   Form array.
   * @param object $form_state
   *   Form state object.
   *
   * @return array
   *   An array containing all generated image styles.
   */
  public function generateSizesImageStyles($form, $form_state) {
    $width = $form_state->getValue([
      'image_styles',
      'dimensions',
      'width',
    ]);
    $height = $form_state->getValue([
      'image_styles',
      'dimensions',
      'height',
    ]);
    $increment_value = $form_state->getValue([
      'image_styles',
      'increment',
      'increment_value',
    ]);
    $increment_type = $form_state->getValue([
      'image_styles',
      'increment',
      'increment_type',
    ]);
    $total_increments = $form_state->getValue([
      'image_styles',
      'increment',
      'total_increments',
    ]);
    $round_up = $form_state->getValue([
      'image_styles',
      'increment',
      'round_up',
    ]);
    $aspect_ratio = NULL;
    $image_styles = [];

    if (empty($width)) {
      return $image_styles;
    }

    if (!empty($height)) {
      $aspect_ratio = ($height / $width) * 100;
    }

    $image_style_label = $this->createSizesImageStyleLabel($width);
    $image_style_name = $this->createImageStyleName($image_style_label);

    // First image style.
    $image_styles[] = [
      'name' => $image_style_name,
      'label' => $image_style_label,
      'increase' => '',
      'width' => $width,
      'height' => $height,
    ];

    if (empty($increment_value) || empty($increment_type) || empty($total_increments)) {
      return $image_styles;
    }

    // Increments.
    $pre_round_up_width = $width;
    for ($i = 0; $i < $total_increments; $i++) {
      $increase = NULL;
      if ($increment_type == 'px') {
        $prev_width = $width;
        $width = $pre_round_up_width + $increment_value;
        $pre_round_up_width = $width;
        if (!empty($round_up)) {
          $width = $this->roundUp($width, $round_up);
        }
        $increase = $width - $prev_width . 'px';
      }
      elseif ($increment_type == 'percent') {
        $prev_width = $width;
        $increment = ($pre_round_up_width / 100) * $increment_value;
        $width = ceil($pre_round_up_width + $increment);
        $pre_round_up_width = $width;
        if (!empty($round_up)) {
          $width = $this->roundUp($width, $round_up);
        }
        $increase = round((($width - $prev_width) / $prev_width) * 100) . '%';
        $increase .= ' (' . ($width - $prev_width) . 'px)';
      }
      if (!empty($aspect_ratio)) {
        $height = ceil(($width / 100) * $aspect_ratio);
      }
      $image_style_label = $this->createSizesImageStyleLabel($width);
      $image_style_name = $this->createImageStyleName($image_style_label);
      $image_styles[] = [
        'name' => $image_style_name,
        'label' => $image_style_label,
        'increase' => $increase,
        'width' => $width,
        'height' => $height,
      ];
    }

    return $image_styles;
  }

  /**
   * Helper function to round up to a value.
   *
   * @param int $num
   *   Value to round up.
   * @param int $round_value
   *   Value to round up to.
   *
   * @returns int
   *   Rounded up value.
   */
  public function roundUp($num, $round_value) {
    return ceil($num / $round_value) * $round_value;
  }

  /**
   * Helper function to create a Picture image style label.
   *
   * @param string $breakpoint_id
   *   The breakpoint ID.
   * @param string $multiplier_label
   *   The multiplier label.
   *
   * @returns string
   *   An image style label.
   */
  public function createPictureImageStyleLabel($breakpoint_id, $multiplier_label) {
    return $this->componentId . '--' . $breakpoint_id . $multiplier_label;
  }

  /**
   * Helper function to create a Sizes image style label.
   *
   * @param int $width
   *   Width of the image style.
   *
   * @returns string
   *   An image style label.
   */
  public function createSizesImageStyleLabel($width) {
    return $this->componentId . '--sizes-' . $width;
  }

  /**
   * Helper function to create a Sizes image style name.
   *
   * @param string $image_style_label
   *   Image style label.
   *
   * @returns string
   *   An image style machine name.
   */
  public function createImageStyleName($image_style_label) {
    return preg_replace('@[^a-z0-9]@', '_', $image_style_label);
  }

  /**
   * Returns aspect ratio options.
   *
   * @return array
   *   An array of aspect ratio options.
   */
  public function aspectRatioOptions() {
    // @todo: make configurable to add custom aspect ratios.
    return [
      '1:1' => $this->t('Square'),
      '2:1' => '2:1',
      '3:2' => '3:2',
      '4:3' => '4:3',
      '16:9' => '16:9',
    ];
  }

  /**
   * Get the form for mapping breakpoints to image styles.
   */
  public function breakpointMappingFormAjax($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $responsive_image_type = $form_state->getValue('responsive_image_type');
    if ($responsive_image_type == 'picture') {
      $this->validateFormPicture($form, $form_state);
    }
    elseif ($responsive_image_type == 'sizes') {
      $this->validateFormSizes($form, $form_state);
    }
  }

  /**
   * Validate function for Picture image styles.
   *
   * @param array $form
   *   Form array.
   * @param object $form_state
   *   Form state object.
   */
  public function validateFormPicture(&$form, $form_state) {
    // Verify if image styles don't already exist.
    $new_image_styles = $form_state->getValue(['image_styles']);
    if (is_array($new_image_styles)) {
      foreach ($new_image_styles as $table_id => $rows) {
        if (is_array($rows)) {
          foreach ($rows as $row_id => $row) {
            if ($row['exclude']['value'] == 1) {
              continue;
            }
            // Check if an image with the same name already exists.
            if (array_key_exists($row['data']['image_style_name'], image_style_options(FALSE))) {
              $form_state->setErrorByName('component_id', $this->t('Image style @image_style_name already exists.', [
                '@image_style_name' => $row['data']['image_style_name'],
              ]));
            }
            // Check if at least one of the dimension values are given.
            if (empty($row['width']['value']) && empty($row['height']['value'])) {
              $form_state->setErrorByName('image_styles][' . $table_id . '][' . $row_id . '][' . 'width][value', $this->t('Width and height can not both be blank.'));
              $form_state->setErrorByName('image_styles][' . $table_id . '][' . $row_id . '][' . 'height][value', $this->t('Width and height can not both be blank.'));
            }
          }
        }
      }
    }
  }

  /**
   * Validate function for Sizes image styles.
   *
   * @param array $form
   *   Form array.
   * @param object $form_state
   *   Form state object.
   */
  public function validateFormSizes(&$form, $form_state) {
    // Verify if image styles don't already exist.
    // Generate image styles.
    $image_styles = $this->generateSizesImageStyles($form, $form_state);
    $new_image_styles = array_map(function ($image_style) {
      return $image_style['name'];
    }, $image_styles);

    if (!empty($new_image_styles)) {
      foreach ($new_image_styles as $new_image_style) {
        // Check if an image with the same name already exists.
        if (array_key_exists($new_image_style, image_style_options(FALSE))) {
          $form_state->setErrorByName('component_id', $this->t('Image style @image_style_name already exists.', [
            '@image_style_name' => $new_image_style,
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Relay submit functions based on responsive image type.
    $responsive_image_type = $form_state->getValue('responsive_image_type');
    if ($responsive_image_type == 'picture') {
      $this->submitFormPicture($form, $form_state);
    }
    elseif ($responsive_image_type == 'sizes') {
      $this->submitFormSizes($form, $form_state);
    }
  }

  /**
   * Submit function for Picture image styles.
   *
   * @param array $form
   *   Form array.
   * @param object $form_state
   *   Form state object.
   */
  public function submitFormPicture($form, $form_state) {
    $values = $form_state->getValues();

    // Get image effects from base image style.
    $effects = $this->getEffectsArray();

    // Create image styles.
    if (is_array($values['image_styles'])) {
      foreach ($values['image_styles'] as $rows) {
        if (is_array($rows)) {
          foreach ($rows as $row) {
            if ($row['exclude']['value'] == 1) {
              continue;
            }
            // Create image style.
            $new_image_style = $this->entityTypeManager
              ->getStorage('image_style')
              ->create([
                'name' => $row['data']['image_style_name'],
                'label' => $row['data']['image_style_label'],
              ]);
            // Add image effects.
            foreach ($effects as $effect) {
              // Remove unique ID.
              unset($effect['uuid']);
              // Override dimensions if available.
              if (array_key_exists('width', $effect['data'])) {
                $effect['data']['width'] = $row['width']['value'];
              }
              if (array_key_exists('height', $effect['data'])) {
                $effect['data']['height'] = $row['height']['value'];
              }
              $new_image_style->addImageEffect($effect);
            }
            $new_image_style->save();

            // Image style message.
            $this->setImageSyleMsg($row['data']['image_style_name'], $row['data']['image_style_label']);
          }
        }
      }
    }

    // Prepare responsive image style array.
    $responsive_image_style_array = [];
    $breakpoints = array_reverse($this->breakpoints, TRUE);
    foreach ($breakpoints as $breakpoint_id => $breakpoint) {
      $responsive_image_style_array[$breakpoint_id] = [];
      foreach ($breakpoint->getMultipliers() as $multiplier_name) {
        $responsive_image_style_array[$breakpoint_id][$multiplier_name] = NULL;
      }
    }
    // Assign values to responsive image style array.
    if (is_array($values['image_styles'])) {
      foreach ($values['image_styles'] as $rows) {
        if (is_array($rows)) {
          foreach ($rows as $row) {
            if ($row['exclude']['value'] == 1) {
              continue;
            }
            $mapping = [
              'image_mapping_type' => 'image_style',
              'image_mapping' => $row['data']['image_style_name'],
            ];
            $breakpoint_id = $row['data']['breakpoint_id'];
            $multiplier_name = $row['data']['multiplier_name'];
            $responsive_image_style_array[$breakpoint_id][$multiplier_name] = $mapping;
          }
        }
      }
    }
    // Create picture mapping.
    $responsive_image_style = $this->entityTypeManager
      ->getStorage('responsive_image_style')
      ->create([
        'id' => $values['component_id'],
        'label' => $values['component_label'],
        'fallback_image_style' => $values['image_styles']['fallback_image_style'],
        'breakpoint_group' => $values['breakpoint_group'],
      ]);
    foreach ($responsive_image_style_array as $breakpoint_id => $multipliers) {
      foreach ($multipliers as $multiplier_name => $mapping) {
        if (!empty($responsive_image_style_array[$breakpoint_id][$multiplier_name])) {
          $responsive_image_style->addImageStyleMapping($breakpoint_id, $multiplier_name, $mapping);
        }
      }
    }
    $responsive_image_style->save();

    // Responsive image style message.
    $this->setResponsiveImageSyleMsg();
  }

  /**
   * Submit function for Sizes image styles.
   *
   * @param array $form
   *   Form array.
   * @param object $form_state
   *   Form state object.
   */
  public function submitFormSizes($form, $form_state) {
    $values = $form_state->getValues();

    // Generate image styles.
    $image_styles = $this->generateSizesImageStyles($form, $form_state);

    // Get image effects from base image style.
    $effects = $this->getEffectsArray();

    // Create image styles.
    if (!empty($image_styles)) {
      foreach ($image_styles as $image_style) {
        // Create image style.
        $new_image_style = $this->entityTypeManager
          ->getStorage('image_style')
          ->create([
            'name' => $image_style['name'],
            'label' => $image_style['label'],
          ]);
        // Add image effects.
        foreach ($effects as $effect) {
          // Remove unique ID.
          unset($effect['uuid']);
          // Override dimensions if available.
          if (array_key_exists('width', $effect['data'])) {
            $effect['data']['width'] = $image_style['width'];
          }
          if (array_key_exists('height', $effect['data'])) {
            $effect['data']['height'] = $image_style['height'];
          }
          $new_image_style->addImageEffect($effect);
        }
        $new_image_style->save();

        // Image style message.
        $this->setImageSyleMsg($image_style['name'], $image_style['label']);
      }
    }

    // Create Sizes mapping.
    $mapping = [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => $values['image_styles']['sizes'],
        'sizes_image_styles' => array_map(function ($image_style) {
          return $image_style['name'];
        }, $image_styles),
      ],
    ];

    // Create image style mapping.
    $this->entityTypeManager
      ->getStorage('responsive_image_style')
      ->create([
        'id' => $values['component_id'],
        'label' => $values['component_label'],
        'fallback_image_style' => $values['image_styles']['fallback_image_style'],
        'breakpoint_group' => 'responsive_image',
      ])
      ->addImageStyleMapping('responsive_image.viewport_sizing', '1x', $mapping)
      ->save();

    // Responsive image style message.
    $this->setResponsiveImageSyleMsg();
  }

  /**
   * Sets a Drupal message for the created image style.
   *
   * @param string $name
   *   Image style name.
   * @param string $label
   *   Image style label.
   */
  public function setImageSyleMsg($name, $label) {
    // Prepare message.
    $url = Url::fromRoute(
      'entity.image_style.edit_form',
      ['image_style' => $name]
    )->toString();
    $msg = $this->t('Created image style: <a href=":image_style_url">@image_style_label</a>', [
      ':image_style_url' => $url,
      '@image_style_label' => $label,
    ]);
    drupal_set_message($msg);
  }

  /**
   * Sets a Drupal message for the created responsive image style.
   */
  public function setResponsiveImageSyleMsg() {
    // Prepare message.
    $url = Url::fromRoute(
      'entity.responsive_image_style.edit_form',
      ['responsive_image_style' => $this->componentId]
    )->toString();
    $msg = $this->t('Created responsive image style: <a href=":responsive_image_style_url">@component_label</a>', [
      ':responsive_image_style_url' => $url,
      '@component_label' => $this->componentLabel,
    ]);
    drupal_set_message($msg);
  }

}
