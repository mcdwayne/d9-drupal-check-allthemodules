<?php

namespace Drupal\reference_swiper\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SwiperOptionSetForm.
 *
 * Implements the form for the swiper option set config entity.
 *
 * @package Drupal\reference_swiper\Form
 */
class SwiperOptionSetForm extends EntityForm {

  /**
   * The injected entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Injects the entity_query service.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#entity_builders'][] = [$this, 'prepareParameters'];
    /* @var $swiper_option_set \Drupal\reference_swiper\SwiperOptionSetInterface */
    $swiper_option_set = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $swiper_option_set->label(),
      '#description' => $this->t('Label for the Swiper option set.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $swiper_option_set->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$swiper_option_set->isNew(),
    ];

    // Display the form in tabs as there are way too many options to display for
    // one single form.
    $form['tabs'] = array(
      '#type' => 'vertical_tabs',
      '#tree' => TRUE,
      '#title' => $this->t('Swiper parameters'),
    );

    // Common settings.
    $form['common'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic settings'),
      '#group' => 'tabs',
    ];
    $form['common']['initialSlide'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Index number of initial slide.'),
    ];
    $form['common']['direction'] = [
      '#type' => 'select',
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
        'vertical' => $this->t('Vertical'),
      ],
      '#description' => $this->t("Could be 'horizontal' or 'vertical' (for vertical slider)."),
    ];
    $form['common']['speed'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Duration of transition between slides (in ms).'),
    ];
    $form['common']['setWrapperSize'] = [
      '#type' => 'checkbox',
      '#description' => $this->t("Enabled this option and plugin will set width/height on swiper wrapper equal to total size of all slides. Mostly should be used as compatibility fallback option for browser that don't support flexbox layout well."),
    ];
    $form['common']['virtualTranslate'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Enabled this option and swiper will be operated as usual except it will not move, real translate values on wrapper will not be set. Useful when you may need to create custom slide transition.'),
    ];
    $form['common']['width'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Swiper width (in px). Parameter allows to force Swiper width. Useful only if you initialize Swiper when it is hidden.<strong>Setting this parameter will make Swiper not responsive</strong>'),
    ];
    $form['common']['height'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Swiper height (in px). Parameter allows to force Swiper height. Useful only if you initialize Swiper when it is hidden.<strong>Setting this parameter will make Swiper not responsive</strong>'),
    ];
    $form['common']['autoHeight'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true and slider wrapper will adopt its height to the height of the currently active slide.'),
    ];
    $form['common']['roundLengths'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to round values of slides width and height to prevent blurry texts on usual resolution screens (if you have such).'),
    ];
    $form['common']['nested'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true on nested Swiper for correct touch events interception. Use only on nested swipers that use same direction as the parent one.'),
    ];
