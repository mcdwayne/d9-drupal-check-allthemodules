<?php

namespace Drupal\field_addresstw\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'FieldAddresstwWidget' widget.
 *
 * @FieldWidget(
 *   id = "FieldAddresstwWidget",
 *   module = "field_addresstw",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "field_addresstw"
 *   }
 * )
 */
class FieldAddresstwWidget extends WidgetBase {
    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

      $field_id = str_replace("_", "-", $this->fieldDefinition->getName()) . '-' . $items->getLangcode() . '-' . $delta;
      $label = $items->getFieldDefinition()->getLabel();
      $description = $items->getFieldDefinition()->getDescription();
      $addresstw = isset($items[$delta]->addresstw) ? $items[$delta]->addresstw : '';
      $county = isset($items[$delta]->county) ? $items[$delta]->county : '';
      $zipcode = isset($items[$delta]->zipcode) ? $items[$delta]->zipcode : '';
      $district = isset($items[$delta]->district) ? $items[$delta]->district : '';

      $bundle_id = $items->getFieldDefinition()->getTargetBundle();
      isset($element['#field_parents']) ? $bundle_id = implode('-', $element['#field_parents']) : $bundle_id = '';
      $divid = 'div-' . $field_id . '-' . $bundle_id . '-addresstw';
      $CountyId = 'edit-' . $field_id . '-' . $bundle_id . '-addresstw-county';
      $districtId = 'edit-' . $field_id . '-' . $bundle_id . '-addresstw-district';
      $zipcodeId = 'edit-' . $field_id . '-' . $bundle_id . '-addresstw-zipcode';

      $element['zipcode'] = $element + [
        '#prefix' => '<div class="addresstw_selection_wrapper" id="'. $divid .'"><div class="address_twzipcode"></div>',
        '#type' => 'textfield',
        '#default_value' => $zipcode,
        '#attributes' => ['class' => ['edit-zipcode visually-hidden'], 'id' => [$zipcodeId]],
        '#title_display' => 'invisible',
        '#weight' => 0,
        '#attached' => [
          'library' => [
            'field_addresstw/zipcodetw',
            'field_addresstw/widgetjs'
          ],
        ],
      ];
      $element['county'] = [
        '#type' => 'textfield',
        '#title_display' => 'invisible',
        '#default_value' => $county,
        '#weight' => 1,
        '#attributes' => ['class' => ['edit-county visually-hidden'], 'id' => [$CountyId]],
      ];
      $element['district'] = [
        '#type' => 'textfield',
        '#title_display' => 'invisible',
        '#default_value' => $district,
        '#weight' => 2,
        '#attributes' => ['class' => ['edit-district visually-hidden'], 'id' => [$districtId]],
      ];
      $element['addresstw'] = [
        '#type' => 'textfield',
        '#title_display' => 'invisible',
        '#suffix' => '</div>',
        '#default_value' => $addresstw,
        '#weight' => 100,
        '#attributes' => [
          'class' => ['twzipcode-address'],
          'placeholder'=>t('Your Address')
        ],
        '#size' => 30,
        '#maxlength' => 30,
      ];

      return $element;
    }
  }