<?php

namespace Drupal\visualn_file_field\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Plugin\GenericDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a 'Image field reader' VisualN drawing fetcher.
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_image_field_reader",
 *  label = @Translation("Image field reader"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class ImageFieldReaderDrawingFetcher extends GenericDrawingFetcherBase {
  // @todo: use standalone fetcher as base to be able to change visualn style for
  //    image field, if not selected try to get drawer config from formatter settigns
  // @todo: add 'current view mode' context' for the case when user doesn't select a visualn style
  //    or add 'default view mode' select into configuration form (or just leave visualn style required)

  const RAW_RESOURCE_FORMAT = 'visualn_generic_data_array';


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image_field' => '',
    ] + parent::defaultConfiguration();
 }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $entity_type = $this->getContextValue('entity_type');
    $bundle = $this->getContextValue('bundle');

    // @todo: this is a temporary solution
    if (empty($entity_type) || empty($bundle)) {
      // @todo: throw an error
      $form['markup'] = [
        '#markup' => t('Entity type or bundle context not set.'),
      ];

      return $form;
    }


    // @todo: here we don't give any direct access to the entity edited
    //    but we can find out whether the entity field has multiple or unlimited delta

    // select file field and maybe delta
    // @todo: maybe select also delta

    $options = ['' => t('- Select -')];
    // @todo: instantiate on create
    $entityManager = \Drupal::service('entity_field.manager');
    $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $bundle);

    foreach ($bundle_fields as $field_name => $field_definition) {
      // filter out base fields
      if ($field_definition->getFieldStorageDefinition()->isBaseField() == TRUE) {
        continue;
      }

      // @todo: move field type into constant
      if ($field_definition->getType() == 'image') {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    // @todo: rename key to image_field_name
    $form['image_field'] = [
      '#type' => 'select',
      '#title' => t('VisualN File field'),
      '#options' => $options,
      // @todo: where to use getConfiguration and where $this->configuration (?)
      //    the same question for other plugin types
      '#default_value' => $this->configuration['image_field'],
      '#description' => t('Select the VisualN File field for the drawing source'),
    ];

    // Attach visualn style select box for the fetcher
    $form += parent::buildConfigurationForm($form, $form_state);

    // Disable "required" behaviour for visualn style - if not selected,
    // try to get drawer config from formatter settings
    //$form['visualn_style_id']['#required'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  // @todo: should be static, review parent class method
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $element = parent::processDrawerContainerSubform($element, $form_state, $form);

    // Add a process callback to convert drawer_fields mapping textfields into select
    // lists since the number and names of keys used by image field to provide data are
    // known and unchangeable.
    // @see VisualNFormsHelper::processDrawerContainerSubform()
    // @see VisualNImageFormatter::processDrawerContainerSubform()
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
  public function fetchDrawing() {
    // @todo: review the code here
    $drawing_markup = parent::fetchDrawing();

    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return $drawing_markup;
    }


    $current_entity = $this->getContextValue('current_entity');

    $field_name = $this->configuration['image_field'];
    //if (empty($field_name) || !$current_entity->hasField($field_name)) {
    if (empty($field_name) || !$current_entity->hasField($field_name) || $current_entity->get($field_name)->isEmpty()) {
      return $drawing_markup;
    }

    $field_instance = $current_entity->get($field_name);

    // @todo: get image files list


      // @todo: unsupported operand types error
      //    add default value into defaultConfiguration()
    $drawer_config = $this->configuration['drawer_config'] ?: [];
    $drawer_fields = $this->configuration['drawer_fields'];


    $data = [];

    //foreach($field_instance->referencedEntities() as $delta => $image_file) {
    foreach($field_instance->referencedEntities() as $delta => $file) {
      $image_uri = $file->getFileUri();
      // @todo: see the note in ImageFormatter::viewElements() relating a bug
      //$url = Url::fromUri(file_create_url($image_uri));
      $url = file_create_url($image_uri);

      // @todo: some other properties could be added, e.g. size etc.
      //   though some of them may be considered secure and shouldn't be added in every
      //   case (e.g. for js data it would be always exposed) and thus should be configured
      $data[] = [
        'url' => $url,
        'title' => $field_instance->get($delta)->get('title')->getString(),
        'alt' => $field_instance->get($delta)->get('alt')->getString(),
      ];
    }


    $raw_resource_plugin_id = static::RAW_RESOURCE_FORMAT;
    $raw_input = [
      'data' => $data,
    ];
    // @todo: add service in ::create() method
    $resource =
      \Drupal::service('plugin.manager.visualn.raw_resource_format')
      ->createInstance($raw_resource_plugin_id, [])
      ->buildResource($raw_input);

    // Get drawing window parameters
    $window_parameters = $this->getWindowParameters();

    // Get drawing build
    $build = $this->visualNBuilder->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields, '', $window_parameters);


    $drawing_markup = $build;


    // @todo: much of the code is taken from VisualNImageFormatter, check for further changes

    return $drawing_markup;
  }


  // @todo: move into a method into the GenericDrawingFetcherBase class
  protected function getManagerOptions() {
  }

}