//    $form['common']['parallax'] = [
//      '#type' => 'checkbox',
//      '#description' => $this->t('Enable, if you want to use "parallaxed" elements inside of slider.'),
//    ];
    $form['common']['grabCursor'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('This option may a little improve desktop usability. If true, user will see the "grab" cursor when hover on Swiper.'),
    ];
    // Navigation Buttons.
    $form['common']['nextButton'] = [
      '#type' => 'textfield',
      '#description' => $this->t('String with CSS selector of the element that will work like "next" button after click on it. <strong>Currently, only HTML id or class selectors are supported</strong>.'),
      '#element_validate' => [[$this, 'validateSelectorString']],
    ];
    $form['common']['prevButton'] = [
      '#type' => 'textfield',
      '#description' => $this->t('String with CSS selector of the element that will work like "prev" button after click on it. <strong>Currently, only HTML id or class selectors are supported</strong>.'),
      '#element_validate' => [[$this, 'validateSelectorString']],
    ];
    // Hash Navigation.
    $form['common']['hashnav'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to enable hash url navigation to for slides.'),
    ];
    // Breakpoints.
    $form['common']['breakpoints'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Allows to set different parameter for different responsive breakpoints (screen sizes). Not all parameters can be changed in breakpoints, only those which are not required different layout and logic, like slidesPerView, slidesPerGroup, spaceBetween. Such parameters like slidesPerColumn, loop, direction, effect won't work. <strong>The value has to be in JSON format, for example: {\"768\":{\"slidesPerView\":1},\"940\":{\"slidesPerView\":2}}</strong>"),
    ];

    // Autoplay.
    $form['autoplay_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Autoplay'),
      '#group' => 'tabs',
    ];
    $form['autoplay_wrapper']['autoplay'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Delay between transitions (in ms). If set to zero or blank, auto play will be disabled.'),
    ];
    $form['autoplay_wrapper']['autoplayStopOnLast'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Enable this parameter and autoplay will be stopped when it reaches last slide (has no effect in loop mode).'),
    ];
    $form['autoplay_wrapper']['autoplayDisableOnInteraction'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false and autoplay will not be disabled after user interactions (swipes), it will be restarted every time after interaction.'),
    ];

    // Progress.
    $form['progress'] = [
      '#type' => 'details',
      '#title' => $this->t('Progress'),
      '#group' => 'tabs',
    ];
    $form['progress']['watchSlidesProgress'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Enable this feature to calculate each slides progress.'),
    ];
    $form['progress']['watchSlidesVisibility'] = [
      '#type' => 'checkbox',
      '#description' => $this->t("'Watch slides progress' should be enabled. Enable this option and slides that are in viewport will have additional visible class."),
    ];

    // Freemode.
    $form['freemode'] = [
      '#type' => 'details',
      '#title' => $this->t('Free mode'),
      '#group' => 'tabs',
    ];
    $form['freemode']['freeMode'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If true then slides will not have fixed positions.'),
    ];
    $form['freemode']['freeModeMomentum'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If true, then slide will keep moving for a while after you release it.'),
    ];
    $form['freemode']['freeModeMomentumRatio'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Higher value produces larger momentum distance after you release slider.'),
    ];
    $form['freemode']['freeModeMomentumBounce'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false if you want to disable momentum bounce in free mode.'),
    ];
    $form['freemode']['freeModeMomentumBounceRatio'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Higher value produces larger momentum bounce effect.'),
    ];
    $form['freemode']['freeModeMinimumVelocity'] = [
      '#min' => 0,
      '#step' => 0.1,
      '#description' => $this->t('Minimum touchmove-velocity required to trigger free mode momentum.'),
    ];
    $form['freemode']['freeModeSticky'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to enable snap to slides positions in free mode.'),
    ];

    // Effects.
    $form['effect_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Effects'),
      '#group' => 'tabs',
    ];
    $form['effect_wrapper']['effect'] = [
      '#type' => 'select',
      '#options' => [
        'slide' => $this->t('Slide'),
        'fade' => $this->t('Fade'),
        'cube' => $this->t('Cube'),
        'coverflow' => $this->t('Coverflow'),
        'flip' => $this->t('Flip'),
      ],
      '#description' => $this->t('Could be "slide", "fade", "cube", "coverflow" or "flip".'),
    ];
    $form['effect_wrapper']['fade'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Fade effect parameters. <strong>The value has to be in JSON format</strong>.'),
    ];
    $form['effect_wrapper']['cube'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Cube effect parameters. For better performance you may disable shadows. <strong>The value has to be in JSON format</strong>.'),
    ];
    $form['effect_wrapper']['coverflow'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Coverflow effect parameters. For better performance you may disable shadows. <strong>The value has to be in JSON format</strong>.'),
    ];
    $form['effect_wrapper']['flip'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Flip effect parameters. limitRotation (when enabled) limits slides rotation angle to 180deg maximum. It allows to quickly "flip" between different slides. If you use "slow" transitions then it is better to disable it. <strong>The value has to be in JSON format</strong>.'),
    ];

    // Slides grid.
    $form['slides_grid'] = [
      '#type' => 'details',
      '#title' => $this->t('Slides grid'),
      '#group' => 'tabs',
    ];
    $form['slides_grid']['spaceBetween'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Distance between slides in px.'),
    ];
    $form['slides_grid']['slidesPerView'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t("Number of slides per view (slides visible at the same time on slider's container) or zero to set automatic."),
    ];
    $form['slides_grid']['slidesPerColumn'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Number of slides per column, for multirow layout.'),
    ];
    $form['slides_grid']['slidesPerColumnFill'] = [
      '#type' => 'select',
      '#options' => [
        'row' => $this->t('Row'),
        'column' => $this->t('Column'),
      ],
      '#description' => $this->t("Could be 'column' or 'row'. Defines how slides should fill rows, by column or by row."),
    ];
    $form['slides_grid']['slidesPerGroup'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Set numbers of slides to define and enable group sliding. Useful to use with slidesPerView > 1.'),
    ];
    $form['slides_grid']['centeredSlides'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If true, then active slide will be centered, not always on the left side.'),
    ];
    $form['slides_grid']['slidesOffsetBefore'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Add (in px) additional slide offset in the beginning of the container (before all slides).'),
    ];
    $form['slides_grid']['slidesOffsetAfter'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Add (in px) additional slide offset in the end of the container (after all slides).'),
    ];

    // Touches.
    $form['touches'] = [
      '#type' => 'details',
      '#title' => $this->t('Touches'),
      '#group' => 'tabs',
    ];
    $form['touches']['touchEventsTarget'] = [
      '#type' => 'select',
      '#options' => [
        'container' => $this->t('Container'),
        'wrapper' => $this->t('Wrapper'),
      ],
      '#description' => $this->t('Target element to listen touch events on. Can be "container" (to listen for touch events on swiper-container) or "wrapper" (to listen for touch events on swiper-wrapper).'),
    ];
    $form['touches']['touchRatio'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
    ];
    $form['touches']['touchAngle'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Allowable angle (in degrees) to trigger touch move.'),
    ];
    $form['touches']['simulateTouch'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If true, Swiper will accept mouse events like touch events (click and drag to change slides).'),
    ];
    $form['touches']['shortSwipes'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false if you want to disable short swipes.'),
    ];
    $form['touches']['longSwipes'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false if you want to disable long swipes.'),
    ];
    $form['touches']['longSwipesRatio'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 0.1,
      '#description' => $this->t('Ratio to trigger swipe to next/previous slide during long swipes.'),
    ];
    $form['touches']['longSwipesMs'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Minimal duration (in ms) to trigger swipe to next/previous slide during long swipes.'),
    ];
    $form['touches']['followFinger'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If disabled, then slider will be animated only when you release it, it will not move while you hold your finger on it.'),
    ];
    $form['touches']['onlyExternal'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If true, then the only way to switch the slide is use of external API functions like slidePrev or slideNext.'),
    ];
    $form['touches']['threshold'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Threshold value in px. If "touch distance" will be lower than this value then swiper will not move.'),
    ];
    $form['touches']['touchMoveStopPropagation'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If enabled, then propagation of "touchmove" will be stopped.'),
    ];
    $form['touches']['iOSEdgeSwipeDetection'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('IEnable to release Swiper events for swipe-to-go-back work in iOS UIWebView.'),
    ];
    $form['touches']['iOSEdgeSwipeThreshold'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Area (in px) from left edge of the screen to release touch events for swipe-to-go-back in iOS UIWebView.'),
    ];

    $form['resistance_clicks'] = [
      '#type' => 'details',
      '#title' => $this->t('Touch resistance and clicks'),
      '#group' => 'tabs',
    ];
    // Touch Resistance.
    $form['resistance_clicks']['resistance'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false if you want to disable resistant bounds.'),
    ];
    $form['resistance_clicks']['resistanceRatio'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 0.01,
      '#description' => $this->t('This option allows you to control resistance ratio.'),
    ];

    // Clicks.
    $form['resistance_clicks']['preventClicks'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to prevent accidental unwanted clicks on links during swiping.'),
    ];
    $form['resistance_clicks']['preventClicksPropagation'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to stop clicks event propagation on links during swiping.'),
    ];
    $form['resistance_clicks']['slideToClickedSlide'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true and click on any slide will produce transition to this slide.'),
    ];

    // Swiping / No swiping.
    $form['swiping'] = [
      '#type' => 'details',
      '#title' => $this->t('Swiping / No swiping'),
      '#group' => 'tabs',
    ];
    $form['swiping']['allowSwipeToPrev'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false to disable swiping to previous slide direction (to left or top).'),
    ];
    $form['swiping']['allowSwipeToNext'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false to disable swiping to next slide direction (to right or bottom).'),
    ];
    $form['swiping']['noSwiping'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to false to disable swiping to next slide direction (to right or bottom).'),
    ];
    $form['swiping']['noSwipingClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t("If true, then you can add noSwipingClass class to swiper's slide to prevent/disable swiping on this element."),
    ];
    $form['swiping']['swipeHandler'] = [
      '#type' => 'textfield',
      '#description' => $this->t('String with CSS selector of the container with pagination that will work as only available handler for swiping.'),
      '#element_validate' => [[$this, 'validateSelectorString']],
    ];

    // Pagination.
    $form['pagination_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Pagination'),
      '#group' => 'tabs',
    ];
    $form['pagination_wrapper']['pagination'] = [
      '#type' => 'textfield',
      '#description' => $this->t('String with CSS selector of the container with pagination. <strong>Currently, only HTML id or class selectors are supported</strong>.'),
      '#element_validate' => [[$this, 'validateSelectorString']],
    ];
    $form['pagination_wrapper']['paginationType'] = [
      '#type' => 'select',
      '#options' => [
        'bullets' => $this->t('Bullets'),
        'fraction' => $this->t('Fraction'),
        'progress' => $this->t('Progress'),
        'custom' => $this->t('Custom'),
      ],
      '#description' => $this->t('Type of pagination. Can be "bullets", "fraction", "progress" or "custom"'),
    ];
    $form['pagination_wrapper']['paginationHide'] = [
      '#type' => 'checkbox',
      '#description' => $this->t("Toggle (hide/true) pagination container visibility when click on Slider's container."),
    ];
    $form['pagination_wrapper']['paginationClickable'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If true then clicking on pagination button will cause transition to appropriate slide.'),
    ];
    $form['pagination_wrapper']['paginationElement'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Defines which HTML tag will be use to represent single pagination bullet. . Only for bullets pagination type.'),
    ];
    $form['pagination_wrapper']['info'] = [
      '#markup' => t('<strong>Do not include function headers in the textfields below. Just enter the corresponding statements (the function body), which shall be executed by the render function.</strong>'),
    ];
    $form['pagination_wrapper']['paginationBulletRender'] = [
      '#type' => 'textarea',
      '#description' => $this->t('This parameter allows totally customize pagination bullets, you need to pass here a function that accepts index number of pagination bullet and required element class name (className). Only for bullets pagination type.'),
    ];
    $form['pagination_wrapper']['paginationFractionRender'] = [
      '#type' => 'textarea',
      '#description' => $this->t('This parameter allows to customize "fraction" pagination html. Only for fraction pagination type.'),
    ];
    $form['pagination_wrapper']['paginationProgressRender'] = [
      '#type' => 'textarea',
      '#description' => $this->t('This parameter allows to customize "progress" pagination. Only for progress pagination type.'),
    ];
    $form['pagination_wrapper']['paginationCustomRender'] = [
      '#type' => 'textarea',
      '#description' => $this->t('This parameter is required for custom pagination type where you have to specify how it should be rendered.'),
    ];

    // Scollbar.
    $form['scrollbar_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Scrollbar'),
      '#group' => 'tabs',
    ];
    $form['scrollbar_wrapper']['scrollbar'] = [
      '#type' => 'textfield',
      '#description' => $this->t('String with CSS selector of the container with scrollbar. <strong>Currently, only HTML id or class selectors are supported</strong>.'),
      '#element_validate' => [[$this, 'validateSelectorString']],
    ];
    $form['scrollbar_wrapper']['scrollbarHide'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Hide scrollbar automatically after user interaction.'),
    ];
    $form['scrollbar_wrapper']['scrollbarDraggable'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to enable make scrollbar draggable that allows you to control slider position.'),
    ];
    $form['scrollbar_wrapper']['scrollbarSnapOnRelease'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to snap slider position to slides when you release scrollbar.'),
    ];

    // Accessibility.
    $form['accessibility'] = [
      '#type' => 'details',
      '#title' => $this->t('Accessibility'),
      '#group' => 'tabs',
    ];
    $form['accessibility']['a11y'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Option to enable keyboard accessibility to provide foucsable navigation buttons and basic ARIA for screen readers.'),
    ];
    $form['accessibility']['prevSlideMessage'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Message for screen readers for previous button.'),
    ];
    $form['accessibility']['nextSlideMessage'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Message for screen readers for next button.'),
    ];
    $form['accessibility']['firstSlideMessage'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Message for screen readers for previous button when swiper is on first slide.'),
    ];
    $form['accessibility']['lastSlideMessage'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Message for screen readers for previous button when swiper is on last slide.'),
    ];
    $form['accessibility']['paginationBulletMessage'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Message for screen readers for single pagination bullet.'),
    ];

    // Keyboard / Mousewheel.
    $form['keyboard'] = [
      '#type' => 'details',
      '#title' => $this->t('Keyboard and mouse control'),
      '#group' => 'tabs',
    ];
    $form['keyboard']['keyboardControl'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to enable navigation through slides using keyboard right and left (for horizontal mode), top and borrom (for vertical mode) keyboard arrows.'),
    ];
    $form['keyboard']['mousewheelControl'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to enable navigation through slides using mouse wheel.'),
    ];
    $form['keyboard']['mousewheelForceToAxis'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to force mousewheel swipes to axis. So in horizontal mode mousewheel will work only with horizontal mousewheel scrolling, and only with vertical scrolling in vertical mode.'),
    ];
    $form['keyboard']['mousewheelReleaseOnEdges'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true and swiper will release mousewheel event and allow page scrolling when swiper is on edge positions (in the beginning or in the end).'),
    ];
    $form['keyboard']['mousewheelInvert'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to invert sliding direction.'),
    ];
    $form['keyboard']['mousewheelSensitivity'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('Multiplier of mousewheel data, allows to tweak mouse wheel sensitivity.'),
    ];

    // Images.
    $form['images'] = [
      '#type' => 'details',
      '#title' => $this->t('Images'),
      '#group' => 'tabs',
    ];
    $form['images']['preloadImages'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('When enabled Swiper will force to load all images.'),
    ];
    $form['images']['updateOnImagesReady'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('When enabled Swiper will be reinitialized after all inner images (<img> tags) are loaded. Required preloadImages: true.'),
    ];
