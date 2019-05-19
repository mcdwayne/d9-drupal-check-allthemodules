<?php

namespace Drupal\visualn_drawing\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Element;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Plugin implementation of the 'visualn_fetcher' widget.
 *
 * @FieldWidget(
 *   id = "visualn_fetcher",
 *   label = @Translation("VisualN fetcher"),
 *   field_types = {
 *     "visualn_fetcher"
 *   }
 * )
 */
class VisualNFetcherWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      //'fetcher_id' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    // Actually default drawing fetcher is already set at field default value configuration level.
    /*$elements['fetcher_id'] = [
      '#type' => 'select',
      '#title' => t('Fetcher plugin id'),
      '#options' => $options,
      '#description' => t('Default drawing fetcher plugin.'),
    ];*/

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // @todo: add settings summary
    $summary = [];

    /*if (!empty($this->getSetting('fetcher_id'))) {
      // @todo: get label for the fetcher plugin
      $summary[] = t('Drawing fetcher: @fetcher_plugin_label', ['@fetcher_plugin_label' => $this->getSetting('fetcher_id')]);
    }*/

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // @todo: review the code, see VisualNResourceProviderWidget class

    $element['#type'] = 'fieldset';
    $element['#description'] = t('Select and configure drawing fetcher plugin to create a drawing. Each fetcher may have its specific configuration options set and drawing building logic implemented.');

    $item = $items[$delta];
    $fetcher_config = !empty($item->fetcher_config) ? unserialize($item->fetcher_config) : [];
    // @todo: use #field_parents key

    $fetchers_list = ['' => t('- Select drawing fetcher -')];

    // Get drawing fetchers plugins list
    // @todo: instantiate at class creation
    $definitions = \Drupal::service('plugin.manager.visualn.drawing_fetcher')->getDefinitions();
    foreach ($definitions as $definition) {
      $fetchers_list[$definition['id']] = $definition['label'];
    }


    // @todo: is this ok to get parents this way?
    //    if used in #process though, #parents key is already set
    $field_name = $this->fieldDefinition->getName();
    $parents = array_merge($element['#field_parents'], [$field_name, $delta]);
    $fetcher_id = $form_state->getValue(array_merge($parents, ['fetcher_id']));

    // @todo: how to check if the form is fresh
    // is null basically means that the form is fresh (maybe check the whole $form_state->getValues() to be sure?)
    // $fetcher_id can be empty string (in case of default choice) or NULL in case of fresh form
    if (is_null($fetcher_id)) {
      $fetcher_id = $item->fetcher_id ?: '';
    }

    $ajax_wrapper_id = $field_name . '-' . $delta . '-fetcher-config-ajax-wrapper';

    // select drawing fetcher plugin
    $element['fetcher_id'] = [
      '#type' => 'select',
      '#title' => t('Drawer fetcher plugin'),
      '#options' => $fetchers_list,
      '#default_value' => $fetcher_id,
      '#ajax' => [
        //'callback' => [get_called_class(), 'ajaxCallback'],
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#empty_value' => '',
    ];
    $element['fetcher_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
    ];

    $element['fetcher_container']['fetcher_config'] = ['#process' => [[$this, 'processFetcherConfigurationSubform']]];
    // @todo: $item is needed in the #process callback to access fetcher_config from field configuration,
    //    maybe there is a better way
    $element['fetcher_container']['fetcher_config']['#item'] = $item;

    // @todo: Set entity type and bundle for the fetcher_plugin since it may need the list of all its fields.

    // @todo: We can't pass the current reference to the entity because it doesn't always exist,
    //    e.g. when setting default value for the field in field settings.
    //    Actually it does (see the note below) but can it be used by plugins or should additional
    //    checks be done? Maybe kind of empty entity?
    // @todo: maybe pass entityType config entity

/*
    $field_definition = $this->fieldDefinition;
    if ($field_definition instanceof Drupal\Core\Field\BaseFieldDefinition) {
    }
*/

    // @note: It also works for configuring field form (see FieldItemList::getEntity())
    //   though entity values are all empty since there basically can't be any real entity
    //   it is a nice feature
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // @note: $field_definition::getTargetBundle() is empty for base entity fields since
    // they are connected to the entity, not bundle
    // though $field_definition::getTargetEntityTypeId() works
    //$entity_type = $field_definition->getTargetEntityTypeId();
    //$bundle = $field_definition->getTargetBundle();


    // @todo: these are not working with 'Default fetcher' field which is a base field
    //   defined in-code in entity definition.
    //$entity_type = $this->fieldDefinition->get('entity_type');
    //$bundle = $this->fieldDefinition->get('bundle');

    // @todo: maybe we can get this data in the #process callback directly from the $item object
    $element['fetcher_container']['fetcher_config']['#entity_type'] = $entity_type;
    $element['fetcher_container']['fetcher_config']['#bundle'] = $bundle;

    return $element;
  }


  // @todo: The code below should be almost the same as for VisualN block configuration form
  public function processFetcherConfigurationSubform(array $element, FormStateInterface $form_state, $form) {
    $item = $element['#item'];
    $entity_type = $element['#entity_type'];
    $bundle = $element['#bundle'];

    $configuration = [
      'fetcher_id' => $item->fetcher_id,
      'fetcher_config' => !empty($item->fetcher_config) ? unserialize($item->fetcher_config) : [],
    ];

    $fetcher_element_parents = array_slice($element['#parents'], 0, -2);
    $fetcher_id = $form_state->getValue(array_merge($fetcher_element_parents, ['fetcher_id']));
    // Whether fetcher_id is an empty string (which means changed to the Default option) or NULL (which means
    // that the form is fresh) there is nothing to attach for fetcher_config subform.
    if (!$fetcher_id) {
      return $element;
    }

    if ($fetcher_id == $configuration['fetcher_id']) {
      // @note: plugins are instantiated with default configuration to know about it
      //    but at configuration form rendering always the form_state values are (should be) used
      $fetcher_config = $configuration['fetcher_config'];
    }
    else {
      $fetcher_config = [];
    }

    // Basically this check is not needed
    if ($fetcher_id) {
      // fetcher plugin buildConfigurationForm() needs Subform:createForSubform() form_state
      $subform_state = SubformState::createForSubform($element, $form, $form_state);

      // instantiate fetcher plugin
      $visualNDrawingFetcherManager = \Drupal::service('plugin.manager.visualn.drawing_fetcher');
      //$fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);
      $fetcher_plugin = $visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);


      // Set "entity_type" and "bundle" contexts
      $context_entity_type = new Context(new ContextDefinition('string', NULL, TRUE), $entity_type);
      $fetcher_plugin->setContext('entity_type', $context_entity_type);

      $context_bundle = new Context(new ContextDefinition('string', NULL, TRUE), $bundle);
      $fetcher_plugin->setContext('bundle', $context_bundle);
      // @todo: see the note regarding setting context in VisualNResourceProviderItem class

      // attach fetcher configuration form
      // @todo: also fetcher_config_key may be added here as it is done for ResourceGenericDraweringFethcher
      //    and drawer_container_key.
      $element = $fetcher_plugin->buildConfigurationForm($element, $subform_state);

      // change fetcher configuration form container to fieldset if not empty
      if (Element::children($element)) {
        $element['#type'] = 'fieldset';
        $element['#title'] = t('Fetcher settings');
      }

      // @todo: a $fetcher_container_key could be used here to avoid the case when two fetcher plugins
      //    have configuration forms with the same keys and one overrides another on changing selected
      //    fetcher in the fetcher select box. See ResourceGenericDrawerFetcher for how it is done
      //    for visualn_style_id and drawer config form select.
/*
      $element['fetcher_config'] = [];
      $element['fetcher_config'] += [
        '#parents' => array_merge($element['#parents'], ['fetcher_config']),
        '#array_parents' => array_merge($element['#array_parents'], ['fetcher_config']),
      ];
*/
/*
      $element[$drawer_container_key]['drawer_config'] = [];
      $element[$drawer_container_key]['drawer_config'] += [
        '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
        '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
      ];
*/
    }

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // @todo: serialize fetcher_config
    foreach ($values as &$value) {
      $fetcher_config = [];
      if (!empty($value['fetcher_container']['fetcher_config'])) {
        foreach ($value['fetcher_container']['fetcher_config'] as $fetcher_config_key => $fetcher_config_item) {
          $fetcher_config[$fetcher_config_key] = $fetcher_config_item;
        }
        // @todo: unset()
      }
      $value['fetcher_config'] = serialize($fetcher_config);
    }
    return $values;
  }


  /**
   * {@inheritdoc}
   *
   * @todo: Add into an interface or add description
   *
   * return drawerConfigForm via ajax at style change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['fetcher_container'];
  }

}
