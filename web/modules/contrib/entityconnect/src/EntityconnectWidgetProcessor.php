<?php

namespace Drupal\entityconnect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Views;

/**
 * A reference field widget processing class for entityconnect module.
 */
class EntityconnectWidgetProcessor {

  /**
   * The entity reference field definition.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldDefinition;

  /**
   * The entity reference field widget element.
   *
   * @var array
   */
  protected $widget;

  /**
   * The entityconnect settings array.
   *
   * @var array
   */
  protected $entityconnectSettings;

  /**
   * The target entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The target entity bundles.
   *
   * @var array
   */
  protected $acceptableTypes;

  /**
   * Constructs a EntityconnectWidgetProcessor object.
   *
   * @param \Drupal\field\Entity\FieldConfig $field_definition
   *   The entity reference field definition.
   * @param array $widget
   *   The entity reference field widget form element.
   */
  public function __construct(FieldConfig $field_definition, array $widget) {
    $this->fieldDefinition = $field_definition;
    $this->widget = $widget;

    // Initialize entityconnect settings on the field.
    $this->entityconnectSettings = $this->fieldDefinition->getThirdPartySettings('entityconnect');
    // Use global defaults if no settings on the field.
    if (!$this->entityconnectSettings) {
      $this->entityconnectSettings = \Drupal::config('entityconnect.administration_config')->get();
    }

    // Initialize the target entity type and bundles.
    $this->initTargetInfo();
  }

  /**
   * Form API callback: Processes an entity_reference field element.
   *
   * Adds entityconnect buttons to the field.
   *
   * This method is assigned as a #process callback in
   * entityconnect_form_alter() function.
   *
   * @param array $element
   *   The widget container element to attach the buttons.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent entity form state.
   * @param array $form
   *   The parent entity form.
   *
   * @return array
   *   The altered element.
   */
  public static function process(array $element, FormStateInterface $form_state, array $form) {

    $entity = $form_state->getFormObject()->getEntity();
    $fieldDefinition = $entity->getFieldDefinition($element['widget']['#field_name']);

    // Instantiate this class so we don't have to pass variables around.
    $widgetProcessor = new EntityconnectWidgetProcessor($fieldDefinition, $element['widget']);

    // Give other contrib modules the chance to change the target.
    $entityType = $widgetProcessor->getEntityType();
    $acceptableTypes = $widgetProcessor->getAcceptableTypes();
    $data = array(
      'entity_type' => &$entityType,
      'acceptable_types' => &$acceptableTypes,
      'field' => $fieldDefinition,
    );
    \Drupal::moduleHandler()->alter('entityconnect_field_attach_form', $data);
    $widgetProcessor->setEntityType($data['entity_type']);
    $widgetProcessor->setAcceptableTypes($data['acceptable_types']);

    // We currently should separate Autocomplete widget from others
    // because "Edit" button will not react well on multiple selected items.
    // Autocomplete widget has no #type value, so we are testing it
    // via $element['widget']['#type']. This does not apply to
    // Autocomplete Tags widget so we have to check for target_id too.
    if (isset($element['widget']['#type']) || isset($element['widget']['target_id'])) {
      $widgetProcessor->attachButtons($element);
    }
    else {
      foreach (Element::getVisibleChildren($element['widget']) as $key) {
        if (is_numeric($key)) {
          $widgetProcessor->attachButtons($element, $key);
        }
      }
    }

    return $element;
  }