//    $form['images']['lazyLoading'] = [
//      '#type' => 'checkbox',
//      '#description' => $this->t('Set to "true" to enable images lazy loading. Note that preloadImages should be disabled.'),
//    ];
//    $form['images']['lazyLoadingInPrevNext'] = [
//      '#type' => 'checkbox',
//      '#description' => $this->t('Set to "true" to enable lazy loading for the closest slides images (for previous and next slide images).'),
//    ];
//    $form['images']['lazyLoadingInPrevNextAmount'] = [
//      '#type' => 'number',
//      '#min' => !empty($swiper_option_set->getParameters()['slidesPerView']) ? $swiper_option_set->getParameters()['slidesPerView'] : 1,
//      '#step' => 1,
//      '#description' => $this->t("Amount of next/prev slides to preload lazy images in. Can't be less than slidesPerView."),
//    ];
//    $form['images']['lazyLoadingOnTransitionStart'] = [
//      '#type' => 'checkbox',
//      '#description' => $this->t('By default, Swiper will load lazy images after transition to this slide, so you may enable this parameter if you need it to start loading of new image in the beginning of transition.'),
//    ];

    $form['loop_control_observer'] = [
      '#type' => 'details',
      '#title' => $this->t('Loop, control and observer'),
      '#group' => 'tabs',
    ];
    // Loop.
    $form['loop_control_observer']['loop'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to enable continuous loop mode.'),
    ];
    $form['loop_control_observer']['loopAdditionalSlides'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Addition number of slides that will be cloned after creating of loop.'),
    ];
    $form['loop_control_observer']['loopedSlides'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t("If you use slidesPerView:'auto' with loop mode you should tell to Swiper how many slides it should loop (duplicate) using this parameter."),
    ];

    // Controller.
