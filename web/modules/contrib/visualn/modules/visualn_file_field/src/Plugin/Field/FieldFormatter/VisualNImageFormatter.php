<?php

namespace Drupal\visualn_file_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\Helpers\VisualN;

/**
 * Plugin implementation of the 'visualn_image' formatter.
 *
 * @FieldFormatter(
 *   id = "visualn_image",
 *   label = @Translation("VisualN image"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class VisualNImageFormatter extends ImageFormatter {
//class VisualNImageFormatter extends FormatterBase {

  const RAW_RESOURCE_FORMAT = 'visualn_generic_data_array';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'visualn_style_id' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $visualn_styles = visualn_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );

    $visualn_style_id = $this->getSetting('visualn_style_id');

    // @todo: choose a more explicit formatter
    $ajax_wrapper_id = 'visualn-image-formatter-drawer-config-form-ajax-wrapper';
    $form['visualn_style_id'] = [
      '#type' => 'select',
      '#title' => t('VisualN style'),
      '#options' => $visualn_styles,
      '#default_value' => $visualn_style_id,
      '#description' => t('Default style for the data to render.'),
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer visualn styles')
        '#access' => TRUE
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select visualization style -'),
    ];
    $form['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processDrawerContainerSubform']],
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];
    //$form['drawer_container']['#stored_configuration'] = $this->getSetting('drawer_config');
    // @todo: basically just this->getSettings() can be passed
    $form['drawer_container']['#stored_configuration'] = [
      'visualn_style_id' => $this->getSetting('visualn_style_id'),
      'drawer_config' => $this->getSetting('drawer_config'),
      'drawer_fields' => $this->getSetting('drawer_fields'),
    ];

    return $form;
    //return $form + parent::settingsForm($form, $form_state);
  }


  /**
   * Return drawer configuration form via ajax request at style change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }


  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'visualn_style_id' => $stored_configuration['visualn_style_id'],
      'drawer_config' => $stored_configuration['drawer_config'],
      'drawer_fields' => $stored_configuration['drawer_fields'],
    ];

    $element = VisualNFormsHelper::processDrawerContainerSubform($element, $form_state, $form, $configuration);

    // Add a process callback to convert drawer_fields mapping textfields into select
    // lists since the number and names of keys used by image field to provide data are
    // known and unchangeable.
    // @see VisualNFormsHelper::processDrawerContainerSubform()
    // @see ImageFieldReaderDrawingFetcher::processDrawerContainerSubform()
    $style_element_parents = array_slice($element['#parents'], 0, -1);
    $visualn_style_id = $form_state->getValue(array_merge($style_element_parents, ['visualn_style_id']));
    if (!$visualn_style_id) {
      return $element;
    }
    $drawer_container_key = $visualn_style_id;
    // $element[$drawer_container_key]['drawer_fields']['#process'] is supposed to be always set
    // if $visualn_style_id is defined, see VisualNFormsHelper::processDrawerContainerSubform()
    if ($element[$drawer_container_key]['drawer_fields']['#process']) {
      $element[$drawer_container_key]['drawer_fields']['#process'][] = [get_called_class(), 'processDrawerFieldsSubform'];
    }

    return $element;
  }

  /**
   * Replace drawer_fields configuration textfields with select lists.
   */
  public static function processDrawerFieldsSubform(array $element, FormStateInterface $form_state, $form) {

    $drawer_fields = $element['#drawer_fields'];

    // @todo: check for additional data keys, e.g. size values though some of them
    //   could be considered secure and shouldn't be exposed for every case
    // Image field provides data with a fixed set of data keys
    $data_keys_options = [
      'url' => 'url',
      'title' => 'title',
      'alt' => 'alt',
    ];

    // replace textfields with selects
    foreach (Element::children($element) as $key) {
      $element[$key]['field'] = [
        '#type' => 'select',
        '#options' => $data_keys_options,
        '#empty_option' => t('- None -'),
        '#default_value' => isset($drawer_fields[$key]) ? $drawer_fields[$key] : '',
      ];
    }

    return $element;
  }



  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return parent::settingsSummary();
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $visualn_style_id = $this->getSetting('visualn_style_id');
    if (empty($visualn_style_id)) {
      return $elements;
    }

    $image_items = $elements;

    // wrap elements into a div so that the initial image contents could be hidden by the formatter handler script
    $fuid = substr(\Drupal::service('uuid')->generate(), 0, 4);
    $image_items_wrapper_id = 'visualn-image-formatter-html-selector--' . $fuid;

    $image_items = [
      '#prefix' => '<div id="' . $image_items_wrapper_id . '">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => [
          'visualn_file_field/visualn-image-formatter-handler'
        ],
        'drupalSettings' => [
          'visualnFile' => ['imageFormatterItemsWrapperId' => [$fuid => $image_items_wrapper_id]],
        ],
      ],
    ] + $image_items;

    // keep original image items for fallback bahaviour in case of disabled javascript
    $elements = [
      '#image_items' => $image_items,
    ];


    // @see ImageFormatter::viewElements()
    // @todo: try to get urls list from $elements
    //$deltas = Element::children($elements);

    $data = [];

    $files = $this->getEntitiesToView($items, $langcode);
    foreach ($files as $delta => $file) {
      $image_uri = $file->getFileUri();
      // @todo: see the note in ImageFormatter::viewElements() relating a bug
      //$url = Url::fromUri(file_create_url($image_uri));
      $url = file_create_url($image_uri);

      // @todo: some other properties could be added, e.g. size etc.
      //   though some of them may be considered secure and shouldn't be added in every
      //   case (e.g. for js data it would be always exposed) and thus should be configured
      $data[] = [
        'url' => $url,
        'title' => $items->get($delta)->get('title')->getString(),
        'alt' => $items->get($delta)->get('alt')->getString(),
      ];
    }

    $drawer_config = $this->getSetting('drawer_config');
    $drawer_fields = $this->getSetting('drawer_fields');

    $raw_resource_plugin_id = static::RAW_RESOURCE_FORMAT;
    $raw_input = [
      'data' => $data,
    ];
    // @todo: add service in ::create() method
    $resource =
      \Drupal::service('plugin.manager.visualn.raw_resource_format')
      ->createInstance($raw_resource_plugin_id, [])
      ->buildResource($raw_input);

    // Get drawing build
    $build = \Drupal::service('visualn.builder')->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields);

    // @todo: html_selector should be connected inside '.field__items' in order
    //    to be able to use quick edit feature


    // field template seems to ignore anything added to the $elements and renders only items (see field.html.twig)
    // @todo: check inline_template solution implemented for other visualn fields formatters



    $elements['#theme'] = 'visualn_image_formatter';
    $elements = [
      '#visualn_drawing_build' => $build,
    ] + $elements;

    return $elements;
  }

}