  /**
   * Attach the entity connect buttons to a single widget element.
   *
   * @param array $element
   *   The widget element to attach the button to.
   * @param string $key
   *   The key of an autocomplete widget element.
   */
  protected function attachButtons(array &$element, $key = 'all') {

    // Get the parents.
    $parents = '';
    if (isset($this->widget['#field_parents'])) {
      foreach ($this->widget['#field_parents'] as $parent) {
        $parents .= ($parents ? '-' : '') . $parent;
      }
    }

    $fieldStorage = $this->fieldDefinition->getFieldStorageDefinition();
    $extraClass = isset($this->widget['#type']) ? $this->widget['#type'] : 'autocomplete';
    $extraClass .= $fieldStorage->getCardinality() > 1 ? ' multiple-values' : ' single-value';
    $extraClass .= (isset($this->widget['#multiple']) && $this->widget['#multiple'] == TRUE) ? ' multiple-selection' : ' single-selection';
    if (isset($this->widget['#type'])) {
      if ((isset($this->widget['#multiple']) && $this->widget['#multiple'] == TRUE) || $this->widget['#type'] == 'radios' || $this->widget['#type'] == 'checkboxes') {
        $element['#attributes']['class'][] = 'inline-label';
      }
    }

    // Set the class strings for the button.
    $buttonClasses = array(
      'extra_class' => $extraClass,
      'parents_class' => $parents,
    );

    // Set the correct element to attach to.
    if ($key === 'all') {
      // Options widget.
      if (isset($this->widget['#type'])) {
        $widgetElement = &$element;
      }
      // Autocomplete Tags widget.
      else {
        $widgetElement = &$element['widget'];
      }
    }
    // Autocomplete widget.
    else {
      $widgetElement = &$element['widget'][$key];
    }

    $this->attachAddButton($widgetElement, $buttonClasses, $key);
    $this->attachEditButton($widgetElement, $buttonClasses, $key);

  }

  /**
   * Attach the Add button.
   *
   * @param array $element
   *   The widget container element.
   * @param string $entityconnect_classes
   *   Button CSS definition array:
   *   - 'extra_class': extra css class string
   *   - 'parents_class': parents class string.
   * @param string $key
   *   Default is 'all' (optional).
   */
  protected function attachAddButton(array &$element, $entityconnect_classes, $key = 'all') {

    // Button values are opposite; 0=On, 1=Off.
    $addbuttonallowed = !$this->entityconnectSettings['buttons']['button_add'];
    $addIcon = $this->entityconnectSettings['icons']['icon_add'];

    // Get the subset of target bundles the user has permission to create.
    $acceptableTypes = array();

    if (!$this->acceptableTypes) {
      // @FIXME: The acceptable types is ALL so check the access for all.
      if (\Drupal::entityTypeManager()
        ->getAccessControlHandler($this->entityType)
        ->createAccess($this->entityType)
      ) {
        $acceptableTypes[] = $this->entityType;
      }
    }
    else {
      foreach ($this->acceptableTypes as $bundle) {
        if (\Drupal::entityTypeManager()
          ->getAccessControlHandler($this->entityType)
          ->createAccess($bundle)
        ) {
          $acceptableTypes[] = $bundle;
        }
      }
    }
    // Now we need to make sure the user should see this button.
    if (\Drupal::currentUser()->hasPermission('entityconnect add button') && $addbuttonallowed && $acceptableTypes) {
      // Determine how the button should be displayed.
      if (isset($addIcon)) {
        if ($addIcon == '0') {
          $classes = $entityconnect_classes['extra_class'] . ' add-icon';
        }
        elseif ($addIcon == '1') {
          $classes = $entityconnect_classes['extra_class'] . ' add-icon add-text';
        }
        else {
          $classes = $entityconnect_classes['extra_class'];
        }
      }

      // Build the button name.
      $button_name = "add_entityconnect__{$this->fieldDefinition->getName()}_{$key}_{$entityconnect_classes['parents_class']}";

      // Build the button element.
      $element[$button_name] = array(
        '#type' => 'entityconnect_submit',
        '#value' => t('New content'),
        '#name' => $button_name,
        '#prefix' => "<div class = 'entityconnect-add $classes'>",
        '#suffix' => '</div>',
        '#key' => $key,
        '#field' => $this->fieldDefinition->getName(),
        '#entity_type_target' => $this->entityType,
        '#acceptable_types' => $acceptableTypes,
        '#add_child' => TRUE,
        '#weight' => 1,
      );

      // Button should be at same form level as widget,
      // or text box if multivalue autocomplete field.
      $parents = $this->widget['#parents'];
      if (is_numeric($key)) {
        $parents[] = $key;
      }
      $element[$button_name]['#parents'] = array_merge($parents, array($button_name));

    }
  }

