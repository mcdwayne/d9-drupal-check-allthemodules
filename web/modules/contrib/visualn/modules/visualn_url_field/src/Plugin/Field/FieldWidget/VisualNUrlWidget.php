<?php

namespace Drupal\visualn_url_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\Helpers\VisualN;

/**
 * Plugin implementation of the 'visualn_url' widget.
 *
 * @FieldWidget(
 *   id = "visualn_url",
 *   label = @Translation("VisualN url"),
 *   field_types = {
 *     "visualn_url"
 *   }
 * )
 */
class VisualNUrlWidget extends LinkWidget implements ContainerFactoryPluginInterface {

  const RAW_RESOURCE_FORMAT_GROUP = 'visualn_url_widget';

  // @todo: implement defaultSettings() method

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer')
    );
  }

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item = $items[$delta];
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';

    $definitions = VisualN::getRawResourceFormatsByGroup(self::RAW_RESOURCE_FORMAT_GROUP);


    // @todo: there should be some default behaviour for the 'None' choice (actually, this refers to formatter)
    $resource_formats = [];
    foreach ($definitions as $definition) {
      $resource_formats[$definition['id']] = $definition['label'];
    }

    $element['resource_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Resource format'),
      '#description' => $this->t('The format of the data source'),
      '#default_value' => $visualn_data['resource_format'],
      '#options' => $resource_formats,
      '#empty_option' => $this->t('- None -'),
      '#weight' => '2',
    ];

    $visualn_style_id = $item->visualn_style_id ?: '';
    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-' . $delta . '-drawer-config-ajax-wrapper';
    $visualn_styles = visualn_style_options(FALSE);
    $element['visualn_style_id'] = [
      '#title' => t('VisualN style'),
      '#type' => 'select',
      '#default_value' => $visualn_style_id,
      '#empty_option' => t('- Select -'),
      '#options' => $visualn_styles,
      '#weight' => '3',
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $element['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#weight' => '3',
      '#type' => 'container',
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];
    // @todo: $item is needed in the #process callback to access drawer_config from field configuration,
    //    maybe there is a better way
    $element['drawer_container']['#item'] = $item;

    return $element;
  }


  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $item = $element['#item'];
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    // @todo: resource_format isn't used in further building process
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';
    $visualn_data['drawer_config'] = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
    $visualn_data['drawer_fields'] = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];

    $configuration = $visualn_data;
    $configuration['visualn_style_id'] = $item->visualn_style_id ?: '';
    // @todo: add visualn_style_id = "" to widget default config (check) to avoid "?:" check

    // in case of ajaxified config forms with dynamic structure drawer fields are updated inside the callback
    $element = VisualNFormsHelper::processDrawerContainerSubform($element, $form_state, $form, $configuration);

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // @todo: maybe also call parent::massageFormValues()
    // @todo: this method is called twice on submit, is that ok?
    foreach ($values as &$value) {
      $value['uri'] = static::getUserEnteredStringAsUri($value['uri']);
      $drawer_config = [];
      if (!empty($value['drawer_config'])) {
        foreach ($value['drawer_config'] as $drawer_config_key => $drawer_config_item) {
          $drawer_config[$drawer_config_key] = $drawer_config_item;
        }
      }

      $drawer_fields = !empty($value['drawer_fields']) ? $value['drawer_fields'] : [];


      $visualn_data = [
        'resource_format' => isset($value['resource_format']) ? $value['resource_format'] : '',
        'drawer_config' => $drawer_config,
        'drawer_fields' => $drawer_fields,
      ];

      // unset the values
      unset($value['drawer_config']);
      unset($value['drawer_fields']);
      unset($value['resource_format']);

      $value['visualn_data'] = serialize($visualn_data);
      // @todo: add comment
      $value += ['options' => []];
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

    return $element['drawer_container'];
  }

}
