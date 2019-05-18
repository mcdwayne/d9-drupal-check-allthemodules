<?php

namespace Drupal\commerce_rental\Plugin\Field\FieldWidget;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_rental\Entity\RentalPeriod;
use Drupal\commerce_rental\RentalRateHelper;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'commerce_rental_quantity' widget.
 *
 * @FieldWidget(
 *   id = "rental_quantity_default",
 *   label = @Translation("Rental Quantity"),
 *   field_types = {
 *     "commerce_rental_quantity",
 *   }
 * )
 */
class RentalQuantityWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new ProductVariationWidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityRepository = $entity_repository;
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
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
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'placeholder' => '',
        'step' => '1',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $step = $this->getSetting('step');
    $element['#element_validate'][] = [get_class($this), 'validateSettingsForm'];
    $element['allow_decimal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow decimal quantities'),
      '#default_value' => $step != '1',
    ];
    $element['step'] = [
      '#type' => 'select',
      '#title' => $this->t('Step'),
      '#description' => $this->t('Only quantities that are multiples of the selected step will be allowed.'),
      '#default_value' => $step != '1' ? $step : '0.1',
      '#options' => [
        '0.1' => '0.1',
        '0.01' => '0.01',
        '0.25' => '0.25',
        '0.5' => '0.5',
        '0.05' => '0.05',
      ],
      '#states' => [
        'visible' => [
          ':input[name="fields[quantity][settings_edit_form][settings][allow_decimal]"]' => ['checked' => TRUE],
        ],
      ],
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Validates the settings form.
   *
   * @param array $element
   *   The settings form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateSettingsForm(array $element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);
    if (empty($value['allow_decimal'])) {
      $value['step'] = '1';
    }
    unset($value['allow_decimal']);
    $form_state->setValue($element['#parents'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = t('No placeholder');
    }
    if ($this->getSetting('step') == 1) {
      $summary[] = $this->t('Decimal quantities not allowed');
    }
    else {
      $summary[] = $this->t('Decimal quantities allowed');
      $summary[] = $this->t('Step: @step', ['@step' => $this->getSetting('step')]);
    }

    return $summary;
  }

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : 0;
    $field_settings = $this->getFieldSettings();

    $element['period_id'] = [
      '#type' => 'value',
      '#value' => isset($items[$delta]) ? $items[$delta]->period_id : '',
    ];

    $element['value'] = [
      '#type' => 'number',
      '#default_value' => $value,
      '#placeholder' => $this->getSetting('placeholder'),
      '#step' => $this->getSetting('step'),
      '#min' => 0
    ];

    // Set minimum and maximum.
    if (is_numeric($field_settings['min'])) {
      $element['value']['#min'] = $field_settings['min'];
    }
    if (is_numeric($field_settings['max'])) {
      $element['value']['#max'] = $field_settings['max'];
    }

    // Add prefix and suffix.
    if ($field_settings['prefix']) {
      $prefixes = explode('|', $field_settings['prefix']);
      $element['value']['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if ($field_settings['suffix']) {
      $suffixes = explode('|', $field_settings['suffix']);
      $element['value']['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }
    return $element;
  }

  protected function getRentalPeriodOptionsList() {
    $options = [];
    $rental_periods = RentalPeriod::loadMultiple();
    /** @var \Drupal\commerce_rental\Entity\RentalPeriod $rental_period */
    foreach ($rental_periods as $rental_period) {
      $options[(int)$rental_period->bundle()][$rental_period->id()] = $rental_period->label();
    }
    return $options;
  }

  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $max = $field_state['items_count'];
    $is_multiple = TRUE;

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $user_input = $form_state->getUserInput();

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    // Try to load variation from product page.
    if ($product) {
      $variations = $this->loadEnabledVariations($product);
      if ($form_state->isRebuilding()) {
        $attribute_parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -2);
        $attribute_input = (array)NestedArray::getValue($user_input, $attribute_parents);
        $selected_variation = $this->selectVariationFromAttributeInput($variations, $attribute_input);
      } else {
        $selected_variation = $this->variationStorage->loadFromContext($product);
        // The returned variation must also be enabled.
        if (!in_array($selected_variation, $variations)) {
          $selected_variation = reset($variations);
        }
      }
    } else if ($variation_input = NestedArray::getValue($user_input, array_merge($parents, ['purchased_entity', 0, 'target_id']))) {
      $variation_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($variation_input);
      $selected_variation = ProductVariation::load($variation_id);
    } else {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $items->getEntity();
      $selected_variation = $order_item->getPurchasedEntity();
    }

    if (!empty($selected_variation)) {
      // @TODO: Use dependency injection
      $rate_helper = new RentalRateHelper();
      $rate_helper->setProductVariation($selected_variation);
      $rental_rates = $rate_helper->getRates();
      $max = count($rental_rates);
    }

    $elements = [];

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {
        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the wrapping
        // table.
        if ($is_multiple) {
          $element = [
            '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
            '#title_display' => 'invisible',
            '#description' => '',
          ];
        }
        else {
          $element = [
            '#title' => $title,
            '#title_display' => 'before',
            '#description' => $description,
          ];
        }

        $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);
        if (isset($rental_rates)) {
          $element['period_id']['#prefix'] = t($rental_rates[$delta]->getRentalPeriod()->label());
          $element['period_id']['#value'] = (int)$rental_rates[$delta]->getRentalPeriod()->id();
        }
        if ($element) {
          $elements[$delta] = $element;
        }
      }
    }

    $field_state['items_count'] = $max;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    if ($elements) {
      $elements += [
        '#type' => 'table',
        '#header' => [t('Rate'), t('Quantity')],
        '#responsive' => TRUE,
      ];
    }

    return $elements;
  }

  /**
   * Selects a product variation from user input.
   *
   * If there's no user input (form viewed for the first time), the default
   * variation is returned.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   An array of product variations.
   * @param array $user_input
   *   The user input.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The selected variation.
   */
  protected function selectVariationFromAttributeInput(array $variations, array $user_input) {
    $current_variation = reset($variations);
    if (!empty($user_input['attributes'])) {
      $attributes = $user_input['attributes'];
      foreach ($variations as $variation) {
        $match = TRUE;
        foreach ($attributes as $field_name => $value) {
          if ($variation->getAttributeValueId($field_name) != $value) {
            $match = FALSE;
          }
        }
        if ($match) {
          $current_variation = $variation;
          break;
        }
      }
    }

    return $current_variation;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order_item' && $field_name == 'rental_quantity';
  }

  /**
   * Gets the enabled variations for the product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   An array of variations.
   */
  protected function loadEnabledVariations(ProductInterface $product) {
    $langcode = $product->language()->getId();
    $variations = $this->variationStorage->loadEnabled($product);
    foreach ($variations as $key => $variation) {
      $variations[$key] = $this->entityRepository->getTranslationFromContext($variation, $langcode);
    }
    return $variations;
  }

}
