<?php

namespace Drupal\shuffle\Plugin\Field\FieldFormatter;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\core\Render\Renderer;

/**
 * Plugin implementation of the 'field_shuffle' formatter.
 *
 * @FieldFormatter(
 *   id = "field_shuffle",
 *   label = @Translation("Shuffle formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ShuffleFieldFormatter extends ImageFormatter implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\core\Render\Renderer
   */
  protected $renderer;

  /**
   * A PHPTransliteration object.
   *
   * @var \Drupal\Component\Transliteration\PhpTransliteration
   */
  protected $transliteration;

  /**
   * Constructs an ShuffleFieldFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Component\Transliteration\PhpTransliteration $transliteration
   *   A PhpTransliteration object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, Renderer $renderer, PhpTransliteration $transliteration) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->renderer = $renderer;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
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
      $container->get('renderer'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'popup' => 0,
      'popup_image_style' => '',
      'gallery_type' => 'all_items',
      'speed' => '200',
      'easing' => 'ease-out',
      'sizerMethod' => 'sizer',
      'columnWidth' => '250',
      'gutterWidth' => '5',
      'sizer' => '.shuffle-item',
      'filter' => 0,
      'removetitle' => 1,
      'useAllFilter' => 0,
      'caption' => 0,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::settingsForm($form, $form_state);
    $field_name = $this->fieldDefinition->getName();

    $element['speed'] = array(
      '#title' => $this->t('Speed'),
      '#description' => $this->t('The transition/animation speed (in milliseconds).'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->getSetting('speed'),
    );

    $element['easing'] = array(
      '#title' => $this->t('Easing'),
      '#description' => $this->t('The CSS easing function to use for transition.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->getSetting('easing'),
    );

    $element['sizerMethod'] = array(
      '#title' => $this->t('SizerMethod'),
      '#description' => $this->t('Use a method to determine the size of columns and gutters. Use a complete CSS class name (sizer) or use a fixed column and gutter width (manual)'),
      '#type' => 'select',
      '#options' => ['sizer' => $this->t('Sizer'), 'manual' => $this->t('Manual')],
      '#default_value' => $this->getSetting('sizerMethod'),
    );

    $element['sizer'] = array(
      '#title' => $this->t('Sizer'),
      '#description' => $this->t('Use a element to determine the size of columns and gutters. Use a complete CSS class name with the point (example: .shuffle-item, or .my-custom-class).'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('sizer'),
      '#states' => array(
        'visible' => array(
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][sizerMethod]"]' => array('value' => 'sizer'),
        ),
      ),
    );

    $element['columnWidth'] = array(
      '#title' => $this->t('Column Width (in px)'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('columnWidth'),
      '#states' => array(
        'visible' => array(
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][sizerMethod]"]' => array('value' => 'manual'),
        ),
      ),
    );

    $element['gutterWidth'] = array(
      '#title' => $this->t('Gutter Width (in px)'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('gutterWidth'),
      '#states' => array(
        'visible' => array(
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][sizerMethod]"]' => array('value' => 'manual'),
        ),
      ),
    );

    $element['filter'] = array(
      '#title' => $this->t('Use the image title text as filter'),
      '#description' => $this->t('Use the title as filters for images.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('filter'),
    );

    $element['removetitle'] = array(
      '#title' => $this->t('Remove title from the img tag if using it as filter'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('removetitle'),
      '#states' => array(
        'visible' => array(
          'input[name="fields[' . $field_name . '][settings_edit_form][settings][filter]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $element['useAllFilter'] = array(
      '#title' => $this->t('Show "All" filter'),
      '#description' => $this->t('Adds a static "All" filter that is automatically selected when deselecting custom filters.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('useAllFilter'),
    );

    $element['caption'] = array(
      '#title' => $this->t('Use image alt text as caption'),
      '#description' => $this->t('Use the alt as caption for images.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('caption'),
    );

    // Magnific popup integration.
    if (!function_exists('libraries_detect')) {
      module_load_include('module', 'libraries');
    }
    if (libraries_detect('magnific-popup')) {

      $element['popup'] = array(
        '#title' => $this->t('Enable Magnific popup'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('popup'),
      );

      $image_styles = image_style_options(FALSE);
      $element['popup_image_style'] = [
        '#title' => t('Magnific popup image style'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('popup_image_style'),
        '#empty_option' => t('None (original image)'),
        '#options' => $image_styles,
        '#states' => array(
          'visible' => array(
            'input[name="fields[' . $field_name . '][settings_edit_form][settings][popup]"]' => array('checked' => TRUE),
          ),
        ),
      ];

      $element['gallery_type'] = [
        '#title' => t('Gallery Type'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('gallery_type'),
        '#options' => $this->getGalleryTypes(),
        '#states' => array(
          'visible' => array(
            'input[name="fields[' . $field_name . '][settings_edit_form][settings][popup]"]' => array('checked' => TRUE),
          ),
        ),
      ];

    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Implement settings summary.
    $summary = parent::settingsSummary();

    $speed = $this->getSetting('speed');
    if ($speed) {
      $summary[] = t('Transition speed: @speed', array('@speed' => $speed));
    }

    $easing = $this->getSetting('easing');
    if ($easing) {
      $summary[] = t('Easing function: @easing', array('@easing' => $easing));
    }

    $sizermethod = $this->getSetting('sizerMethod');
    if ($sizermethod == 'sizer') {
      $summary[] = t('Sizer Method width class: @sizer',
        array('@sizer' => $this->getSetting('sizer')));
    }

    if ($sizermethod == 'manual') {
      $summary[] = t('Column width : @columnWidth and gutter width : @gutterWidth',
        array(
          '@columnWidth' => $this->getSetting('columnWidth'),
          '@gutterWidth' => $this->getSetting('gutterWidth'),
        )
      );
    }

    $filter = $this->getSetting('filter');
    $removetitle = $this->getSetting('removetitle');
    if ($filter && !$removetitle) {
      $summary[] = t('Using title value as filters');
    }

    if ($filter && $removetitle) {
      $summary[] = t('Using title value as filters (and remove title from img tag)');
    }

    $useAllFilter = $this->getSetting('useAllFilter');
    if ($useAllFilter) {
      $summary[] = t('With "All" filter');
    }

    $caption = $this->getSetting('caption');
    if ($caption) {
      $summary[] = t('Using alt value as caption');
    }

    if ($this->getSetting('pop')) {
      $image_styles = image_style_options(FALSE);
      $popup_image_style = $this->getSetting('popup_image_style');
      $summary[] = t('Magnific popup enabled. Image style: @popup_style', [
        '@popup_style' => isset($image_styles[$popup_image_style]) ? $popup_image_style : 'Original Image',
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $shuffle_items = [];

    if ($this->getSetting('popup')) {
      $contents = $this->preparePopupElements($items, $langcode);
    }
    else {
      $contents = parent::viewElements($items, $langcode);
    }

    // Early opt-out if the field is empty.
    if (empty($contents)) {
      return [];
    }

    $filters_list = [];

    foreach ($contents as $delta => $content) {
      /** @var \Drupal\Core\Template\Attribute $attributes */
      $attributes = $this->newAttribute();
      $attributes->addClass(['shuffle-item']);

      $shuffle_items[$delta]['content'] = $content;

      if ($this->getSetting('caption')) {
        $shuffle_items[$delta]['caption'] = $this->getFieldText($content['#item'], 'alt');
      }

      if ($this->getSetting('filter')) {
        if ($filter = $this->getFieldText($content['#item'], 'title')) {
          // Store the filter renderable array in the item renderable array for
          // use it eventually in the template.
          $shuffle_items[$delta]['filter'] = $filter;
          // We need the filter as a string to add it in the data-group.
          $filter = trim(strip_tags($this->renderer->render($filter)));
          // Transliterate and encode in a Json object.
          $filter_key = (array) strtolower(trim($this->transliteration->transliterate($filter, $langcode)));
          $filter_key = Json::encode($filter_key);
          $attributes->setAttribute('data-groups', $filter_key);
          // Store all filters name in a list.
          $filters_list = array_unique(array_merge($filters_list, array($filter)));
        }

        if ($this->getSetting('removetitle')) {
          if (isset($content['#item']->title) && is_string($content['#item']->title)) {
            $content['#item']->title = '';
          }
        }
      }

      $shuffle_items[$delta]['attributes'] = $attributes;

    }

    // Prepare the list of filter options.
    $filter_options = [];
    if ($filters_list) {
      foreach ($filters_list as $key => $value) {
        $filter_options[$key]['#markup'] = $value;
        $filter_options[$key]['#wrapper_attributes'] = array(
          'class' => array('shuffle-filters-item'),
          'data-group' => array(strtolower(trim($this->transliteration->transliterate($value, $langcode)))),
        );
      }

      // Add a "show all" filter.
      if ($this->getSetting('useAllFilter')) {
        $all_filter = [];
        $all_filter['#markup'] = t('All');
        $all_filter['#wrapper_attributes'] = array(
          'class' => array('shuffle-filters-item active'),
          'data-group' => 'all',
        );
        array_unshift($filter_options, $all_filter);
      }

      $filter_options = array(
        '#theme' => 'item_list',
        '#items' => $filter_options,
        '#attributes' => array(
          'class' => array(
            'shuffle-filters',
            'filter-options',
            'list-inline',
            'inline',
          ),
        ),
      );
    }

    $shuffle_id = Html::cleanCssIdentifier('field-shuffle-' . $entity_type_id . '-' . $bundle . '-' . $field_name . '-' . $this->viewMode);
    $wrapper_class = $this->newAttribute();
    $wrapper_class->addClass($shuffle_id);

    // Preparing the js variables and adding the js to our display.
    $settings['speed'] = $this->getSetting('speed');
    $settings['easing'] = $this->getSetting('easing');
    $settings['sizerMethod'] = $this->getSetting('sizerMethod');
    $settings['useAllFilter'] = $this->getSetting('useAllFilter');

    if ($this->getSetting('sizerMethod') == 'sizer') {
      $settings['sizer'] = ($this->getSetting('sizer')) ? $this->getSetting('sizer') : '.shuffle-item';
    }
    if ($this->getSetting('sizerMethod') == 'manual') {
      $settings['columnWidth'] = $this->getSetting('columnWidth');
      $settings['gutterWidth'] = $this->getSetting('gutterWidth');
    }

    $elements[0] = [
      '#theme' => 'field_shuffle',
      '#items' => $shuffle_items,
      '#filter_options' => $filter_options,
      '#view_mode' => $this->viewMode,
      '#wrapper_class' => $wrapper_class,
      '#settings' => $this->getSettings(),
      '#entity' => $entity,
      '#field_name' => $field_name,
      '#attached' => [
        'library' => [
          'shuffle/shuffle_plugin',
          'shuffle/shuffle',
        ],
        'drupalSettings' => [
          'shuffle' => [
            $shuffle_id => $settings,
          ],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $elements['#attributes']['class'][] = 'field-shuffle-display';
    if ($this->getSetting('popup')) {
      $gallery_type = $this->getSetting('gallery_type');
      $elements['#attributes']['class'][] = 'mfp-field';
      $elements['#attributes']['class'][] = 'mfp-' . Html::cleanCssIdentifier($gallery_type);
    }
    return $elements;
  }

  /**
   * Utility to get sanitized text directly from a field item.
   *
   * This method will attempt to extract text, in a format safe for display,
   * from the data contained within a file item.
   *
   * @param FieldItemInterface $item
   *   A field item implementing FieldItemInterface.
   * @param string $source
   *   The source property that contains the text we want to extract. This
   *   property may be part of the item metadata or a property on a referenced
   *   entity.
   *
   * @return array
   *   renderable array processed_text.
   */
  protected function getFieldText(FieldItemInterface $item, $source) {
    // If the text source is the filename we need to get the data from the
    // item's related file entity.
    if ($source == 'filename' && isset($item->entity)) {
      $entity_properties = $item->entity->toArray();
      if (isset($entity_properties[$source])) {
        // A processed_text render array will utilize text filters on rendering.
        $text_to_build = array('#type' => 'processed_text', '#text' => $item->entity->get($source)->value);
        return $text_to_build;
      }
    }
    // Otherwise we are dealing with an item value (such as image alt or title
    // text). For some reason alt and title values are not always set as
    // properties on items, so we can't use $item->get(). However, calling the
    // variable directly triggers __get(), which works for BOTH properties and
    // plain values.
    if (isset($item->{$source}) && is_string($item->{$source})) {
      // A processed_text render array will utilize text filters on rendering.
      $text_to_build = array('#type' => 'processed_text', '#text' => $item->{$source});
      return $text_to_build;
    }

    return [];
  }

  /**
   * This function wraps images with a link to the image style for popup.
   *
   * See Magnific popup module.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   A field item list.
   * @param string $langcode
   *   The langcode.
   *
   * @return array $elements
   *   A renderable array for images with magnific popup integration.
   */
  protected function preparePopupElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $image_style = $this->getSetting('image_style');
    $popup_image_style = $this->getSetting('popup_image_style');
    $files = $this->getEntitiesToView($items, $langcode);

    foreach ($files as $delta => $file) {
      $image_uri = $file->getFileUri();
      $popup_image_path = !empty($popup_image_style) ? ImageStyle::load($popup_image_style)->buildUrl($image_uri) : $image_uri;
      // Depending on the outcome of https://www.drupal.org/node/2622586,
      // Either a class will need to be added to the $url object,
      // Or a custom theme function might be needed to do so.
      // For the time being, 'a' is used as the delegate in magnific-popup.js.
      $url = Url::fromUri(file_create_url($popup_image_path));
      $item = $file->_referringItem;
      $item_attributes = $file->_attributes;
      unset($file->_attributes);

      $item_attributes['class'][] = 'mfp-thumbnail';

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style,
        '#url' => $url,
        '#attached' => [
          'library' => [
            'shuffle/magnific_popup_plugin',
            'shuffle/magnific_popup',
          ],
        ],
      ];

    }

    return $elements;
  }

  /**
   * Get an array of gallery types.
   *
   * @return array
   *   An array of gallery types for use in display settings.
   */
  protected function getGalleryTypes() {
    return [
      'all_items' => $this->t('Gallery: All Items Displayed'),
      'separate_items' => $this->t('No Gallery: Display Each Item Separately'),
    ];
  }

  /**
   * Get an Attribute object.
   *
   * @return object
   *   an Attribut object.
   */
  protected function newAttribute() {
    return new Attribute();
  }

}
