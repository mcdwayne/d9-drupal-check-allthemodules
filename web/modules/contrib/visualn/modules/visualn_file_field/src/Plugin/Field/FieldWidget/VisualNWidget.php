<?php

// @todo: rename the file to VisualNFileWidget.php
// @todo: fix bugs:
//    @see https://www.drupal.org/project/drupal/issues/2926219
//    @see https://www.drupal.org/project/drupal/issues/2926220

namespace Drupal\visualn_file_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
//use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\visualn_file_field\Plugin\FileWidgetWrapper;
use Symfony\Component\HttpFoundation\Request;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\Core\Field\WidgetBase;
use Drupal\visualn\Helpers\VisualN;

/**
 * Plugin implementation of the 'visualn_visualn' widget.
 *
 * @FieldWidget(
 *   id = "visualn_file",
 *   label = @Translation("VisualN file"),
 *   field_types = {
 *     "visualn_file"
 *   }
 * )
 */
//class VisualNWidget extends FileWidget {
class VisualNWidget extends FileWidgetWrapper {

  const RAW_RESOURCE_FORMAT_GROUP = 'visualn_file_widget';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'visualn_style_id' => '',
      'visualn_data' => '',
    ) + parent::defaultSettings();
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

    // exclude element with no files uploaded ("add more" button)
    if (empty($item->fids)) {
      return $element;
    }

    // @todo: check the same issue https://drupal.stackexchange.com/questions/235459/get-previous-values-for-inline-entity-form-items-when-parent-form-submitted

    // @todo: Basically it is a hack to keep initial field configuration for selected visualn style
    //    to be able to return to it when user changes selected style and then goes back.
    //    We can avoid overriding $item->visualn_data and visualn_style_id here and just create another
    //    standard object (or an array) to pass the initial config to #process callback (see below
    //    for $element['drawer_container']['#item']).
    //    The issue is related to FileWidet::submit() and maybe FileWidget::extractFormValues().
/*
    if (!isset($item->visualn_data_storage)) {
      $item->visualn_data_storage = $item->visualn_data;
    }
    //$item->visualn_data = $item->visualn_data_storage;
    if (!isset($item->visualn_style_id_storage)) {
      $item->visualn_style_id_storage = $item->visualn_style_id;
    }
    //$item->visualn_style_id = $item->visualn_style_id_storage;
*/

    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    //$visualn_data = !empty($item->visualn_data_storage) ? unserialize($item->visualn_data_storage) : [];
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';

    // @todo: it is a weird behaviour somehow connected with adding/removing new files and rebuilding the
    //    whole widget form. For details see FileWidget::submit(), where it unsets user input with comment
    //    that "The rebuilt elements will have #default_value set appropriately for the current state of the field,
    //    so nothing is lost in doing this."
    // @todo: see FileWidget::submit()

/*
    // We can't use here visual_data key directly because it will be overridden by actual form values.
    $element['visualn_style_id_storage'] = [
      '#type' => 'hidden',
      //'#default_value' => $item->visualn_data_storage ? : $item->visualn_data,
      '#default_value' => $item->visualn_style_id_storage,
    ];
    $element['visualn_data_storage'] = [
      '#type' => 'hidden',
      //'#default_value' => $item->visualn_data_storage ? : $item->visualn_data,
      '#default_value' => $item->visualn_data_storage,
    ];
*/

    // @todo: together with the FileWidget::submit() override in FileWidgetWrapper this
    // is a hack to keep visualn_data between requests (especially when uploading new files)
    // @todo: !important: leaving it like this may potentially cause security issues since
    //    configuration may containt data that shouldn't be accessible by user
    //    (that may have access to the widget form).
/*
    $element['visualn_data'] = [
      '#type' => 'hidden',
      '#default_value' => $item->visualn_data,
    ];
*/
    $element['visualn_data'] = [
      '#type' => 'value',
      '#value' => $item->visualn_data,
    ];



    // @todo: all this deserves more attention, it seems to be very buggy, at least
    //    what relates to retrieving original visualn_style_id and visualn_data from storage



/*
    $initial_config_item = new \StdClass();
    $initial_config_item->visualn_style_id = $item->visualn_style_id_storage;
    $initial_config_item->visualn_data = $item->visualn_data_storage;
*/

    //dsm($item->visualn_style_id);
    //dsm($item->visualn_data);

    // @todo: this is a copy of VisualNResourceWidget so may be moved into the \VisualNFormsHelper class
    //    except the last line with setting #item key to initial_config_item
    //    also here visualn_style_id is used from original value which resembles actual state of the
    //    form. For more detail see FileWidget::submit().

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

    // @todo: it is not clear why it saving the values (actually why it works as it should) since
    //    in ResourceGenericDrawingFetcher the drawer_container part should have been moved into a separate #process callback
    $visualn_style_id = $item->visualn_style_id ?: '';
    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-' . $delta . '-drawer-config-ajax-wrapper';
    $visualn_styles = visualn_style_options(FALSE);
    asort($visualn_styles);
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
    //$element['drawer_container']['#item'] = $initial_config_item;

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    //parent::extractFormValues($items, $form, $form_state);
    //return;

    // @todo: needs to open an issue on the FileWidget?
    // This is not a static method, this is a way to call parent class for FileWidget
    // since FileWidget::extractFormValues() overrides initial $items values with current ones
    // and thus we can't get initial values for drawer_plugin initialization and checking if style select
    // was changed.
    WidgetBase::extractFormValues($items, $form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    //$new_values = parent::massageFormValues($values, $form, $form_state);
    $values = parent::massageFormValues($values, $form, $form_state);
    // @todo: get drawer config values and attach to $new_values
    foreach ($values as &$value) {
      //$value['uri'] = static::getUserEnteredStringAsUri($value['uri']);
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


  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  // @todo: mostly this is a copy ResourceGenericDrawerFetcher::processDrawerContainerSubform()
  public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $item = $element['#item'];
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';
    $visualn_data['drawer_config'] = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
    $visualn_data['drawer_fields'] = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];

    $configuration = $visualn_data;
    $configuration['visualn_style_id'] = $item->visualn_style_id ?: '';
    //dsm('resource id: ' . $item->visualn_style_id);
    // @todo: add visualn_style_id = "" to widget default config (check) to avoid "?:" check

    $element = VisualNFormsHelper::processDrawerContainerSubform($element, $form_state, $form, $configuration);

    return $element;
  }


  /**
   * {@inheritdoc}
   *
   * return drawerConfigForm via ajax at style change
   * @todo: Add into an interface or add description
   * @todo: Rename method if needed
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }

}
