<?php

namespace Drupal\svg_icon_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\svg_icon_field\StaticIcons;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'icon_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "icon_widget_type",
 *   label = @Translation("Icon widget type"),
 *   field_types = {
 *     "icon_field_type"
 *   }
 * )
 */
class IconWidgetType extends WidgetBase implements ContainerFactoryPluginInterface {

  // This is the name of the variable in this class
  // that allows access to StaticIcons class.
  /**
   * Var staticIcon.
   *
   * @var Drupal\svg_icon_field\StaticIcons
   */
  protected $staticIcons;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, StaticIcons $staticIcons) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->static_icons = $staticIcons;
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
      $configuration['third_party_settings'],
      $container->get('svg_icon_field.static_icons')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * This form is going to appear on `Manage form display` tab in field's row
   * right after you click on cog icon.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * This summary appears on `Manage form display` tab on node/entity edit form.
   * For example, for `test` node content type it's going to be displayed on:
   * admin/structure/types/manage/test/form-display page in unnamed column.
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * Ajax callback.
   */
  public function getIconsFormItem(array &$form, FormStateInterface $form_state) {
    // IMPORTANT. To debug. ksm($form) is gonna return just a 'recursion'
    // To get to know what's inside just do ksm(array_keys($form));
    // This way you're gonna get each key of form
    // After that just do ksm($form['desired_key']); .
    //
    // To get to know the name of field we need to get it from parents of
    // triggering element.

    $element = $form_state->getTriggeringElement();
    // IMPORTANT - check #array_parents element.
    // There's full path to your element.
    $k = 1;
    $parents = '';
    foreach ($element['#array_parents'] as $parent_key => $parent) {
      if ($k != (count($element['#array_parents']))) {
        // Here we are destroying a form by overriding it,
        // but that's all we need from form.
        // Might be confusing we're returning a whole
        // form at the end, but we don't because
        // we override it here.
        $form = $form[$parent];
      }
      $k++;
    }
    return $form;
  }

  /**
   * Process form.
   */
  private function processParents($form, $parents) {
    foreach ($parents as $parent) {
      $form = $form[$parent];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the state of form, if it's in config mode or not.
    // Some functionalities vary between config and entity edit / add form.
    $is_in_config_mode = (!empty($form['#parents'][0]) && $form['#parents'][0] == 'default_value_input') ? TRUE : FALSE;
    // Get field name.
    $field_name = $items->getName();

    // Get form_state values.
    $values = $form_state->getValues();

    // Get category options.
    $category_options = $this->static_icons->getHumanReadableIconCategories();

    // Create a container used for ajax and as a grouping element
    // with the name of field.
    $element['container'] = [
      '#type' => 'fieldset',
      '#title' => $items->getFieldDefinition()->getLabel(),
      '#attributes' => ['id' => 'svg-icons-fieldset-container-' . $delta],
    ];

    // Create a group for category icons.
    $element['container']['group'] = [
      '#title' => $this->t('Select icon category'),
      '#type' => 'select',
      '#required' => $this->fieldDefinition->isRequired(),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => !empty($items[$delta]->group) ? $items[$delta]->group : NULL,
      '#options' => $category_options,
      '#ajax' => [
        'callback' => [$this, 'getIconsFormItem'],
        'event' => 'change',
        'wrapper' => 'svg-icons-fieldset-container-' . $delta,
      ],
    ];

    // Unset #empty_option if field is required and form is not in config mode.
    if ($this->fieldDefinition->isRequired() && !$is_in_config_mode) {
      unset($element['container']['group']['#empty_option']);
    }

    // If there are values in form_state.
    if (!empty($values)) {
      $parents = $element['#field_parents'];
      $parents = $this->processParents($values, $parents);

      // Field settings page.
      if (!empty($values['default_value_input'])) {
        $path = !empty($values['default_value_input'][$field_name][$delta]['container']['group']) ? $this->static_icons->getCategoryLocation($values['default_value_input'][$field_name][$delta]['container']['group']) : NULL;
        $options = $this->static_icons->getIcons($path);
        $attribution = $this->static_icons->getCategoryAttribution($values['default_value_input'][$field_name][$delta]['container']['group']);
      }
      // Entity add / edit page.
      else {
        // Here we need to care about two scenarios. First one is the
        // simple ajax processed field.
        //
        // Let's say you added a field to a content type
        // and it's loaded right after the page has loaded. In that case
        // we can grab a value from $values array using $parents element
        // to process it.
        //
        // However there might be a case where a field that you've added
        // is loaded on ajax request. Example would be adding this field to
        // custom paragraph. Then this paragraph with the field is referenced
        // in some content type. In tht situation the field is not going
        // to appear right after the page has loaded. The field is going
        // to be rendered after clicking "Add new paragraph" button or
        // similar which is ajax generated. So, to load previously saved values
        // we have to rely on $items[$delta]->group value rather then
        // $parents[$field_name][$delta]['container']['group'] value, because
        // $parents[$field_name][$delta]['container']['group'] does not
        // exist yet even if
        // $values exists and $values exists only because the field
        // was rendered by ajax provided by paragraphs. It's quite complicated.
        //
        //
        // Get icons based on group value.
        if (!empty($parents[$field_name][$delta]['container']['group'])) {
          $path = $this->static_icons->getCategoryLocation($parents[$field_name][$delta]['container']['group']);
          $attribution = $this->static_icons->getCategoryAttribution($parents[$field_name][$delta]['container']['group']);
          // @todo
          // Find out way to get default_value (saved_value). This value is
          // disappearing right after the ajax is triggered.
        }
        // In this case we assume $items[$delta]->group value exists.
        else {
          $path = $this->static_icons->getCategoryLocation($items[$delta]->group);
          $attribution = $this->static_icons->getCategoryAttribution($items[$delta]->group);
          $default_value = !(empty($items[$delta]->icon)) ? $items[$delta]->icon : NULL;
        }

        $options = $this->static_icons->getIcons($path);
      }
    }
    // If there's no values / form just loaded.
    else {
      // Get default options.
      $path = !empty($items[$delta]->group) ? $this->static_icons->getCategoryLocation($items[$delta]->group) : NULL;
      $options = !empty($items[$delta]->group) ? $this->static_icons->getIcons($path) : $this->static_icons->getIcons();
      $attribution = !empty($items[$delta]->group) ? $this->static_icons->getCategoryAttribution($items[$delta]->group) : NULL;
      $default_value = isset($items[$delta]->icon) ? $items[$delta]->icon : NULL;
    }

    // Checks if there's an initial data in group field and if values is empty.
    // It's applied right after the page is rendered.
    $initial_data = (!empty($items[$delta]->group)) ? TRUE : FALSE;

    // Checks if there's a data returned by ajax in values variable.
    $ajax_values = (!empty($parents[$field_name][$delta]['container']['group'])) ? TRUE : FALSE;

    // Checks if there's a data for `default_value_input`.
    // This condition refers to field settings default value form.
    $setting_form = (!empty($values['default_value_input'][$field_name][$delta]['container']['group'])) ? TRUE : FALSE;

    if ($initial_data || $ajax_values || $setting_form) {
      $element['container']['icon'] = [
        '#title' => $this->t('Select icon'),
        '#prefix' => $attribution,
        '#type' => 'radios',
        '#default_value' => $default_value,
        '#options' => $options,
        '#required' => $this->fieldDefinition->isRequired(),
        '#process' => [[$this, 'processRadios']],
      ];
    }

    $element['#attached']['library'][] = 'svg_icon_field/svg_icon_field_widget';

    return $element;
  }

  /**
   * Process radios, so it renders with icon attached in field_suffix.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = Radios::processRadios($element, $form_state, $complete_form);
    if (count($element['#options']) > 0) {
      foreach ($element['#options'] as $key => $choice) {

        $icon['icon'] = [
          '#theme' => 'svg_icon_field_widget',
          '#uri' => $element[$key]['#title'],
        ];

        // Add markup for icon.
        $element[$key]['#field_suffix'] = $icon;

        // Unset title.
        unset($element[$key]['#title']);
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Becasue of 'group' and 'icon' elements defined in formElement method
    // are wrapped in container (fieldset) values passed to field (when save)
    // needs to be moved up from container, so it's ready to save.
    foreach ($values as &$value) {
      $value['group'] = (!empty($value['container']['group'])) ? $value['container']['group'] : '';
      $value['icon'] = (!empty($value['container']['icon'])) ? $value['container']['icon'] : '';
    }
    return $values;
  }

}
