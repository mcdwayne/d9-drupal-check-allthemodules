<?php

namespace Drupal\visualn\Helpers;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

class VisualNFormsHelper {

  // @todo: rename to doProcessDrawerContainerSubform() to avoid confusion
  public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form, $configuration) {

    $visualNStyleStorage = \Drupal::service('entity_type.manager')->getStorage('visualn_style');


    // @todo: how to check if the form is fresh
    // is null basically means that the form is fresh (maybe check the whole $form_state->getValues() to be sure?)
    $style_element_parents = array_slice($element['#parents'], 0, -1);
    // since the function if called as a #process callback and the visualn_style_id select was already processed
    // and the values were mapped then it is enough to get form_state value for it and no need to check
    // configuration value (see FormBuilder::processForm() and FormBuilder::doBuildForm())
    // and no need in "is_null($visualn_style_id) then set value from config"
    $visualn_style_id = $form_state->getValue(array_merge($style_element_parents, ['visualn_style_id']));

    // If it is a fresh form (is_null($visualn_style_id)) or an empty option selected ($visualn_style_id == ""),
    // there is nothing to attach for drawer config.
    if (!$visualn_style_id) {
      return $element;
    }


    // Here the drawer plugin is initialized (inside getDrawerPlugin()) with the config stored in the style.
    $drawer_plugin = $visualNStyleStorage->load($visualn_style_id)->getDrawerPlugin();

    // We use drawer config from configuration only if it corresponds to the selected style. Also
    // we don't get form_state values for the drawer config here since they are handled by
    // drawer buildConfigurationForm() method itself and also even in buildConfigurationForm()
    // drawer should have access to the $this->configuration['drawer_config'] values.
    if ($visualn_style_id == $configuration['visualn_style_id']) {
      // Set initial configuration for the plugin according to the configuration stored in fetcher config.
      $drawer_config = $configuration['drawer_config'];
      $drawer_plugin->setConfiguration($drawer_config);

      // @todo: uncomment when the issue with handling drawer fields form_state values is resolved.
      //$drawer_fields = $this->configuration['drawer_fields'];

      // @todo: Until some generic way to hande drawer_fields form is introduced,
      //    e.g. \VisualN::buildDrawerDataKeysForm(), we should handle form_state values for the drawer_fields
      //    manually (i.e. in case of form validation errors form_state values should be used).
      $drawer_fields
        = $form_state->getValue(array_merge($style_element_parents, ['drawer_fields']), $configuration['drawer_fields']);
      // @todo: Technically values should be taken from  array_merge($element['#parents'], ['drawer_fields'])
      //    but since validation function (see #element_validate) restructures the values (which should be
      //    done at submit level), we're taking it into consideration here.
    }
    else {
      // Leave drawer_config unset for later initialization with drawer_plugin->getConfiguration() values
      // which are generally taken from visualn style configuration.

      // Initialize drawer_config based on (visualn style stored config) in case it is needed somewhere else below.
      $drawer_config = $drawer_plugin->getConfiguration();

      // Since drawer_fields is always an empty array for a visualn style drawer plugin (VisualNStyle::getDrawerPlugin()),
      // it is ok to set it to an empty array here. In contrast, if null, drawer_config should be taken
      // from the visualn style plugin configuraion.
      $drawer_fields = [];
    }

    // Attach drawer configuration form

    // The visualn style stored drawer configuration is generally only used for fresh form or when
    // switching visualn select box to a new style. In other cases drawer_config is provided by
    // the fetcher_config or set to an empty array.
    // Remember that this drawer_config is used only for plugin initialization, the build config form method
    // also checks form_state by itself.


    // Use unique drawer container key for each visualn style from the select box so that the settings
    // wouldn't be overridden by the previous one on ajax calls (expecially when styles use the same
    // drawer and thus the same configuration form with the same keys).
    $drawer_container_key = $visualn_style_id;

    // get drawer configuration form

    $element[$drawer_container_key]['drawer_config'] = [];
    $element[$drawer_container_key]['drawer_config'] += [
      '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$drawer_container_key]['drawer_config'], $form, $form_state);
    // attach drawer configuration form
    $element[$drawer_container_key]['drawer_config']
              = $drawer_plugin->buildConfigurationForm($element[$drawer_container_key]['drawer_config'], $subform_state);




    // @todo: Use some kind of \VisualN::buildDrawerDataKeysForm($drawer_plugin, $form, $form_state) here.
    // @todo: trim data_keys values after submitting settings

    // Drawer fields subform (i.e. data_keys mappings) should be attached in a separate #process callback
    // that would trigger after the drawer buildConfigurationForm() attaches the config form
    // and is completed. It is required for the case when drawer has a variable number of data keys.
    // see the code below
    // @see VisualNDrawing::processDrawerContainerSubform()
    $element[$drawer_container_key]['drawer_fields']['#process'] = [[get_called_class(), 'processDrawerFieldsSubform']];
    $element[$drawer_container_key]['drawer_fields']['#drawer_plugin'] = $drawer_plugin;
    $element[$drawer_container_key]['drawer_fields']['#drawer_fields'] = $drawer_fields;

    // Check if drawer config has ajaxified elements to update data keys (same as in views).
    // This should be done in #process (but not #after_build) since #ajax settings use
    // also a #process callback to attach js etc.
    $style_element_array_parents = array_slice($element['#array_parents'], 0, -1);
    $base_element = NestedArray::getValue($form, $style_element_array_parents);

    // get ajax wrapper id to replace the original drawer one if found
    $ajax_wrapper_id = $base_element['visualn_style_id']['#ajax']['wrapper'];
    static::replaceAjaxOptions($element, $form_state, $ajax_wrapper_id);

    // open drawer_container 'details' if needed
    $element[$drawer_container_key]['#after_build'][] = [get_called_class(), 'afterBuildDrawerDetailsOpenSubform'];


    // @todo: replace with #element_submit when introduced into core
    // extract values for drawer_container subform and drawer_config and drawer_fields
    //    remove drawer_container key from form_state values path
    //    also it can be done in ::submitConfigurationForm()
    $element[$drawer_container_key]['#element_validate'] = [[get_called_class(), 'validateDrawerContainerSubForm']];
    //$element[$drawer_container_key]['#element_validate'] = [[get_called_class(), 'submitDrawerContainerSubForm']];

    return $element;
  }

  /**
   * Attach drawer_fields subform based on drawer_plugin dataKeys().
   *
   * The subform is attached in a #process callback to have drawer config form
   * values already mapped at this point. It is needed e.g. for drawers with variable
   * number of data keys managed in a #process callback set in buildConfigurationForm().
   *
   * @see \Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer\LinechartBasicDrawer
   */
  public static function processDrawerFieldsSubform(array $element, FormStateInterface $form_state, $form) {
    $element_parents = $element['#array_parents'];
    $base_element_parents = array_slice($element_parents, 0, -1);
    $base_element_parents[] = 'drawer_config';

    $config_element = NestedArray::getValue($form, $base_element_parents);
    $subform_state = SubformState::createForSubform($config_element, $form, $form_state);

    $drawer_plugin = $element['#drawer_plugin'];
    $drawer_fields = $element['#drawer_fields'];
    $drawer_plugin_clone = clone $drawer_plugin;
    $drawer_config = $drawer_plugin->extractFormValues($config_element, $subform_state);
    $drawer_plugin_clone->setConfiguration($drawer_config);

    $data_keys = $drawer_plugin_clone->dataKeys();
    // @todo: convert textfields into a table in a #process callback
    //    maybe even inside Mapper config form method
    if (!empty($data_keys)) {
      // @todo: get rid of value from 'field' or massage value at plugin submit
      $element += [
        '#type' => 'table',
        '#header' => [t('Data key'), t('Field')],
      ];
      foreach ($data_keys as $i => $data_key) {
        $element[$data_key]['label'] = [
          '#plain_text' => $data_key,
        ];
        $element[$data_key]['field'] = [
          '#type' => 'textfield',
          '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
        ];
      }
    }

    return $element;
  }

  /**
   * Process callback for drawer config and drawer fields container subform.
   *
   * Check  if 'details' element should be open after all drawer_config and drawer_fields elements are attached.
   */
  public static function afterBuildDrawerDetailsOpenSubform(array $element, FormStateInterface $form_state) {
    // since drawer and fields configuration forms may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element['drawer_config']) || Element::children($element['drawer_fields'])) {
      // @todo: actually it is base_element_array_parents for both, visualn_style_id and drawer_config
      $style_element_array_parents = array_slice($element['#array_parents'], 0, -2);
      //$style_element_array_parents = array_slice($element['#array_parents'], 0, -1);
      // check that the triggering element is visualn_style_id but not fetcher_id select (or some other element) itself
      $details_open = FALSE;
      if ($form_state->getTriggeringElement()) {
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($style_element_array_parents, ['visualn_style_id']);

        // also open details on ajaxified element click
        if (!$details_open) {
          $diff = array_diff($triggering_element['#array_parents'], $style_element_array_parents);
          // the $diff is supposed not to be empty since drawer_config and visualn_style_id have the same base
          if ($diff) {
            $details_open = $triggering_element['#array_parents'] === array_merge($style_element_array_parents, $diff);
          }
        }
      }
      $element = [
        '#type' => 'details',
        '#title' => t('Style configuration'),
        '#open' => $details_open,
      ] + $element;
    }

    return $element;
  }


  // @todo: Restructuring form_state values (removing drawer_container key) should be moved
  //    into #element_submit callback when introduced.
  public static function validateDrawerContainerSubForm(&$form, FormStateInterface $form_state, $full_form) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get drawer_container_key (for selected visualn style is equal by convention to visualn_style_id,
    // see processDrawerContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $drawer_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $drawer_container_key = array_pop($element_parents);

    // remove 'drawer_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);



    // Call drawer_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    // @todo: it is not correct to call submit inside a validate method (validateDrawerContainerSubForm())
    //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
    //$full_form = $form_state->getCompleteForm();
    $subform = $form['drawer_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $visualn_style_id  = $form_state->getValue(array_merge($base_element_parents, ['visualn_style_id']));
    $visualn_style = \Drupal::service('entity_type.manager')->getStorage('visualn_style')->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();
    $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);


    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_config_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_config']));
    if (!is_null($drawer_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['drawer_config']), $drawer_config_values);
    }

    // move drawer_fields two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_fields_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_fields']));
    if (!is_null($drawer_fields_values)) {
      $new_drawer_fields_values = [];
      foreach ($drawer_fields_values as $drawer_field_key => $drawer_field) {
        $new_drawer_fields_values[$drawer_field_key] = $drawer_field['field'];
      }

      $form_state->setValue(array_merge($base_element_parents, ['drawer_fields']), $new_drawer_fields_values);
    }

    // remove remove 'drawer_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$drawer_container_key]));
  }




  public static function doProcessBaseDrawerSubform(array $element, FormStateInterface $form_state, $form, $configuration) {
    // @todo: how to check if the form is fresh
    // is null basically means that the form is fresh (maybe check the whole $form_state->getValues() to be sure?)
    $drawer_element_parents = array_slice($element['#parents'], 0, -1);

    // since the function if called as a #process callback and the drawer_plugin_id select was already processed
    // and the values were mapped then it is enough to get form_state value for it and no need to check
    // configuration value (see FormBuilder::processForm() and FormBuilder::doBuildForm())
    // and no need in "is_null($visualn_style_id) then set value from config"
    $drawer_plugin_id = $form_state->getValue(array_merge($drawer_element_parents, ['drawer_plugin_id']));

    // If it is a fresh form (is_null($visualn_style_id)) or an empty option selected ($visualn_style_id == ""),
    // there is nothing to attach for drawer config.
    if (!$drawer_plugin_id) {
      return $element;
    }

    $visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');

    // Intentionally instantiate a plugin with default configuration.
    $drawer_plugin = $visualNDrawerManager->createInstance($drawer_plugin_id, []);


    if ($drawer_plugin_id == $configuration['drawer_plugin_id']) {
      $drawer_config = $configuration['drawer_config'];
      $drawer_plugin->setConfiguration($drawer_config);
    }
    else {
      $drawer_config = $drawer_plugin->getConfiguration();
    }

    $drawer_container_key = $drawer_plugin_id;

    // get drawer configuration form

    $element[$drawer_container_key]['drawer_config'] = [];
    $element[$drawer_container_key]['drawer_config'] += [
      '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$drawer_container_key]['drawer_config'], $form, $form_state);
    // attach drawer configuration form
    $element[$drawer_container_key]['drawer_config']
              = $drawer_plugin->buildConfigurationForm($element[$drawer_container_key]['drawer_config'], $subform_state);


    // since drawer and fields onfiguration forms may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$drawer_container_key]['drawer_config'])) {
      $drawer_element_array_parents = array_slice($element['#array_parents'], 0, -1);
      // check that the triggering element is visualn_style_id but not fetcher_id select (or some other element) itself
      if ($form_state->getTriggeringElement()) {
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($drawer_element_array_parents, ['drawer_plugin_id']);
        $element[$drawer_container_key] = [
          '#type' => 'details',
          '#title' => t('Base Drawer configuration'),
          '#open' => $details_open,
        ] + $element[$drawer_container_key];
      }
    }

    $element[$drawer_container_key]['#element_validate'] = [[get_called_class(), 'validateBaseDrawerSubForm']];
    // @todo: uncomment when #element_submit is introduced into core
    //$element[$drawer_container_key]['#element_submit'] = [[get_called_class(), 'submitBaseDrawerSubForm']];

    return $element;
  }

  // @todo: this is based on ResourceGenericDrawerFetcher::processDrawerContainerSubform()
  public static function validateBaseDrawerSubForm(&$form, FormStateInterface $form_state) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    $visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get drawer_container_key (for selected visualn style is equal by convention to visualn_style_id,
    // see processDrawerContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $drawer_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $drawer_container_key = array_pop($element_parents);

    // remove 'drawer_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);



    // Call drawer_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    // @todo: it is not correct to call submit inside a validate method (validateDrawerContainerSubForm())
    //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
    // @todo: get full_form from the method arguments
    $full_form = $form_state->getCompleteForm();
    $subform = $form['drawer_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $drawer_plugin_id  = $form_state->getValue(array_merge($base_element_parents, ['drawer_plugin_id']));
    // @todo: no need in drawer_config here since submitConfigurationForm() should fully rely on form_state values
    $drawer_plugin = $visualNDrawerManager->createInstance($drawer_plugin_id, []);
    $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);


    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_config_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_config']));
    if (!is_null($drawer_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['drawer_config']), $drawer_config_values);
    }


    // remove remove 'drawer_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$drawer_container_key]));
  }




  public static function doProcessProviderContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'resource_provider_id' => $stored_configuration['resource_provider_id'],
      'resource_provider_config' => $stored_configuration['resource_provider_config'],
    ];
    $context_entity_type = $element['#entity_type'] ?: '';
    $context_bundle = $element['#bundle'] ?: '';



    $provider_element_parents = array_slice($element['#parents'], 0, -1);
    $resource_provider_id = $form_state->getValue(array_merge($provider_element_parents, ['resource_provider_id']));

    // If it is a fresh form (is_null($resource_provider_id)) or an empty option selected ($resource_provider_id == ""),
    // there is nothing to attach for provider config.
    if (!$resource_provider_id) {
      return $element;
    }

    if ($resource_provider_id == $configuration['resource_provider_id']) {
      $resource_provider_config = $configuration['resource_provider_config'];
    }
    else {
      $resource_provider_config = [];
    }

    $visualNResourceProviderManager = \Drupal::service('plugin.manager.visualn.resource_provider');

    $provider_plugin = $visualNResourceProviderManager->createInstance($resource_provider_id, $resource_provider_config);

    // @todo: maybe just pass all available contexts

    // Set "entity_type" and "bundle" contexts
    $context_entity_type = new Context(new ContextDefinition('string', NULL, TRUE), $context_entity_type);
    $provider_plugin->setContext('entity_type', $context_entity_type);

    $context_bundle = new Context(new ContextDefinition('string', NULL, TRUE), $context_bundle);
    $provider_plugin->setContext('bundle', $context_bundle);

    // @todo: see the note regarding setting context in VisualNResourceProviderItem class

    $provider_container_key = $resource_provider_id;

    // get provider configuration form

    $element[$provider_container_key]['provider_config'] = [];
    $element[$provider_container_key]['provider_config'] += [
      '#parents' => array_merge($element['#parents'], [$provider_container_key, 'provider_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$provider_container_key, 'provider_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$provider_container_key]['provider_config'], $form, $form_state);
    // attach provider configuration form
    $element[$provider_container_key]['provider_config']
              = $provider_plugin->buildConfigurationForm($element[$provider_container_key]['provider_config'], $subform_state);


    // since provider configuration form may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$provider_container_key]['provider_config'])) {
      $provider_element_array_parents = array_slice($element['#array_parents'], 0, -1);
      // check that the triggering element is resource_provider_id but not fetcher_id select (or some other element) itself
      $details_open = FALSE;
      if ($form_state->getTriggeringElement()) {
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($provider_element_array_parents, ['resource_provider_id']);
      }
      // @todo: take it out everywhere else
      $element[$provider_container_key] = [
        '#type' => 'details',
        '#title' => t('Provider configuration'),
        '#open' => $details_open,
      ] + $element[$provider_container_key];
    }

    // @todo: replace with #element_submit when introduced into core
    // extract values for provider_container subform and provider_config
    //    remove provider_container key from form_state values path
    //    also it can be done in ::submitConfigurationForm()
    $element[$provider_container_key]['#element_validate'] = [[get_called_class(), 'validateProviderContainerSubForm']];
    //$element[$provider_container_key]['#element_validate'] = [[get_called_class(), 'submitDrawerContainerSubForm']];


    return $element;
  }

  // @todo: Restructuring form_state values (removing provider_container key) should be moved
  //    into #element_submit callback when introduced.
  // This is based on VisualNFormHelper::validateDrawerContainerSubForm().
  public static function validateProviderContainerSubForm(&$form, FormStateInterface $form_state, $full_form) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get provider_container_key (for selected provider is equal by convention to resource_provider_id,
    // see processProviderContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $provider_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $provider_container_key = array_pop($element_parents);

    // remove 'provider_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);



    // Call provider_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    // @todo: it is not correct to call submit inside a validate method (validateDrawerContainerSubForm())
    //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
    //$full_form = $form_state->getCompleteForm();
    $subform = $form['provider_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $visualNResourceProviderManager = \Drupal::service('plugin.manager.visualn.resource_provider');
    $resource_provider_id  = $form_state->getValue(array_merge($base_element_parents, ['resource_provider_id']));
    // The submit callback shouldn't depend on plugin configuration, it relies only on form_state values.
    $resource_provider_config  = [];
    $provider_plugin = $visualNResourceProviderManager->createInstance($resource_provider_id, $resource_provider_config);
    $provider_plugin->submitConfigurationForm($subform, $sub_form_state);


    // move provider_config two levels up (remove 'provider_container' and $provider_container_key) in form_state values
    $provider_config_values = $form_state->getValue(array_merge($element_parents, [$provider_container_key, 'provider_config']));
    if (!is_null($provider_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['resource_provider_config']), $provider_config_values);
    }

    // remove remove 'provider_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$provider_container_key]));
    // also unset 'provider_container' key if empty
    // this check is added in case something else is added to the container by extending classes
    // @todo: actually the same check should be added before unsetting provider_container_key (and
    //    to other places where the same logic with config forms is implemented)
    if (!$form_state->getValue($element_parents)) {
      $form_state->unsetValue($element_parents);
    }
  }





  public static function doProcessGeneratorContainerSubform(array $element, FormStateInterface $form_state, $form, $configuration) {
    $generator_element_parents = array_slice($element['#parents'], 0, -1);
    $data_generator_id = $form_state->getValue(array_merge($generator_element_parents, ['data_generator_id']));

    // If it is a fresh form (is_null($data_generator_id)) or an empty option selected ($data_generator_id == ""),
    // there is nothing to attach for generator config.
    if (!$data_generator_id) {
      return $element;
    }

    if ($data_generator_id == $configuration['data_generator_id']) {
      $data_generator_config = $configuration['data_generator_config'];
    }
    else {
      $data_generator_config = [];
    }

    $visualNDataGeneratorManager = \Drupal::service('plugin.manager.visualn.data_generator');

    $generator_plugin = $visualNDataGeneratorManager->createInstance($data_generator_id, $data_generator_config);

    $generator_container_key = $data_generator_id;

    // get generator configuration form

    $element[$generator_container_key]['generator_config'] = [];
    $element[$generator_container_key]['generator_config'] += [
      '#parents' => array_merge($element['#parents'], [$generator_container_key, 'generator_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$generator_container_key, 'generator_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$generator_container_key]['generator_config'], $form, $form_state);
    // attach generator configuration form
    $element[$generator_container_key]['generator_config']
              = $generator_plugin->buildConfigurationForm($element[$generator_container_key]['generator_config'], $subform_state);


    // since generator configuration form may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$generator_container_key]['generator_config'])) {
      $generator_element_array_parents = array_slice($element['#array_parents'], 0, -1);
      // check that the triggering element is data_generator_id but not fetcher_id or resource_provider_id select (or some other element) itself
      $details_open = FALSE;
      if ($form_state->getTriggeringElement()) {
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($generator_element_array_parents, ['data_generator_id']);
      }
      // @todo: take it out everywhere else
      $element[$generator_container_key] = [
        '#type' => 'details',
        '#title' => t('Generator configuration'),
        '#open' => $details_open,
      ] + $element[$generator_container_key];
    }

    // @todo: replace with #element_submit when introduced into core
    // extract values for generator_container subform and generator_config
    //    remove generator_container key from form_state values path
    //    also it can be done in ::submitConfigurationForm()
    $element[$generator_container_key]['#element_validate'] = [[get_called_class(), 'validateGeneratorContainerSubForm']];
    //$element[$generator_container_key]['#element_validate'] = [[get_called_class(), 'submitDrawerContainerSubForm']];


    return $element;
  }

  // @todo: Restructuring form_state values (removing generator_container key) should be moved
  //    into #element_submit callback when introduced.
  // This is based on VisualNFormHelper::validateDrawerContainerSubForm().
  public static function validateGeneratorContainerSubForm(&$form, FormStateInterface $form_state, $full_form) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get generator_container_key (for selected generator is equal by convention to data_generator_id,
    // see processGeneratorContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $generator_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $generator_container_key = array_pop($element_parents);

    // remove 'generator_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);



    // Call generator_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    // @todo: it is not correct to call submit inside a validate method (validateDrawerContainerSubForm())
    //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
    //$full_form = $form_state->getCompleteForm();
    $subform = $form['generator_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $visualNDataGeneratorManager = \Drupal::service('plugin.manager.visualn.data_generator');
    $data_generator_id  = $form_state->getValue(array_merge($base_element_parents, ['data_generator_id']));
    // The submit callback shouldn't depend on plugin configuration, it relies only on form_state values.
    $data_generator_config  = [];
    $generator_plugin = $visualNDataGeneratorManager->createInstance($data_generator_id, $data_generator_config);
    $generator_plugin->submitConfigurationForm($subform, $sub_form_state);


    // move generator_config two levels up (remove 'generator_container' and $generator_container_key) in form_state values
    $generator_config_values = $form_state->getValue(array_merge($element_parents, [$generator_container_key, 'generator_config']));
    if (!is_null($generator_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['data_generator_config']), $generator_config_values);
    }

    // remove remove 'generator_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$generator_container_key]));
    // also unset 'generator_container' key if empty
    // this check is added in case something else is added to the container by extending classes
    // @todo: actually the same check should be added before unsetting generator_container_key (and
    //    to other places where the same logic with config forms is implemented)
    if (!$form_state->getValue($element_parents)) {
      $form_state->unsetValue($element_parents);
    }
  }





  /**
   * Process #ajax elements. If drawer configuration form uses #ajax to rebuild elements on cerain events,
   * those calls must use views specific 'url' setting or new elements values won't be saved.
   *
   * @todo: based on views display style VisualNDrawing::replaceAjaxOptions()
   */
  protected static function replaceAjaxOptions(&$element, FormStateInterface $form_state, $ajax_wrapper_id, $base_depth = NULL) {
    if (is_null($base_depth)) {
      // get base_depth without 'drawer_container' key
      $base_depth = count($element['#array_parents']) - 1;
    }
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['#ajax'])) {
        $element[$key]['#ajax'] = [
          'callback' => [get_called_class(), 'ajaxCallback2'],
          'wrapper' => $ajax_wrapper_id,
          // we can't use element_depth since in some cases it will contain wrong value,
          // e.g. when using #ajax with 'radios', each radio button inherits parent element
          // element_depth value (with all other #ajax settings) though the real value is greater by one
          // @todo: maybe set as $element[$key]['#drawer_config_base_element_depth']
          'base_depth' => $base_depth,
        ];
      }

      // check subtree elements
      static::replaceAjaxOptions($element[$key], $form_state, $ajax_wrapper_id, $base_depth);
    }
  }

  public static function ajaxCallback2(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();

    // the base_depth value is set in replaceAjaxOptions()
    $base_depth = $triggering_element['#ajax']['base_depth'];

    // get parent element of the whole config form (parent element for drawer_container), including data keys subform
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, $base_depth);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }


  // @todo: mostly based on VisualNFormsHelper::doProcessGeneratorContainerSubform()
  public static function doProcessSkinContainerSubform(array $element, FormStateInterface $form_state, $form, $configuration) {
    $skin_element_parents = array_slice($element['#parents'], 0, -1);
    $drawer_skin_id = $form_state->getValue(array_merge($skin_element_parents, ['drawer_skin_id']));

    // If it is a fresh form (is_null($drawer_skin_id)) or an empty option selected ($drawer_skin_id == ""),
    // there is nothing to attach for skin config.
    if (!$drawer_skin_id) {
      return $element;
    }

    if ($drawer_skin_id == $configuration['drawer_skin_id']) {
      $drawer_skin_config = $configuration['drawer_skin_config'];
    }
    else {
      $drawer_skin_config = [];
    }

    $visualNDrawerSkinManager = \Drupal::service('plugin.manager.visualn.drawer_skin');

    $skin_plugin = $visualNDrawerSkinManager->createInstance($drawer_skin_id, $drawer_skin_config);

    $skin_container_key = $drawer_skin_id;

    // get skin configuration form

    $element[$skin_container_key]['skin_config'] = [];
    $element[$skin_container_key]['skin_config'] += [
      '#parents' => array_merge($element['#parents'], [$skin_container_key, 'skin_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$skin_container_key, 'skin_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$skin_container_key]['skin_config'], $form, $form_state);
    // attach skin configuration form
    $element[$skin_container_key]['skin_config']
              = $skin_plugin->buildConfigurationForm($element[$skin_container_key]['skin_config'], $subform_state);


    // since skin configuration form may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$skin_container_key]['skin_config'])) {
      $skin_element_array_parents = array_slice($element['#array_parents'], 0, -1);
      // check that the triggering element is drawer_skin_id but not fetcher_id or resource_provider_id select (or some other element) itself
      $details_open = FALSE;
      if ($form_state->getTriggeringElement()) {
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($skin_element_array_parents, ['drawer_skin_id']);
      }
      // @todo: take it out everywhere else
      $element[$skin_container_key] = [
        '#type' => 'details',
        '#title' => t('Skin configuration'),
        '#open' => $details_open,
      ] + $element[$skin_container_key];
    }

    // @todo: replace with #element_submit when introduced into core
    // extract values for skin_container subform and skin_config
    //    remove skin_container key from form_state values path
    //    also it can be done in ::submitConfigurationForm()
    $element[$skin_container_key]['#element_validate'] = [[get_called_class(), 'validateSkinContainerSubForm']];
    //$element[$skin_container_key]['#element_validate'] = [[get_called_class(), 'submitDrawerContainerSubForm']];


    return $element;
  }

  // @todo: Restructuring form_state values (removing skin_container key) should be moved
  //    into #element_submit callback when introduced.
  // This is based on VisualNFormHelper::validateDrawerContainerSubForm().
  public static function validateSkinContainerSubForm(&$form, FormStateInterface $form_state, $full_form) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get skin_container_key (for selected skin is equal by convention to drawer_skin_id,
    // see processGeneratorContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $skin_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $skin_container_key = array_pop($element_parents);

    // remove 'skin_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);



    // Call skin_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    // @todo: it is not correct to call submit inside a validate method (validateDrawerContainerSubForm())
    //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
    //$full_form = $form_state->getCompleteForm();
    $subform = $form['skin_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $visualNDrawerSkinManager = \Drupal::service('plugin.manager.visualn.drawer_skin');
    $drawer_skin_id  = $form_state->getValue(array_merge($base_element_parents, ['drawer_skin_id']));
    // The submit callback shouldn't depend on plugin configuration, it relies only on form_state values.
    $drawer_skin_config  = [];
    $skin_plugin = $visualNDrawerSkinManager->createInstance($drawer_skin_id, $drawer_skin_config);
    $skin_plugin->submitConfigurationForm($subform, $sub_form_state);


    // move skin_config two levels up (remove 'skin_container' and $skin_container_key) in form_state values
    $skin_config_values = $form_state->getValue(array_merge($element_parents, [$skin_container_key, 'skin_config']));
    if (!is_null($skin_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['drawer_skin_config']), $skin_config_values);
    }

    // remove remove 'skin_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$skin_container_key]));
    // also unset 'skin_container' key if empty
    // this check is added in case something else is added to the container by extending classes
    // @todo: actually the same check should be added before unsetting skin_container_key (and
    //    to other places where the same logic with config forms is implemented)
    if (!$form_state->getValue($element_parents)) {
      $form_state->unsetValue($element_parents);
    }
  }

}
