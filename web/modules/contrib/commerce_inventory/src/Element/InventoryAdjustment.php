<?php

namespace Drupal\commerce_inventory\Element;

use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;

/**
 * Provides an Commerce Inventory Adjustment form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("commerce_inventory_adjustment")
 */
class InventoryAdjustment extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    // List of InventoryAdjustment constants.
    return [
      '#field_title_format' => NULL,
      '#ajax_submission' => FALSE,
      '#hidden_fields' => [],
      '#table_format' => NULL,
      '#title_format' => NULL,
      '#default_value' => NULL,
      '#element_validate' => [
        [$class, 'validateAdjustment'],
      ],
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => [
        [$class, 'processAdjustment'],
      ],
      '#theme_wrappers' => ['fieldset'],
      '#tree' => TRUE,
    ];
  }

  /**
   * The Adjustment Type manager.
   *
   * @return \Drupal\commerce_inventory\InventoryAdjustmentTypeManager
   *   The Adjustment Type manager.
   */
  public static function getAdjustmentTypeManager() {
    return \Drupal::service('plugin.manager.commerce_inventory_adjustment_type');
  }

  /**
   * The Inventory Adjustment storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorageInterface
   *   The Inventory Adjustment storage.
   */
  public static function getAdjustmentStorage() {
    return \Drupal::entityTypeManager()->getStorage('commerce_inventory_adjustment');
  }

  /**
   * The Inventory Item storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   *   The Inventory Item storage.
   */
  public static function getInventoryItemStorage() {
    return \Drupal::entityTypeManager()->getStorage('commerce_inventory_item');
  }

  /**
   * Ensures all keys are set on the provided value.
   *
   * @param array $value
   *   The value.
   *
   * @return array
   *   The modified value.
   */
  public static function applyDefaultValues(array $value) {
    $properties = [
      'item' => NULL,
      'quantity' => NULL,
      'related_item' => NULL,
      'type' => 'increase',
    ];
    foreach ($properties as $property_key => $property) {
      if (!isset($value[$property_key])) {
        $value[$property_key] = $property;
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Ensure both the default value and the input have all keys set.
    $element['#default_value'] = (isset($element['#default_value'])) ? (array) $element['#default_value'] : [];
    $element['#default_value'] = self::applyDefaultValues($element['#default_value']);

    if ($form_state->get('force_default_entity_reload')) {
      if ($element['#default_value']['item'] instanceof InventoryItemInterface) {
        $element['#default_value']['item'] = self::getInventoryItemStorage()->load($element['#default_value']['item']->id());
      }
      if ($element['#default_value']['related_item'] instanceof InventoryItemInterface) {
        $element['#default_value']['related_item'] = self::getInventoryItemStorage()->load($element['#default_value']['related_item']->id());
      }
      $form_state->set('force_default_entity_reload', FALSE);
    }

    $element['#default_value']['item'] = self::getInventoryItemEntity($element['#default_value']['item']);
    $element['#default_value']['related_item'] = self::getInventoryItemEntity($element['#default_value']['related_item']);

    if (is_array($input) && array_key_exists('quantity', $input) && !is_null($input['quantity'])) {
      $input['quantity'] = abs($input['quantity']);
    }

    // Apply defaults to input.
    $input = (array) $input;
    $input += $element['#default_value'];
    $input['item'] = self::getInventoryItemEntity($input['item']);
    $input['related_item'] = (!is_null($input['item'])) ? self::getInventoryItemEntity($input['related_item']) : NULL;
    $input['type_plugin'] = self::getAdjustmentTypeManager()->createInstance($input['type']);

    return is_array($input) ? $input : $element['#default_value'];
  }

  /**
   * Submit callback for the "Adjust" button.
   *
   * @param array $form
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function makeAdjustment(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    // Exit early if Adjust submission wasn't clicked.
    if (!isset($triggering_element['#name']) || $triggering_element['#name'] !== $element['apply']['#name']) {
      return;
    }

    // Get values.
    $adjustment_type_id = &$element['#value']['type'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item */
    $inventory_item = &$element['#value']['item'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $related_item */
    $related_item = &$element['#value']['related_item'];
    $quantity = &$element['#value']['quantity'];

    // Create adjustment.
    self::getAdjustmentStorage()->createAdjustment($adjustment_type_id, $inventory_item, $quantity, [], $related_item);

    // Clear the input and rebuilt form.
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, $element['#parents'], '');

    $destination = \Drupal::destination();
    if ($destination->get()) {
      $path = \Drupal::destination()->get();
      $url = Url::fromUserInput($path);
      $form_state->setRedirectUrl($url);
    }
    else {
      $form_state->setRebuild();
      $form_state->set('force_default_entity_reload', TRUE);
    }
  }

  /**
   * Adds adjustment functionality to a form element.
   *
   * Properties include:
   *   - #field_title_format: String of the word format to use for each field
   *     label in the form element. Available options include 'basic',
   *     'descriptive', 'sentence', and 'default' (NULL).
   *   - #handle_submission: Boolean whether the element should handle
   *     submission and create and save a new Inventory Adjustment. Defaults to
   *     FALSE.
   *   - #title_format: String of the word format to use for the form element's
   *     label. Available options include 'hidden', 'sentence',
   *     and 'default' (NULL).
   *   - #table_format: String of the table display format to use. Available
   *     options include 'hidden', and 'default' (NULL).
   *   - #hidden_fields: An array of which fields to hide on the form. Available
   *     options include 'item', 'quantity', 'related_item', 'type'.
   *
   * Example usage:
   * @code
   *   $form['element'] = [
   *     '#type' => 'commerce_inventory_adjustment',
   *     '#default_value' => [
   *       'item' => $inventory_item_entity,
   *       'type' => 'move_to',
   *     ],
   *     '#field_title_format' => 'sentence',
   *     '#hidden_fields' => ['type', 'item'],
   *     '#title_format' => 'hidden',
   *     '#table_format' => 'hidden'
   *   ];
   * @endcode
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the #provider is missing or invalid.
   */
  public static function processAdjustment(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Default value shortcuts.
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $adjustment_item */
    $adjustment_item = &$element['#value']['item'];
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface $adjustment_type */
    $adjustment_type = &$element['#value']['type_plugin'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $related_item */
    $related_item = &$element['#value']['related_item'];
    $quantity = &$element['#value']['quantity'];

    // Initialize element settings.
    $element_wrapper_id = self::getElementId($element['#parents'], 'adjustment-ajax-wrapper');
    $element += [
      '#prefix' => '<div id="' . $element_wrapper_id . '">',
      '#suffix' => '</div>',
      '#adjustment_settings' => [],
      '#wrapper_id' => $element_wrapper_id,
      // @todo add class based on title and field format
    ];

    // Format element title.
    switch ($element['#title_format']) {
      case 'sentence':
        $title_replacements = [
          '@item' => t('(Select item)'),
          '@location' => t("(Location)"),
          '@purchasable_entity' => t("(Purchasable item)"),
          '@related_location' => t('(Select location)'),
          '@adjustment_verb' => $adjustment_type->getVerbLabel(),
          '@adjustment_preposition' => $adjustment_type->getPrepositionLabel(),
          '@related_preposition' => $adjustment_type->getRelatedPrepositionLabel(),
        ];
        if ($adjustment_item instanceof InventoryItemInterface) {
          $title_replacements['@item'] = $adjustment_item->label();
          $title_replacements['@location'] = $adjustment_item->getLocation()->label();
          $title_replacements['@purchasable_entity'] = $adjustment_item->getPurchasableEntity()->label();
        }
        if ($adjustment_type->hasRelatedAdjustmentType() && $related_item instanceof InventoryItemInterface) {
          $title_replacements['@related_location'] = $related_item->getLocation()->label();
        }
        $element_label = t($adjustment_type->getSentenceLabelTemplate(), $title_replacements);
        $element['#title'] = Unicode::ucfirst($element_label);
        break;

      case 'hidden':
        break;

      default:
        $element['#title'] = Unicode::ucfirst($adjustment_type->getLabel());
        break;
    }

    // @todo throw warning if either adjustment sends total quantity into the negative
    // @todo add comment field?

    // Add adjustment-item field.
    self::addItemField($element, $form_state);

    // Add related-item field.
    self::addRelatedItemField($element, $form_state);

    // Add quantity field.
    self::addQuantityField($element, $form_state);

    // Add submit field.
    self::addSubmitField($element, $form_state);

    // Add adjustment-type field.
    self::addTypeField($element, $form_state);

    // Add adjustment table.
    self::addTable($element, $form_state);

    return $element;
  }

  /**
   * Validation callback for an inventory adjustment element.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateAdjustment(array &$element, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $adjustment_item */
    $adjustment_item = &$element['#value']['item'];
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface $adjustment_type */
    $adjustment_type = &$element['#value']['type_plugin'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $related_item */
    $related_item = &$element['#value']['related_item'];
    $quantity = &$element['#value']['quantity'];

    // Validate quantity is not zero.
    if ($quantity == 0) {
      $quantity_path = implode('][', $element['quantity']['#parents']);
      $form_state->setErrorByName($quantity_path, t('Adjustment quantity required.'));
    }

    // Adjustment Inventory Item required. Error set by required validation.
    if (!$adjustment_item instanceof InventoryItemInterface) {
      return;
    }

    // Validate that the adjustment type is valid.
    if (!$adjustment_type instanceof InventoryAdjustmentTypeInterface) {
      $type_path = implode('][', $element['type']['#parents']);
      $form_state->setErrorByName($type_path, t('Invalid adjustment type selected.'));
      return;
    }

    // Do related adjustment validation.
    if ($adjustment_type->hasRelatedAdjustmentType()) {
      // Related Inventory Item required. Error set by required validation.
      if (!$related_item instanceof InventoryItemInterface) {
        return;
      }
      // Validate that both Inventory Items use the same purchasable entity.
      if ($adjustment_item->getPurchasableEntityTypeId() !== $related_item->getPurchasableEntityTypeId() ||
        $adjustment_item->getPurchasableEntityId() !== $related_item->getPurchasableEntityId()) {
        $related_item_path = implode('][', $element['related_item']['#parents']);
        $form_state->setErrorByName($related_item_path, t('Invalid related item selected.'));
        return;
      }
    }

  }

  /**
   * Add the Inventory Item field to the element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function addItemField(array &$element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface $adjustment_type */
    $adjustment_type = &$element['#value']['type_plugin'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $adjustment_item */
    $adjustment_item = &$element['#default_value']['item'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $related_item */
    $related_item = &$element['#value']['related_item'];

    // Set field.
    if (!in_array('item', $element['#hidden_fields'])) {
      // Format field label.
      switch ($element['#field_title_format']) {
        case 'basic':
          $field_label = t('Inventory item');
          break;

        case 'descriptive':
          $field_label = Unicode::ucfirst($adjustment_type->getLabel());
          break;

        case 'sentence':
          $field_label = Unicode::ucfirst($adjustment_type->getPrepositionLabel());
          break;

        default:
          $field_label = Unicode::ucfirst(t('@adjustment_preposition item', [
            '@adjustment_preposition' => $adjustment_type->getPrepositionLabel(),
          ]));
          break;
      }

      // Add entity reference field.
      $element['item'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $field_label,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'disable-refocus' => TRUE,
          'event' => 'blur',
          'wrapper' => $element['#wrapper_id'],
        ],
        '#default_value' => $adjustment_item,
        '#required' => TRUE,
        '#selection_settings' => [],
        '#target_type' => 'commerce_inventory_item',
        '#weight' => 6,
      ];
    }
    elseif (is_null($adjustment_item)) {
      throw new \InvalidArgumentException('Missing required adjustment item parameter.');
    }
    else {
      $element['item'] = [
        '#type' => 'value',
        '#value' => $adjustment_item,
      ];
    }
  }

  /**
   * Add the Quantity field to the element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function addQuantityField(array &$element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface $adjustment_type */
    $adjustment_type = &$element['#value']['type_plugin'];
    $adjustment_quantity = $element['#default_value']['quantity'];
    if (!is_null($adjustment_quantity)) {
      $adjustment_quantity = abs($adjustment_quantity);
    }

    // Format field label.
    switch ($element['#field_title_format']) {
      case 'sentence':
        $field_label = Unicode::ucfirst($adjustment_type->getVerbLabel());
        break;

      default:
        $field_label = t('Quantity');
    }

    // Add float field.
    $element['quantity'] = [
      '#type' => 'number',
      '#title' => $field_label,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxTableRefresh'],
        'event' => 'keyup',
        'effect' => 'fade',
        'speed' => 'fast',
      ],
      '#default_value' => $adjustment_quantity,
      '#min' => 0,
      '#required' => TRUE,
      '#step' => 'any',
      '#size' => 4,
      '#weight' => 5,
      '#attached' => [
        'library' => [
          'commerce_inventory/adjustment_ajax'
        ]
      ]
    ];
  }

  /**
   * Add the related Inventory Item field to the element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function addRelatedItemField(array &$element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $adjustment_item */
    $adjustment_item = &$element['#value']['item'];
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface $adjustment_type */
    $adjustment_type = &$element['#value']['type_plugin'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $related_item */
    $related_item = &$element['#default_value']['related_item'];

    // Exit early if there is no related adjustment.
    if (!$adjustment_type->hasRelatedAdjustmentType()) {
      return;
    }

    // Set field.
    if (!in_array('related_item', $element['#hidden_fields'])) {
      // Format field label.
      switch ($element['#field_title_format']) {
        case 'basic':
          $field_label = t('Related adjustment location');
          break;

        case 'descriptive':
          $field_label = Unicode::ucfirst(t('@adjustment_verb @related_preposition location', [
            '@adjustment_verb' => $adjustment_type->getVerbLabel(),
            '@related_preposition' => $adjustment_type->getRelatedPrepositionLabel(),
          ]));
          break;

        case 'sentence':
          $field_label = Unicode::ucfirst($adjustment_type->getRelatedPrepositionLabel());
          break;

        default:
          $field_label = Unicode::ucfirst(t('@related_preposition location', [
            '@related_preposition' => $adjustment_type->getRelatedPrepositionLabel(),
          ]));
          break;
      }

      // Add entity reference field.
      $element['related_item'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $field_label,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'disable-refocus' => TRUE,
          'event' => 'blur',
          'wrapper' => $element['#wrapper_id'],
        ],
        '#default_value' => $related_item,
        '#disabled' => (!$adjustment_item instanceof InventoryItemInterface),
        '#required' => TRUE,
        '#selection_handler' => 'commerce_inventory_item',
        '#selection_settings' => [
          'entity' => $adjustment_item,
          'label_field' => 'location_id',
          'restrict_by' => 'purchasable_entity'
        ],
        '#target_type' => 'commerce_inventory_item',
        '#weight' => 6,
      ];
    }
    elseif (is_null($related_item)) {
      throw new \InvalidArgumentException('Missing required related-adjustment item parameter.');
    }
    else {
      $element['related_item'] = [
        '#type' => 'value',
        '#value' => $related_item,
      ];
    }
  }

  /**
   * Add the submit adjustment field to the element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function addSubmitField(array &$element, FormStateInterface $form_state) {
    // Only add the submit field if this element handles adjustment creation.
    if (in_array('apply', $element['#hidden_fields'])) {
      return;
    }

    $element['apply'] = [
      '#type' => 'submit',
      '#access' => (!in_array('apply', $element['#hidden_fields'])),
      '#limit_validation_errors' => [
        $element['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'makeAdjustment'],
      ],
      '#value' => t('Adjust'),
      '#weight' => 7,
    ];

    if ($element['#ajax_submission'] == TRUE) {
      $element['apply']['#ajax'] = [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $element['#wrapper_id'],
        'event' => 'mousedown',
        'keypress' => TRUE,
        'prevent' => 'click'
      ];
    }

  }

  /**
   * Add the Adjustment Type field to the element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function addTypeField(array &$element, FormStateInterface $form_state) {
    if (!in_array('type', $element['#hidden_fields'])) {
      $element['type'] = [
        '#type' => 'select',
        '#title' => t('Adjustment'),
        '#default_value' => $element['#default_value']['type'],
        '#options' => array_map(function ($definition) {
          return $definition['label'];
        }, self::getAdjustmentTypeManager()->getDefinitions(TRUE)),
        '#weight' => 4,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $element['#wrapper_id'],
        ],
      ];
    }
    else {
      $element['type'] = [
        '#type' => 'value',
        '#value' => $element['#default_value']['type'],
      ];
    }
  }

  /**
   * Add the table to the element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function addTable(array &$element, FormStateInterface $form_state) {
    // Exit early if table is hidden.
    if ($element['#table_format'] == 'hidden') {
      return;
    }

    // Default value shortcuts.
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $adjustment_item */
    $adjustment_item = &$element['#value']['item'];
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface $adjustment_type */
    $adjustment_type = &$element['#value']['type_plugin'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $related_item */
    $related_item = &$element['#value']['related_item'];
    $quantity = &$element['#value']['quantity'];

    // Initialize table.
    $table = [
      '#type' => 'table',
      '#caption' => NULL,
      '#header' => [
        'title' => t('Location'),
        'current' => t('Current'),
        'adjustment' => t('Adjustment'),
        'updated' => t('Updated'),
      ],
      '#sticky' => FALSE,
      '#tree' => TRUE,
      '#rows' => [],
      '#weight' => 10,
    ];

    if ($adjustment_item instanceof InventoryItemInterface && $adjustment_type instanceof InventoryAdjustmentTypeInterface) {
      self::addTableRow($table, $adjustment_item, $adjustment_type->adjustQuantity($quantity, $adjustment_item->getQuantity(FALSE)));
    }

    if ($related_item instanceof InventoryItemInterface && $adjustment_type instanceof InventoryAdjustmentTypeInterface && $adjustment_type->hasRelatedAdjustmentType()) {
      self::addTableRow($table, $related_item, $adjustment_type->getRelatedAdjustmentType()->adjustQuantity($quantity, $related_item->getQuantity(FALSE)));
    }

    $element['table'] = $table;
  }

  /**
   * Add a row to the adjustment description table.
   *
   * @param array $table
   *   The table to add the row to.
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item entity that is being adjusted.
   * @param float $quantity_adjustment
   *   The numerical adjustment being made.
   *
   * @todo possible future table formats
   * FULL
   * |            |    Current | Adjustment |    Updated |
   * | -------------- Move from Item name -------------- |
   * | On-Hand    |          2 |         -3 |          5 |
   * | Available  |          1 |         -3 |          4 |
   * | ----------- Move to Another Item name ----------- |
   * | On-Hand    |          1 |         +3 |          4 |
   * | Available  |          0 |         +3 |          3 |
   *
   * Default -> has mouse-over which shows availability
   * | Location   |    Current | Adjustment |    Updated |
   * | Item Name  |          2 |         +3 |          5 |
   */
  protected static function addTableRow(array &$table, InventoryItemInterface $inventory_item, $quantity_adjustment) {
    $quantity_on_hand = $inventory_item->getQuantity(FALSE);
    $row_id_on_hand = $inventory_item->uuid() . '-on-hand';
    $text_adjustment_on_hand = ($quantity_adjustment > 0) ? '+' . $quantity_adjustment : strval($quantity_adjustment);

    $table['#rows'][$row_id_on_hand] = [
      'class' => [$row_id_on_hand],
      'data' => [
        'title' => [
          'class' => ['title'],
          'data' => $inventory_item->getLocation()->label(),
          'header' => TRUE,
        ],
        'current' => self::addTableCell($quantity_on_hand, ['current']),
        'adjustment' => self::addTableCell($text_adjustment_on_hand, ['adjustment']),
        'updated' => self::addTableCell($quantity_on_hand + $quantity_adjustment, ['updated']),
      ],
    ];
  }

  /**
   * Add a cell in a table.
   *
   * @param string $markup
   *   The inner-markup of the table cell.
   * @param array $classes
   *   An array of html classes to add to the table cell.
   *
   * @return array
   *   The created table-cell render data.
   */
  protected static function addTableCell($markup, array $classes = []) {
    return [
      'class' => $classes,
      'data' => [
        '#markup' => $markup,
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];
  }

  /**
   * Extracts the entity ID from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;

    // Take "label (entity id)', match the ID from inside the parentheses.
    // @todo Add support for entities containing parentheses in their ID.
    // @see https://www.drupal.org/node/2520416
    if (preg_match("/.+\s\(([^\)]+)\)/", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

  /**
   * Gets the current element subform.
   *
   * @param array $form
   *   The complete form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element subform.
   */
  protected static function getElementForm(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    $triggering_element_name = array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Generate a unique element ID based on an element's parent elements.
   *
   * @param array $parents
   *   The element's parents.
   * @param string $suffix
   *   The suffix to append to the ID, describing the element or action.
   *
   * @return string
   *   The element ID.
   */
  protected static function getElementId(array $parents, $suffix) {
    return Html::getUniqueId(implode('-', $parents) . '-' . $suffix);
  }

  /**
   * Process value for Inventory Item entity.
   *
   * @param mixed $value
   *   The value to validate and process.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryItemInterface|null
   *   The Inventory Item entity if found.
   */
  public static function getInventoryItemEntity($value) {
    // Exit early if there is nothing to do.
    if ($value instanceof InventoryItemInterface || is_null($value)) {
      return $value;
    }

    // Get value from 'Label (id)' entity_reference field format.
    if (is_string($value)) {
      $item_id = self::extractEntityIdFromAutocompleteInput($value);
      if (is_null($item_id) && $value = intval($value)) {
        $item_id = $value;
      }
    }
    // Get value from [0][target_id] array format.
    elseif (is_array($value)) {
      $item_id = NestedArray::getValue($value, [0, 'target_id']);
    }
    // Get value from entity ID passed in directly.
    elseif (is_int($value)) {
      $item_id = $value;
    }
    // Exit if invalid value.
    else {
      return NULL;
    }

    return self::getInventoryItemStorage()->load($item_id);
  }

  /**
   * A generic ajax refresh for use with the full form.
   *
   * @param array $form
   *   The complete form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The refreshed element form.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return self::getElementForm($form, $form_state);
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   *
   * @param array $form
   *   The complete form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public static function ajaxTableRefresh(array $form, FormStateInterface $form_state) {
    $element = self::getElementForm($form, $form_state);
    $response = new AjaxResponse();

    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $adjustment_item */
    $adjustment_item = &$element['#value']['item'];
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface $adjustment_type */
    $adjustment_type = &$element['#value']['type_plugin'];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $related_item */
    $related_item = &$element['#value']['related_item'];

    if ($adjustment_type instanceof InventoryAdjustmentTypeInterface) {
      // Update adjustment item quantity values.
      if ($adjustment_item instanceof InventoryItemInterface) {
        $item_on_hand_id = $adjustment_item->uuid() . '-on-hand';
        $item_on_hand_class = '.' . $item_on_hand_id;
        $item_on_hand_current = $element['table']['#rows'][$item_on_hand_id]['data']['current']['data']['#markup'];
        $item_on_hand_adjustment = $element['table']['#rows'][$item_on_hand_id]['data']['adjustment']['data']['#markup'];
        $item_on_hand_updated = $element['table']['#rows'][$item_on_hand_id]['data']['updated']['data']['#markup'];
        $response->addCommand(new ReplaceCommand($item_on_hand_class . ' .current div', $item_on_hand_current));
        $response->addCommand(new ReplaceCommand($item_on_hand_class . ' .adjustment div', $item_on_hand_adjustment));
        $response->addCommand(new ReplaceCommand($item_on_hand_class . ' .updated div', $item_on_hand_updated));
      }
      // Update related item quantity values.
      if ($related_item instanceof InventoryItemInterface && $adjustment_type->hasRelatedAdjustmentType()) {
        $related_on_hand_id = $related_item->uuid() . '-on-hand';
        $related_on_hand_class = '.' . $related_on_hand_id;
        $related_on_hand_current = $element['table']['#rows'][$related_on_hand_id]['data']['current']['data']['#markup'];
        $related_on_hand_adjustment = $element['table']['#rows'][$related_on_hand_id]['data']['adjustment']['data']['#markup'];
        $related_on_hand_updated = $element['table']['#rows'][$related_on_hand_id]['data']['updated']['data']['#markup'];
        $response->addCommand(new ReplaceCommand($related_on_hand_class . ' .current div', $related_on_hand_current));
        $response->addCommand(new ReplaceCommand($related_on_hand_class . ' .adjustment div', $related_on_hand_adjustment));
        $response->addCommand(new ReplaceCommand($related_on_hand_class . ' .updated div', $related_on_hand_updated));
      }
    }

    return $response;

  }

}