//     $form['loop_control_observer']['control'] = [
//       '#type' => '[Swiper Instance]',
//       '#description' => $this->t('Pass here another Swiper instance or array with Swiper instances that should be controlled by this Swiper.'),
//     ];
    $form['loop_control_observer']['controlInverse'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true and controlling will be in inverse direction.'),
    ];
    $form['loop_control_observer']['controlBy'] = [
      '#type' => 'textfield',
      '#description' => $this->t("Can be 'slide' or 'container'. Defines a way how to control another slider: slide by slide (with respect to other slider's grid) or depending on all slides/container (depending on total slider percentage) Observer"),
    ];

    // Observer.
    $form['loop_control_observer']['observer'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true to enable Mutation Observer on Swiper and its elements. In this case Swiper will be updated (reinitialized) each time if you change its style (like hide/show) or modify its child elements (like adding/removing slides).'),
    ];
    $form['loop_control_observer']['observeParents'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Set to true if you also need to watch Mutations for Swiper parent elements.'),
    ];

    // Callbacks.
    $form['callbacks'] = [
      '#type' => 'details',
      '#title' => $this->t('Callbacks'),
      '#group' => 'tabs',
    ];
    $form['callbacks']['runCallbacksOnInit'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Run on[Transition/SlideChange][Start/End] callbacks on swiper initialization. Such callbacks will be fired on initialization in case of your initialSlide is not 0, or you use loop mode.'),
    ];
    $form['callbacks']['info'] = [
      '#markup' => t('<strong>Do not include function headers in the textfields for the callbacks below. Just enter the corresponding statements (the function body), which shall be executed by the callback.</strong>'),
    ];
    $form['callbacks']['onInit'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed right after Swiper initialization.'),
    ];
    $form['callbacks']['onSlideChangeStart'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed in the beginning of animation to other slide (next or previous). Receives swiper instance as an argument.'),
    ];
    $form['callbacks']['onSlideChangeEnd'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed after animation to other slide (next or previous). Receives slider instance as an argument.'),
    ];
    $form['callbacks']['onSlideNextStart'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Same as "onSlideChangeStart" but for "forward" direction only.'),
    ];
    $form['callbacks']['onSlideNextStart'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Same as "onSlideChangeEnd" but for "forward" direction only.'),
    ];
    $form['callbacks']['onSlidePrevStart'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Same as "onSlideChangeStart" but for "backward" direction only.'),
    ];
    $form['callbacks']['onSlidePrevEnd'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Same as "onSlideChangeEnd" but for "backward" direction only.'),
    ];
    $form['callbacks']['onTransitionStart'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed in the beginning of transition. Receives swiper instance as an argument.'),
    ];
    $form['callbacks']['onTransitionEnd'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed after transition. Receives slider instance as an argument.'),
    ];
    $form['callbacks']['onTouchStart'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user touch Swiper. Receives swiper instance and 'touchstart' event as an arguments."),
    ];
    $form['callbacks']['onTouchMove'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user touch and move finger over Swiper. Receives swiper instance and 'touchmove' event as an arguments."),
    ];
    $form['callbacks']['onTouchMoveOpposite'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user touch and move finger over Swiper in direction opposite to direction parameter. Receives swiper instance and 'touchmove' event as an arguments."),
    ];
    $form['callbacks']['onSliderMove'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user touch and move finger over Swiper and move it. Receives swiper instance and 'touchmove' event as an arguments."),
    ];
    $form['callbacks']['onTouchEnd'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user release Swiper. Receives swiper instance and 'touchend' event as an arguments."),
    ];
    $form['callbacks']['onClick'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user click/tap on Swiper after 300ms delay. Receives swiper instance and 'touchend' event as an arguments."),
    ];
    $form['callbacks']['onTap'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user click/tap on Swiper. Receives swiper instance and 'touchend' event as an arguments."),
    ];
    $form['callbacks']['onDoubleTap'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, event, will be executed when user double tap on Swiper's container. Receives swiper instance and 'touchend' event as an arguments"),
    ];
    $form['callbacks']['onImagesReady'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed right after all inner images are loaded. updateOnImagesReady should be also enabled'),
    ];
    $form['callbacks']['onProgress'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, progress, will be executed when Swiper progress is changed, as second arguments it receives progress that is always from 0 to 1'),
    ];
    $form['callbacks']['onReachBeginning'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed when Swiper reach its beginning (initial position)'),
    ];
    $form['callbacks']['onReachEnd'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed when Swiper reach last slide'),
    ];
    $form['callbacks']['onDestroy'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed when you destroy Swiper'),
    ];
    $form['callbacks']['onSetTranslate'] = [
      '#type' => 'textarea',
      '#description' => $this->t("Callback function with arguments, swiper, translate, will be executed when swiper's wrapper change its position. Receives swiper instance and current translate value as an arguments"),
    ];
    $form['callbacks']['onSetTransition'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, transition, will be executed everytime when swiper starts animation. Receives swiper instance and current transition duration (in ms) as an arguments'),
    ];
    $form['callbacks']['onAutoplay'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Same as onSlideChangeStart but caused by autoplay.'),
    ];
    $form['callbacks']['onAutoplayStart'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed when when autoplay started'),
    ];
    $form['callbacks']['onAutoplayStop'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function with arguments, swiper, will be executed when when autoplay stopped'),
    ];