  /**
   * Attach the edit button.
   *
   * @param array $element
   *   The widget container element.
   * @param string $entityconnect_classes
   *   Button CSS definition array:
   *   - 'extra_class': extra css class string
   *   - 'parents_class': parents class string.
   * @param int|string $key
   *   Target entity id (optional).
   */
  protected function attachEditButton(array &$element, $entityconnect_classes, $key = 'all') {

    // Button values are opposite; 0=On, 1=Off.
    $editbuttonallowed = !$this->entityconnectSettings['buttons']['button_edit'];
    $editIcon = $this->entityconnectSettings['icons']['icon_edit'];

    // Now we need to make sure the user should see this button.
    if (\Drupal::currentUser()->hasPermission('entityconnect edit button') && $editbuttonallowed) {
      // Determine how the button should be displayed.
      if (isset($editIcon)) {
        if ($editIcon == '0') {
          $classes = $entityconnect_classes['extra_class'] . ' edit-icon';
        }
        elseif ($editIcon == '1') {
          $classes = $entityconnect_classes['extra_class'] . ' edit-icon edit-text';
        }
        else {
          $classes = $entityconnect_classes['extra_class'];
        }
      }

      // Build the button name.
      $button_name = "edit_entityconnect__{$this->fieldDefinition->getName()}_{$key}_{$entityconnect_classes['parents_class']}";

      // Build the button element.
      $element[$button_name] = array(
        '#type' => 'entityconnect_submit',
        '#value' => t('Edit content'),
        '#name' => $button_name,
        '#prefix' => "<div class = 'entityconnect-edit $classes'>",
        '#suffix' => '</div>',
        '#key' => $key,
        '#field' => $this->fieldDefinition->getName(),
        '#entity_type_target' => $this->entityType,
        '#acceptable_types' => $this->acceptableTypes,
        '#add_child' => FALSE,
        '#weight' => 1,
      );

      // Button should be at same form level as widget,
      // or text box if multivalue autocomplete field.
      $parents = $this->widget['#parents'];
      if (is_numeric($key)) {
        $parents[] = $key;
      }
      $element[$button_name]['#parents'] = array_merge($parents, array($button_name));

    }
  }

  /**
   * Returns the array of acceptable target bundles.
   *
   * @return array
   *   Array of acceptable bundles.
   */
  public function getAcceptableTypes() {
    return $this->acceptableTypes;
  }

  /**
   * Returns the target entity type.
   *
   * @return string
   *   Target entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Sets the target entity type.
   *
   * @param string $entityType
   *   Target entity type.
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

  /**
   * Sets the target bundles.
   *
   * @param array $acceptableTypes
   *   Array of acceptable bundles.
   */
  public function setAcceptableTypes(array $acceptableTypes) {
    $this->acceptableTypes = $acceptableTypes;
  }

  /**
   * Initialize entityType and targetBundles from the handler settings.
   */
  protected function initTargetInfo() {
    $targetSettings = $this->fieldDefinition->getSettings();
    $this->entityType = $targetSettings['target_type'];
    $this->acceptableTypes = array();

    // If this is the default setting then just get the target bundles.
    if (isset($targetSettings['handler_settings']['target_bundles'])) {
      if (!is_null($targetSettings['handler_settings']['target_bundles'])) {
        $this->acceptableTypes = $targetSettings['handler_settings']['target_bundles'];
      }
      else {
        // Use the entity type if the target entity has no bundles.
        $this->acceptableTypes[] = $this->entityType;
      }
    }
    // If this is an entity_reference view, then try getting the target bundles
    // from the filter.
    elseif ($targetSettings['handler'] == 'views') {
      $view = Views::getView($targetSettings['handler_settings']['view']['view_name']);
      // Get filters from the entity_reference display.
      $viewDisplay = $view->storage->getDisplay($targetSettings['handler_settings']['view']['display_name']);
      if (!isset($viewDisplay['display_options']['filters'])) {
        // Get filters from the Master display.
        $viewDisplay = $view->storage->getDisplay('default');
      }

      switch ($this->entityType) {
        // Type(bundle) value is under vid key for taxonomy terms.
        case 'taxonomy_term':
          if (isset($viewDisplay['display_options']['filters']['vid'])) {
            $this->acceptableTypes = $viewDisplay['display_options']['filters']['vid']['value'];
          }
          break;

        // Otherwise, type(bundle) value is under type key.
        default:
          if (isset($viewDisplay['display_options']['filters']['type'])) {
            $this->acceptableTypes = $viewDisplay['display_options']['filters']['type']['value'];
          }
          // $this->acceptableTypes was already set to empty array before.
          break;
      }
    }
  }

}
