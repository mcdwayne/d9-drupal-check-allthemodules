<?php

namespace Drupal\simpleaddress\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'simpleaddress_default' widget.
 *
 * @FieldWidget(
 *   id = "simpleaddress_default",
 *   module = "simpleaddress",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "simpleaddress"
 *   }
 * )
 */
class SimpleAddressDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'placeholder_streetAddress' => '102, Olive Grove',
      'placeholder_addressLocality' => 'Swindon',
      'placeholder_addressRegion' => 'Wiltshire',
      'placeholder_postalCode' => 'SN25 9RT',
      'placeholder_postOfficeBoxNumber' => 'P.O. Box 12345',
      'placeholder_addressCountry' => 'GB',
    ) + parent::defaultSettings();
  }
  
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['streetAddress'] = array(
      '#type' => 'textfield',
      '#title' => t('Street address'),
      '#placeholder' => $this->getSetting('placeholder_streetAddress'),
      '#default_value' => isset($items[$delta]->streetAddress) ? $items[$delta]->streetAddress : NULL,
      '#maxlength' => 255,
    );
    $element['addressLocality'] = array(
      '#type' => 'textfield',
      '#title' => t('Town/City'),
      '#placeholder' => $this->getSetting('placeholder_addressLocality'),
      '#default_value' => isset($items[$delta]->addressLocality) ? $items[$delta]->addressLocality : NULL,
      '#maxlength' => 255,
    );
    $element['addressRegion'] = array(
      '#type' => 'textfield',
      '#title' => t('Region'),
      '#placeholder' => $this->getSetting('placeholder_addressRegion'),
      '#default_value' => isset($items[$delta]->addressRegion) ? $items[$delta]->addressRegion : NULL,
      '#maxlength' => 255,
    );
    $element['postalCode'] = array(
      '#type' => 'textfield',
      '#title' => t('Postal code'),
      '#placeholder' => $this->getSetting('placeholder_postalCode'),
      '#default_value' => isset($items[$delta]->postalCode) ? $items[$delta]->postalCode : NULL,
      '#maxlength' => 255,
    );
    $element['postOfficeBoxNumber'] = array(
      '#type' => 'textfield',
      '#title' => t('P.O. Box'),
      '#placeholder' => $this->getSetting('placeholder_postOfficeBoxNumber'),
      '#default_value' => isset($items[$delta]->postOfficeBoxNumber) ? $items[$delta]->postOfficeBoxNumber : NULL,
      '#maxlength' => 255,
    );
    $element['addressCountry'] = array(
      '#type' => 'select',
      '#options' => array('' => t('- None -')) + \Drupal::service('country_manager')->getList(),
      '#title' => t('Country'),
      '#placeholder' => $this->getSetting('placeholder_addressCountry'),
      '#default_value' => isset($items[$delta]->addressCountry) ? $items[$delta]->addressCountry : '',
    );

    // If cardinality is 1, ensure a label is output for the field by wrapping it
    // in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
      );
    }

    return $element;
  }


}