//    $form['callbacks']['onLazyImageLoad'] = [
//      '#type' => 'textarea',
//      '#description' => $this->t('Callback function with arguments, swiper, slide, image, will be executed in the beginning of lazy loading of image'),
//    ];
//    $form['callbacks']['onLazyImageReady'] = [
//      '#type' => 'textarea',
//      '#description' => $this->t('Callback function with arguments, swiper, slide, image, will be executed when lazy loading image will be loaded'),
//    ];
    $form['callbacks']['onPaginationRendered'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Callback function, will be executed after pagination elements generated and added to DOM.'),
    ];

    // Namespace.
    $form['namespace'] = [
      '#type' => 'details',
      '#title' => $this->t('Namespace'),
      '#group' => 'tabs',
    ];
    $form['namespace']['slideClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of slide.'),
    ];
    $form['namespace']['slideActiveClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of currently active slide.'),
    ];
    $form['namespace']['slideVisibleClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of currently visible slide.'),
    ];
    $form['namespace']['slideDuplicateClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of slide duplicated by loop mode.'),
    ];
    $form['namespace']['slideNextClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of slide which is right after currently active slide.'),
    ];
    $form['namespace']['slidePrevClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of slide which is right before currently active slide.'),
    ];
    $form['namespace']['wrapperClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t("CSS class name of slides' wrapper."),
    ];
    $form['namespace']['bulletClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of single pagination bullet.'),
    ];
    $form['namespace']['bulletActiveClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of currently active pagination bullet.'),
    ];
    $form['namespace']['paginationHiddenClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of pagination when it becomes inactive.'),
    ];
    $form['namespace']['paginationCurrentClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of the element with currently active index in "fraction" pagination.'),
    ];
    $form['namespace']['paginationTotalClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of the element with total number of "snaps" in "fraction" pagination.'),
    ];
    $form['namespace']['paginationProgressbarClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of pagination progressbar.'),
    ];
    $form['namespace']['buttonDisabledClass'] = [
      '#type' => 'textfield',
      '#description' => $this->t('CSS class name of next/prev button when it becomes disabled.'),
    ];

    // Use this invisible element only to group parameter values in the form
    // state values array. Such that they are available as sub keys of a
    // 'parameters' element that corresponds to the config entity's property.
    $form['parameters'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );

    // Set defaults and add titles for the fields.
    $defaults = $this->getSwiperDefaults();
    foreach ($defaults as $group => $options) {
      foreach (array_keys($options) as $key) {
        $title = ucfirst(strtolower(preg_replace('/([A-Z])/', ' $1', $key)));
        // If parameter was yet set, use it, otherwise use default fallback.
        $default = array_key_exists($key, $swiper_option_set->getParameters()) ? $swiper_option_set->getParameters()[$key] : $options[$key];
        $form[$group][$key]['#title'] = $title;
        $form[$group][$key]['#default_value'] = $default;
        // Make sure the value appears below 'parameters' key in form state.
        $form[$group][$key]['#parents'] = ['parameters', $key];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Clear parameters before setting them in order to prevent setting of
    // disabled parameters like width or height.
    $status = $this->entity->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Swiper option set.', array(
        '%label' => $this->entity->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Swiper option set was not saved.', array(
        '%label' => $this->entity->label(),
      )));
    }

    $form_state->setRedirect('entity.swiper_option_set.collection');
  }

  /**
   * Resets the parameters property on the option set.
   *
   * This is necessary because an option set shall contain only parameters,
   * which are differing from the default values. As parameters get passed down
   * to the client side as drupal settings, we don't want to pollute those
   * with default values that are set upon Swiper instantiation anyway.
   *
   * The buildEntity() method of the parent copies all values into the
   * parameters property, so we override the property again in this entity
   * builder callback, in order to make sure only parameters that differ from
   * defaults are set.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::form()
   */
  protected function prepareParameters($entity_type_id, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['parameters'];
    $grouped_defaults = $this->getSwiperDefaults();
    $parameter_defaults = [];
    $parameters = [];
    // Get all defaults into one array.
    foreach ($grouped_defaults as $group => $defaults) {
      $parameter_defaults += $defaults;
    }
    // Gather all parameters that are not equal to their default value.
    foreach ($values as $parameter_key => $value) {
      if ($value != $parameter_defaults[$parameter_key]) {
        $parameters[$parameter_key] = $value;
      }
    }

    // Finally make sure some html classes are are set, as they're required in
    // pre-processing.
    $base_parameters = ['wrapperClass', 'slideClass'];
    foreach ($base_parameters as $parameter_key) {
      if (empty($parameters[$parameter_key])) {
        $parameters[$parameter_key] = $parameter_defaults[$parameter_key];
      }
    }

    // Clear parameters and reset them to the gathered ones.
    $entity
      ->clearParameters()
      ->setParameters($parameters);
  }

  /**
   * Validation callback for elements used as html selector in pre-process.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateSelectorString(&$element, FormStateInterface $form_state) {
    // If nothing was entered there's the option set isn't saving the value, so
    // no need to validate anything at all.
    $parameter_key = $element['#array_parents'][1];
    $element_value = $form_state->getValue('parameters')[$parameter_key];
    if (empty($element_value)) {
      return;
    }

    $selector_start = substr($element_value, 0, 1);
    if (!in_array($selector_start, ['.', '#'])) {
      $form_state->setError(
        $element,
        t(
          '@parameter: Please enter a valid HTML id or class selector.',
          ['@parameter' => $parameter_key]
        )
      );
    }
  }

  /**
   * Checks whether an option sets with the given id exists yet.
   *
   * @param string $id
   *   ID to check for.
   *
   * @return bool
   *   TRUE if an entity with the given ID exists, FALSE otherwise.
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('swiper_option_set')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Provides the Swiper library's parameter default values.
   *
   * @return array
   *   Array of default parameter values, indexed by parameter id.
   */
  protected function getSwiperDefaults() {
    // @todo make defaults configurable using simple config.
    return [
      'common' => [
        'initialSlide' => 0,
        'direction' => 'horizontal',
        'speed' => 300,
        'setWrapperSize' => FALSE,
        'virtualTranslate' => FALSE,
        'width' => '',
        'height' => '',
        'autoHeight' => FALSE,
        'roundLengths' => FALSE,
        'nested' => FALSE,
//        'parallax' => FALSE,
        'grabCursor' => FALSE,
        'nextButton' => '',
        'prevButton' => '',
        'hashnav' => FALSE,
        'breakpoints' => '',
      ],
      // Autoplay.
      'autoplay_wrapper' => [
        'autoplay' => 0,
        'autoplayStopOnLast' => FALSE,
        'autoplayDisableOnInteraction' => TRUE,
      ],
      // Progress.
      'progress' => [
        'watchSlidesProgress' => FALSE,
        'watchSlidesVisibility' => FALSE,
      ],
      // Freemode.
      'freemode' => [
        'freeMode' => FALSE,
        'freeModeMomentum' => TRUE,
        'freeModeMomentumRatio' => 1,
        'freeModeMomentumBounce' => TRUE,
        'freeModeMomentumBounceRatio' => 1,
        'freeModeMinimumVelocity' => 0.02,
        'freeModeSticky' => FALSE,
      ],
      // Effects.
      'effect_wrapper' => [
        'effect' => 'slide',
        'fade' => '',
        'cube' => '',
        'coverflow' => '',
        'flip' => '',
      ],
      // Slides grid.
      'slides_grid' => [
        'spaceBetween' => 0,
        'slidesPerView' => 1,
        'slidesPerColumn' => 1,
        'slidesPerColumnFill' => 'column',
        'slidesPerGroup' => 1,
        'centeredSlides' => FALSE,
        'slidesOffsetBefore' => 0,
        'slidesOffsetAfter' => 0,
      ],
      // Touches.
      'touches' => [
        'touchEventsTarget' => 'container',
        'touchRatio' => 1,
        'touchAngle' => 45,
        'simulateTouch' => TRUE,
        'shortSwipes' => TRUE,
        'longSwipes' => TRUE,
        'longSwipesRatio' => 0.5,
        'longSwipesMs' => 300,
        'followFinger' => TRUE,
        'onlyExternal' => FALSE,
        'threshold' => 0,
        'touchMoveStopPropagation' => TRUE,
        'iOSEdgeSwipeDetection' => FALSE,
        'iOSEdgeSwipeThreshold' => 20,
      ],
      'resistance_clicks' => [
        // Touch resistance.
        'resistance' => TRUE,
        'resistanceRatio' => 0.85,
        // Clicks.
        'preventClicks' => TRUE,
        'preventClicksPropagation' => TRUE,
        'slideToClickedSlide' => FALSE,
      ],
      // Swiping / no swiping.
      'swiping' => [
        'allowSwipeToPrev' => TRUE,
        'allowSwipeToNext' => TRUE,
        'noSwiping' => TRUE,
        'noSwipingClass' => 'swiper-no-swiping',
        'swipeHandler' => NULL,
      ],
      // Pagination.
      'pagination_wrapper' => [
        'pagination' => '',
        'paginationType' => 'bullets',
        'paginationHide' => TRUE,
        'paginationClickable' => FALSE,
        'paginationElement' => 'span',
        'paginationBulletRender' => NULL,
        'paginationFractionRender' => NULL,
        'paginationProgressRender' => NULL,
        'paginationCustomRender' => NULL,
      ],
      // Scrollbar.
      'scrollbar_wrapper' => [
        'scrollbar' => '',
        'scrollbarHide' => TRUE,
        'scrollbarDraggable' => FALSE,
        'scrollbarSnapOnRelease' => FALSE,
      ],
      // Accessibility.
      'accessibility' => [
        'a11y' => FALSE,
        'prevSlideMessage' => $this->t('Previous slide'),
        'nextSlideMessage' => $this->t('Next slide'),
        'firstSlideMessage' => $this->t('This is the first slide'),
        'lastSlideMessage' => $this->t('This is the last slide'),
        'paginationBulletMessage' => $this->t('Go to slide {{index}}'),
      ],
      // Keyboard / mousewheel.
      'keyboard' => [
        'keyboardControl' => FALSE,
        'mousewheelControl' => FALSE,
        'mousewheelForceToAxis' => FALSE,
        'mousewheelReleaseOnEdges' => FALSE,
        'mousewheelInvert' => FALSE,
        'mousewheelSensitivity' => 1,
      ],
      // Images.
      'images' => [
        'preloadImages' => TRUE,
        'updateOnImagesReady' => TRUE,
//        'lazyLoading' => FALSE,
//        'lazyLoadingInPrevNext' => FALSE,
//        'lazyLoadingInPrevNextAmount' => 1,
//        'lazyLoadingOnTransitionStart' => FALSE,
      ],
      'loop_control_observer' => [
        // Loop.
        'loop' => FALSE,
        'loopAdditionalSlides' => 0,
        'loopedSlides' => NULL,
        // Control
        // 'control' => '', // Named reference to Swiper instances.
        'controlInverse' => FALSE,
        'controlBy' => 'slide',
        // Observer.
        'observer' => FALSE,
        'observeParents' => FALSE,
      ],
      // Callbacks.
      'callbacks' => [
        'runCallbacksOnInit' => TRUE,
        'onInit' => '',
        'onSlideChangeStart' => '',
        'onSlideChangeEnd' => '',
        'onSlideNextStart' => '',
        'onSlideNextEnd' => '',
        'onSlidePrevStart' => '',
        'onSlidePrevEnd' => '',
        'onTransitionStart' => '',
        'onTransitionEnd' => '',
        'onTouchStart' => '',
        'onTouchMove' => '',
        'onTouchMoveOpposite' => '',
        'onSliderMove' => '',
        'onTouchEnd' => '',
        'onClick' => '',
        'onTap' => '',
        'onDoubleTap' => '',
        'onImagesReady' => '',
        'onProgress' => '',
        'onReachBeginning' => '',
        'onReachEnd' => '',
        'onDestroy' => '',
        'onSetTranslate' => '',
        'onSetTransition' => '',
        'onAutoplay' => '',
        'onAutoplayStart' => '',
        'onAutoplayStop' => '',
//        'onLazyImageLoad' => '',
//        'onLazyImageReady' => '',
        'onPaginationRendered' => '',
      ],
      // Namespace.
      'namespace' => [
        'slideClass' => 'swiper-slide',
        'slideActiveClass' => 'swiper-slide-active',
        'slideVisibleClass' => 'swiper-slide-visible',
        'slideDuplicateClass' => 'swiper-slide-duplicate',
        'slideNextClass' => 'swiper-slide-next',
        'slidePrevClass' => 'swiper-slide-prev',
        'wrapperClass' => 'swiper-wrapper',
        'bulletClass' => 'swiper-pagination-bullet',
        'bulletActiveClass' => 'swiper-pagination-bullet-active',
        'paginationHiddenClass' => 'swiper-pagination-hidden',
        'paginationCurrentClass' => 'swiper-pagination-current',
        'paginationTotalClass' => 'swiper-pagination-total',
        'paginationProgressbarClass' => 'swiper-pagination-progressbar',
        'buttonDisabledClass' => 'swiper-button-disabled',
      ],
    ];
  }

}
