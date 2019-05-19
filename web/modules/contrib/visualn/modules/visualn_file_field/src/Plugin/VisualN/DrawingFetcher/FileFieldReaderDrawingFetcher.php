<?php

namespace Drupal\visualn_file_field\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Core\DrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'File field reader' VisualN drawing fetcher.
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_file_field_reader",
 *  label = @Translation("File field reader"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class FileFieldReaderDrawingFetcher extends DrawingFetcherBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'visualn_file_field' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    $drawing_markup = parent::fetchDrawing();

    $current_entity = $this->getContextValue('current_entity');

    // @todo: use just $field_name for the variable name
    $visualn_file_field = $this->configuration['visualn_file_field'];
    if (empty($visualn_file_field) || !$current_entity->hasField($visualn_file_field)) {
      return $drawing_markup;
    }

    $field_instance = $current_entity->get($visualn_file_field);
    if (!$field_instance->isEmpty()) {
      $first_delta = $field_instance->first();

      // @todo: this is based on VisualNFormatterSettingsTrait; review
      $visualn_style_id = $first_delta->visualn_style_id;
      $visualn_data = !empty($first_delta->visualn_data) ? unserialize($first_delta->visualn_data) : [];
      if ($visualn_style_id) {
        $drawer_config = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
        $drawer_fields = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];

        // @todo: this is a bit hackish, see GenericFileFormatter::viewElements
        $file = $first_delta->entity;

        // get resource and the drawing, else return empty drawing_markup
        if (!empty($visualn_data['resource_format'])) {
          $resource_format_plugin_id = $visualn_data['resource_format'];
          $raw_input = [
            'file_url' => $file->url(),
            'file_mimetype' => $file->getMimeType(),
          ];
          // @todo: config may be needed for some raw resources
          // @todo: add service in ::create() method
          $resource = \Drupal::service('plugin.manager.visualn.raw_resource_format')
                        ->createInstance($resource_format_plugin_id, [])
                        ->buildResource($raw_input);


          // Get drawing window parameters
          $window_parameters = $this->getWindowParameters();

          // Get drawing build
          $build = \Drupal::service('visualn.builder')->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields, '', $window_parameters);
          $drawing_markup = $build;
        }



        // @todo: this doesn't take into consideration formatter settings if visualn style is the same,
        //    see VisualNFormatterSettingsTrait
      }

      //dsm($first_delta->getSettings());

      // @todo: get drawer id and configuration and render the drawing (if overridden)
      //    else get settings from formatter (if not raw)
    }

    return $drawing_markup;
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

    // @todo: add as '#empty' select element propery
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
      if ($field_definition->getType() == 'visualn_file') {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    $form['visualn_file_field'] = [
      '#type' => 'select',
      '#title' => t('VisualN File field'),
      '#options' => $options,
      // @todo: where to use getConfiguration and where $this->configuration (?)
      //    the same question for other plugin types
      '#default_value' => $this->configuration['visualn_file_field'],
      '#description' => t('Select the VisualN File field for the drawing source'),
    ];

    return $form;
  }

}

