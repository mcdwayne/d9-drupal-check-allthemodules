<?php

namespace Drupal\visualn_url_field\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Core\DrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Url field reader' VisualN drawing fetcher.
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_url_field_reader",
 *  label = @Translation("Url field reader"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class UrlFieldReaderDrawingFetcher extends DrawingFetcherBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'visualn_url_field' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * @todo: this is mostly a copy-paste of the FileFieldReaderDrawingFetcher::fetchDrawing()
   *   review the code
   */
  public function fetchDrawing() {
    $drawing_markup = parent::fetchDrawing();

    $current_entity = $this->getContextValue('current_entity');

    // @todo: use just $field_name for the variable name
    $visualn_url_field = $this->configuration['visualn_url_field'];
    if (empty($visualn_url_field) || !$current_entity->hasField($visualn_url_field)) {
      return $drawing_markup;
    }

    $field_instance = $current_entity->get($visualn_url_field);
    if (!$field_instance->isEmpty()) {
      $first_delta = $field_instance->first();

      // @todo: this is based on VisualNFormatterSettingsTrait; review
      $visualn_style_id = $first_delta->visualn_style_id;
      $visualn_data = !empty($first_delta->visualn_data) ? unserialize($first_delta->visualn_data) : [];
      if ($visualn_style_id) {
        $drawer_config = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
        $drawer_fields = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];


        // get resource and the drawing, else return empty drawing_markup
        if (!empty($visualn_data['resource_format'])) {
          $resource_format_plugin_id = $visualn_data['resource_format'];

          $raw_input = [
            // @todo: check if not empty (?)
            'file_url' => $first_delta->getUrl()->toString(),
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
   *
   * @todo: this is mostly a copy-paste of the FileFieldReaderDrawingFetcher::buildConfigurationForm()
   *   review the code
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

    // @todo: add as 'empty' option
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
      if ($field_definition->getType() == 'visualn_url') {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    $form['visualn_url_field'] = [
      '#type' => 'select',
      '#title' => t('VisualN Url field'),
      '#options' => $options,
      // @todo: where to use getConfiguration and where $this->configuration (?)
      //    the same question for other plugin types
      '#default_value' => $this->configuration['visualn_url_field'],
      '#description' => t('Select the VisualN Url field for the drawing source'),
    ];

    return $form;
  }

}
