<?php

/**
 * @file
 * Contains \Drupal\jcarousel\Plugin\views\style\jcarousel.
 */

namespace Drupal\jcarousel\Plugin\views\style;

use Drupal\jcarousel\jCarouselSkinsManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Style plugin to render each item in a grid cell.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "jcarousel",
 *   title = @Translation("jCarousel"),
 *   help = @Translation("Displays rows in a jCarousel."),
 *   theme = "views_view_jcarousel",
 *   display_types = {"normal"}
 * )
 */
class jcarousel extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * jCarousel Skin Manager.
   *
   * @var \Drupal\jcarousel\jCarouselSkinsManager
   */
  protected $skinsManager;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\jcarousel\jCarouselSkinsManager $skins_manager
   *   Jcarousel Skin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, jCarouselSkinsManager $skins_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->skinsManager = $skins_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jcarousel.skins.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['wrap'] = ['default' => NULL];
    $options['skin'] = ['default' => 'default'];
    $options['visible'] = ['default' => NULL];
    $options['responsive'] = ['default' => 0];
    $options['scroll'] = ['default' => ''];
    $options['auto'] = ['default' => 0];
    $options['autoPause'] = ['default' => '1'];
    $options['animation'] = ['default' => ''];
    $options['start'] = ['default' => '1'];
    $options['easing'] = ['default' => NULL];
    $options['vertical'] = ['default' => FALSE];
    $options['navigation'] = ['default' => ''];
    $options['swipe'] = ['default' => TRUE];
    $options['draggable'] = ['default' => TRUE];
    $options['method'] = ['default' => 'scroll'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Build the list of skins as options.
    $skins = $this->skinsManager->getDefinitions();
    foreach ($skins as $key => $skin) {
      $skins[$key] = $skin['label'];
    }
    $skins[''] = $this->t('None');

    // Number of options to provide in count-based options.
    $start_range = range(-10, 10);
    $range = array_combine($start_range, $start_range);
    // Remove '0'.
    unset($range[0]);
    $auto_range = ['' => t('Auto')] + array_combine(range(1, 10), range(1, 10));

    $form['description'] = [
      '#type' => 'markup',
      '#value' => '<div class="messages">' . t('The jCarousel style is affected by several other settings within the display. Enable the "Use AJAX" option on your display to have items loaded dynamically. The "Items to display" option will determine how many items are preloaded into the carousel on each AJAX request. Non-AJAX carousels will contain the total number of items set in the "Items to display" option. Carousels may not be used with the "Use pager" option.') . '</div>',
    ];

    $form['wrap'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrap content'),
      '#default_value' => $this->options['wrap'],
      '#description' => $this->t('Specifies whether to wrap at the first/last item (or both) and jump back to the start/end.'),
      '#options' => [
        0 => $this->t('Disabled'),
        'circular' => $this->t('Circular'),
        'both' => $this->t('Both'),
        'last' => $this->t('Last'),
        'first' => $this->t('First'),
      ],
    ];
    $form['skin'] = [
      '#type' => 'select',
      '#title' => $this->t('Skin'),
      '#default_value' => $this->options['skin'],
      '#options' => $skins,
      '#description' => $this->t('Skins may be provided by other modules. Set to "None" if your theme includes carousel theming directly in style.css or another stylesheet. "None" does not include any built-in navigation, arrows, or positioning at all.'),
    ];
    $form['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive (number of items)'),
      '#default_value' => $this->options['responsive'],
      '#description' => $this->t('Select this option to have the carousel automatically adjust the number of visible items and the number of items to scroll at a time based on the available width.') . ' <strong>' . t('Changing this option will override the "Visible" and "Scroll" options and set carousel orientation to "horizontal".') . '</strong>',
    ];
    $form['visible'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of visible items'),
      '#options' => $auto_range,
      '#default_value' => $this->options['visible'],
      '#description' => $this->t('Set an exact number of items to show at a time. It is recommended to leave set this to "auto", in which the number of items will be determined automatically by the space available to the carousel.') . ' <strong>' . t('Changing this option will override "width" properties set in your CSS.') . '</strong>',
    ];
    $form['scroll'] = [
      '#type' => 'select',
      '#title' => t('Scroll'),
      '#description' => t('The number of items to scroll at a time. The "auto" setting scrolls all the visible items.'),
      '#options' => $auto_range,
      '#default_value' => $this->options['scroll'],
    ];
    $form['auto'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auto-scroll after'),
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => $this->options['auto'],
      '#field_suffix' => ' ' . $this->t('seconds'),
      '#description' => $this->t('Specifies how many seconds to periodically auto-scroll the content. If set to 0 (default) then autoscrolling is turned off.'),
    ];
    $form['navigation'] = [
      '#type' => 'select',
      '#title' => $this->t('Enable navigation'),
      '#options' => [
        '' => $this->t('None'),
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
      ],
      '#default_value' => $this->options['navigation'],
      '#description' => $this->t('Enable a clickable navigation list to jump straight to a given page.'),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#parents' => ['style_options'],
    ];
    $form['advanced']['animation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation speed'),
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => $this->options['animation'],
      '#description' => $this->t('The speed of the scroll animation as string in jQuery terms ("slow"  or "fast") or milliseconds as integer (See <a href="http://api.jquery.com/animate/">jQuery Documentation</a>).'),
    ];
    $form['advanced']['easing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing effect'),
      '#size' => 10,
      '#maxlength' => 128,
      '#default_value' => $this->options['easing'],
      '#description' => $this->t('The name of the easing effect that you want to use such as "swing" (the default) or "linear". See list of options in the <a href="http://api.jquery.com/animate/">jQuery Documentation</a>.'),
    ];
    $form['advanced']['start'] = [
      '#type' => 'select',
      '#title' => $this->t('Start position'),
      '#description' => $this->t('The item that will be shown as the first item in the list upon loading. Useful for starting a list in the middle of a set. A negative value allows choosing an item in the end, e.g. -1 is the last item.'),
      '#options' => $range,
      '#default_value' => $this->options['start'],
    ];
    $form['advanced']['autoPause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause auto-scroll on hover'),
      '#description' => $this->t('If auto-scrolling, pause the carousel when the user hovers the mouse over an item.'),
      '#default_value' => $this->options['autoPause'],
    ];
    $form['advanced']['vertical'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Vertical'),
      '#description' => $this->t('Specifies wether the carousel appears in horizontal or vertical orientation. Changes the carousel from a left/right style to a up/down style carousel. Defaults to horizontal.'),
      '#default_value' => $this->options['vertical'],
    ];
    $form['advanced']['swipe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable jcarouselSwipe plugin'),
      '#description' => $this->t('Adds support user-friendly swipe gestures.'),
      '#default_value' => $this->options['swipe'],
    ];
    $form['advanced']['draggable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('On/off dragging items on swipe'),
      '#default_value' => $this->options['draggable'],
    ];
    $form['advanced']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Swipe method'),
      '#options' => [
        'scroll' => $this->t('Scroll'),
        'scrollIntoView' => $this->t('ScrollIntoView'),
      ],
      '#default_value' => $this->options['method'],
      '#description' => $this->t('What method should used to switch slides.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $errors = parent::validateOptionsForm($form, $form_state);
    $display = $this->view->getDisplay();
    $pager = $display->getPlugin('pager');
    if ($pager->usePager() && !in_array($pager->getPluginId(), ['none', 'jcarousel'])) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    parent::preRender($result);

    $skin = !empty($this->options['skin']) ? $this->options['skin'] : 'default';
    $this->view->element['#attached']['library'][] = 'jcarousel/jcarousel';
    $this->view->element['#attached']['library'][] = 'jcarousel/jcarousel.swipe';
    $this->view->element['#attached']['library'][] = 'jcarousel/drupal.jcarousel';
    $this->view->element['#attached']['library'][] = 'jcarousel/skin.' . $skin;
  }

}
