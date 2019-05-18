<?php

namespace Drupal\commerce_rental\Plugin\Field\FieldWidget;

use Drupal\commerce_price\Plugin\Field\FieldWidget\PriceDefaultWidget;
use Drupal\commerce_rental\Entity\RentalPeriod;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "rental_rate_default",
 *   label = @Translation("Rental Rate"),
 *   description = @Translation("A rental period with a price."),
 *   field_types = {
 *     "commerce_rental_rate"
 *   }
 * )
 */
class RentalRateWidget extends PriceDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['period_id'] = [
      '#title' => t('Rental Period'),
      '#type' => 'select',
      '#options' => $this->getRentalPeriodOptionsList(),
      '#default_value' => isset($items[$delta]) ? $items[$delta]->period_id : '',
    ];
    $element += [
      '#title' => t('Price'),
      '#type' => 'commerce_price',
      '#available_currencies' => array_filter($this->getFieldSetting('available_currencies')),
    ];
    if (!$items[$delta]->isEmpty()) {
      $element['#default_value'] = $items[$delta]->toPrice()->toArray();
    }


    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $max = $field_state['items_count'] == 0 ? 1 : $field_state['items_count'];
    $is_multiple = TRUE;

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

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

        if ($element) {
          // Input field for the delta (drag-n-drop reordering).
          if ($is_multiple) {
            // We name the element '_weight' to avoid clashing with elements
            // defined by widget.
            $element['_weight'] = [
              '#type' => 'weight',
              '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
              '#title_display' => 'invisible',
              // Note: this 'delta' is the FAPI #type 'weight' element's property.
              '#delta' => $max,
              '#default_value' => $items[$delta]->_weight ?: $delta,
              '#weight' => 100,
            ];
          }

          $elements[$delta] = $element;
        }
      }
    }

    $field_state['items_count'] = $max;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#element_validate' => [
          [static::class, 'validate'],
        ],
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, [$field_name]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add Rental Rate'),
          '#attributes' => ['class' => ['field-add-more-submit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name])],
          '#submit' => [[get_class($this), 'addMoreSubmit']],
          '#ajax' => [
            'callback' => [get_class($this), 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * Make sure a rental period is not selected more than once.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function validate(array $form, FormStateInterface $form_state) {
    // @TODO: Should we enforce that rental periods with higher time units have a greater price value than lesser rental periods?
    // e.g. -- don't allow weekly rate price to be greater than daily rate price because it doesnt make sense
    // daily rate: Time Units = 1 Day, Price = $75
    // weekly rate: Time Units = 7 Days, Price = $50
    $values = $form_state->getValue($form['#parents']) ;
    $period_ids = [];
    foreach ($values as $delta => $value) {
      if (is_array($value) && !empty($value['period_id'])) {
        if (in_array($value['period_id'], $period_ids)) {
          $form_state->setError($form[$delta], t('Please choose a rental period that has not already been selected.'));
        }
        $period_ids[$delta] = $value['period_id'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }


  protected function getRentalPeriodOptionsList() {
    $options = [];
    $rental_periods = RentalPeriod::loadMultiple();
    /** @var \Drupal\commerce_rental\Entity\RentalPeriod $rental_period */
    foreach ($rental_periods as $rental_period) {
      $options[$rental_period->bundle()][$rental_period->id()] = $rental_period->label();
    }
    return $options;
  }

}