<?php

namespace Drupal\field_slideshow\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\field_slideshow\FieldSlideshowPagerPluginManager;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'slideshow' formatter.
 *
 * @FieldFormatter(
 *   id = "slideshow",
 *   label = @Translation("Slideshow"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class FieldSlideshow extends ImageFormatter implements ContainerFactoryPluginInterface {

  /**
   * ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * FieldSlideshowPager manager.
   *
   * @var \Drupal\field_slideshow\FieldSlideshowPagerPluginManager
   */
  protected $pagerManager;

  /**
   * FieldSlideshow constructor.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Settings.
   * @param string $label
   *   Label.
   * @param string $view_mode
   *   View mode.
   * @param array $third_party_settings
   *   Third part settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   Image style storage.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   * @param \Drupal\field_slideshow\FieldSlideshowPagerPluginManager $pagerManager
   *   PagerManager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountInterface $current_user,
    EntityStorageInterface $image_style_storage,
    ModuleHandler $moduleHandler,
    Renderer $renderer,
    FieldSlideshowPagerPluginManager $pagerManager
  ) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
    $this->pagerManager = $pagerManager;
  }

  /**
   * Use symfony dependency injection container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return \Drupal\Core\Plugin\ContainerFactoryPluginInterface|\Drupal\field_slideshow\Plugin\Field\FieldFormatter\FieldSlideshow|\Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter
   *   Container.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('plugin.manager.field_slideshow_pager')
    );
  }

  /**
   * Default settings.
   *
   * @return array
   *   Array settings.
   */
  public static function defaultSettings() {
    return [
      'slideshow' => [
        'fx' => 'fade',
        'allowWrap' => TRUE,
        'autoHeight' => 0,
        'delay' => 0,
        'hideNonActive' => TRUE,
        'loader' => 'false',
        'loop' => 0,
        'pauseOnHover' => FALSE,
        'paused' => FALSE,
        'random' => FALSE,
        'reverse' => FALSE,
        'speed' => 500,
        'startingSlide' => 0,
        'swipe' => FALSE,
        'sync' => TRUE,
        'timeout' => 4000,
      ],
      'slideshow_pager' => [
        'pager' => [
          'after' => 'after',
        ],
        'pager_type' => 'thumbnails',
        'controls' => TRUE,
      ],
      'colorbox_image_style' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * Settings summary.
   *
   * @return array|string[]
   *   Summary array.
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $image_styles = image_style_options(FALSE);
    $slideshow = array_filter($this->getSetting('slideshow'));
    $pager = array_filter($this->getSetting('slideshow_pager'));
    $colorbox = $this->getSetting('colorbox_image_style');
    $colorbox_link = $this->getSetting('image_link');

    if ($colorbox && $colorbox_link == 'colorbox') {
      $summary[] = 'Colorbox: ' . $image_styles[$colorbox];
    }

    foreach ($slideshow as $key => $value) {
      if ($value == 1) {
        $value = 'TRUE';
      }
      $summary[] = ucfirst($key) . ': ' . $value;
    }

    if (isset($pager['pager']) && array_filter($pager['pager'])) {
      $summary[] = 'Pager:' . implode(',', array_filter($pager['pager']));
    }

    if (isset($pager['controls'])) {
      $summary[] = 'Controls';
    }

    return $summary;
  }

  /**
   * Settings form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array|mixed
   *   Form array.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    // Colorbox support.
    if ($this->moduleHandler->moduleExists('colorbox')) {
      $form['image_link']['#options']['colorbox'] = 'Colorbox';
      $form['colorbox_image_style'] = [
        '#title'          => $this->t('Colorbox image style'),
        '#type'           => 'select',
        '#default_value'  => $this->getSetting('colorbox_image_style'),
        '#empty_option'   => $this->t('None (original image)'),
        '#options'        => image_style_options(FALSE),
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][image_link]"]' => [
              'value' => 'colorbox',
            ],
          ],
        ],
      ];

    }

    $form['slideshow'] = [
      '#type' => 'details',
      '#title' => $this->t('Slideshow settings'),
      '#open' => FALSE,
    ];

    $form['slideshow']['fx'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition'),
      '#required' => TRUE,
      '#options' => $this->getTransitions(),
      '#default_value' => $this->getSetting('slideshow')['fx'],
    ];

    $form['slideshow']['allowWrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow wrap'),
      '#default_value' => $this->getSetting('slideshow')['allowWrap'],
    ];

    $form['slideshow']['autoHeight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auto height'),
      '#default_value' => $this->getSetting('slideshow')['autoHeight'],
    ];

    $form['slideshow']['delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('The number of milliseconds to add onto, or substract from, the time before the first slide transition occurs.'),
      '#default_value' => $this->getSetting('slideshow')['delay'],
    ];

    $form['slideshow']['hideNonActive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide non active'),
      '#description' => $this->t('Determines whether or not Cycle2 hides the inactive slides.'),
      '#default_value' => $this->getSetting('slideshow')['hideNonActive'],
    ];

    $form['slideshow']['loader'] = [
      '#type' => 'select',
      '#title' => $this->t('Loader'),
      '#options' => [
        'true' => 'True',
        'false' => 'False',
        'wait' => 'Wait',
      ],
      '#default_value' => $this->getSetting('slideshow')['loader'],
    ];

    $form['slideshow']['loop'] = [
      '#type' => 'number',
      '#title' => $this->t('Loop'),
      '#description' => $this->t('The number of times an auto-advancing slideshow should loop before terminating. If the value is less than 1 then the slideshow will loop continuously. Set to 1 to loop once, etc. Setting the allow-wrap option to false will override looping.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $this->getSetting('slideshow')['loop'],
    ];

    $form['slideshow']['pauseOnHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#description' => $this->t('If true an auto-running slideshow will be paused while the mouse is over the slideshow.'),
      '#default_value' => $this->getSetting('slideshow')['pauseOnHover'],
    ];

    $form['slideshow']['paused'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Paused'),
      '#description' => $this->t('If true the slideshow will begin in a paused state.'),
      '#default_value' => $this->getSetting('slideshow')['paused'],
    ];

    $form['slideshow']['random'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Random'),
      '#description' => $this->t("If true the order of the slides will be randomized. This only effects slides that are initially in the markup, not slides added via the add command or via Cycle2's image loader functionality."),
      '#default_value' => $this->getSetting('slideshow')['random'],
    ];

    $form['slideshow']['reverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse'),
      '#description' => $this->t('If true the slideshow will proceed in reverse order and transitions that support this option will run a reverse animation.'),
      '#default_value' => $this->getSetting('slideshow')['reverse'],
    ];

    $form['slideshow']['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Speed'),
      '#description' => $this->t('The speed of the transition effect in milliseconds.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $this->getSetting('slideshow')['speed'],
    ];

    $form['slideshow']['startingSlide'] = [
      '#type' => 'number',
      '#title' => $this->t('Starting slide'),
      '#description' => $this->t('The zero-based index of the slide that should be initially displayed.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $this->getSetting('slideshow')['startingSlide'],
    ];

    $form['slideshow']['swipe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Swipe'),
      '#description' => $this->t('Set to true to enable swipe gesture support for advancing the slideshow forward or back. You should downlad cycle2.swipe plugin and place it in /libraries/jquery.cycle2/ directory.'),
      '#default_value' => $this->getSetting('slideshow')['swipe'],
    ];

    $form['slideshow']['sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync'),
      '#description' => $this->t('If true then animation of the incoming and outgoing slides will be synchronized. If false then the animation for the incoming slide will not start until the animation for the outgoing slide completes.'),
      '#default_value' => $this->getSetting('slideshow')['sync'],
    ];

    $form['slideshow']['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Tmeout'),
      '#description' => $this->t('The time between slide transitions in milliseconds.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $this->getSetting('slideshow')['timeout'],
    ];

    $form['slideshow_pager'] = [
      '#type' => 'details',
      '#title' => $this->t('Slideshow pager settings'),
      '#open' => FALSE,
    ];

    $form['slideshow_pager']['pager'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Pager'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
      ],
      '#default_value' => $this->getSetting('slideshow_pager')['pager'],
    ];

    $form['slideshow_pager']['pager_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Pager type'),
      '#options' => $this->getPagerTypes(),
      '#default_value' => $this->getSetting('slideshow_pager')['pager_type'],
    ];

    $form['slideshow_pager']['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Controls'),
      '#default_value' => $this->getSetting('slideshow_pager')['controls'],
    ];

    return $form;
  }

  /**
   * Prepare transitions.
   *
   * @return array
   *   Array with transitions.
   */
  protected function getTransitions() {
    return [
      'fade' => 'fade',
      'fadeout' => 'fadeout',
      'none' => 'none',
      'scrollHorz' => 'scrollHorz',
    ];
  }

  /**
   * Helper function to generate available plugins.
   *
   * @return array
   *   Array of plugins.
   */
  protected function getPagerTypes() {
    $options = $this->pagerManager->getDefinitions();
    $pagers = [];

    foreach ($options as $option) {
      $pagers[$option['id']] = $option['label'];
    }

    return $pagers;
  }

  /**
   * View elements.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Items.
   * @param string $langcode
   *   Langcode.
   *
   * @return array
   *   Rendered array.
   *
   * @throws \Exception
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $itemListInterface = $items;
    $id = Html::getUniqueId('field-slideshow-id');
    $slideshow_settings = $this->getSetting('slideshow');
    $files = $this->getEntitiesToView($items, $langcode);
    $items = parent::viewElements($items, $langcode);
    $pager = NULL;

    if (!count($items)) {
      return [];
    }

    if (count($items) > 1) {
      $pager = $this->getSetting('slideshow_pager');
      $pagerType = $this->pagerManager->createInstance($pager['pager_type']);
      $pager['pager_type'] = $pagerType->viewPager($itemListInterface);
    }

    $output = [];

    // Load cycle2swipe if needed.
    if ($slideshow_settings['swipe']) {
      $libraries[] = 'field_slideshow/field_slideshow.cycle2swipe';
    }

    $libraries[] = 'field_slideshow/field_slideshow.cycle2';

    if ($this->getSetting('image_link') === 'colorbox') {
      $colorbox_style_setting = $this->getSetting('colorbox_image_style');

      if (!empty($colorbox_style_setting)) {
        $image_style = $this->imageStyleStorage->load($colorbox_style_setting);
      }

      $options = [
        'attributes' => [
          'class' => [
            'colorbox',
          ],
          'data-colorbox-gallery' => 'gallery-' . $id,
        ],
      ];

      foreach ($items as $key => $item) {
        // Create colorbox image url with image style.
        $original_url = $url = $files[$key]->getFileUri();
        if (isset($image_style)) {
          $url = $image_style->buildUri($original_url);
          if (!file_exists($url)) {
            $image_style->createDerivative($original_url, $url);
          }
        }

        $url = Url::fromUri(file_create_url($url), $options);

        // Image-formatter.html.twig does not give
        // ability to add url attributes like class.
        $img = $this->renderer->render($items[$key]);
        $link = Link::fromTextAndUrl($img, $url);
        $items[$key] = $link->toString();
      }

      // We can't use dependency injection for this
      // service because colorbox is optional.
      \Drupal::service('colorbox.attachment')->attach($output);
    }

    $output = array_merge_recursive($output, [
      '#theme' => 'field_slideshow',
      '#items' => $items,
      '#pager' => $pager,
      '#id' => $id,
      '#attached' => [
        'library' => $libraries,
        'drupalSettings' => [
          'field_slideshow' => [
            $id => $slideshow_settings,
          ],
        ],
      ],
    ]);

    return $output;
  }

}
