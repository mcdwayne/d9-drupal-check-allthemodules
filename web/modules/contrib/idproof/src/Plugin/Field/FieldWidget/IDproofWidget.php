<?php

namespace Drupal\idproof\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 *
 * @FieldWidget(
 *   id = "idproof_widget",
 *   label = @Translation("IDproof Widget"),
 *   field_types = {
 *     "idproof"
 *   }
 * )
 */
 class IDproofWidget extends WidgetBase {
   /**
    * Define the form for the field type.
    *
    * Inside this method we can define the form used to edit the field type.
    */
    public function formElement(FieldItemListInterface $items, $delta, Array $element, Array &$form, FormStateInterface $formState ) {
      $field_settings = $this->getFieldSettings();
      $field_name = $this->fieldDefinition->getName();
      $idproof = isset($items[$delta]->idproof) ? $items[$delta]->idproof : NULL;
      $title = $items[$delta]->getFieldDefinition()->getLabel();
      $required = $items[$delta]->getFieldDefinition()->isRequired();
      $desc = $items[$delta]->getFieldDefinition()->getDescription();
      $element_idproof_id = Html::getUniqueId("edit-{$field_name}-{$delta}-idproof");
      $element['#type'] = 'container';
      $element['#element_validate'] = [[get_class($this), 'validateIDproof']];
      $element['label'] = [
        '#type' => 'label',
        '#title' => $this->fieldDefinition->getLabel(),
        '#attributes' => [
          'for' => $element_idproof_id,
        ],
        '#required' => $element['#required'],
        '#prefix' => '<div class="col-md-2">',
        '#suffix' => '</div>'
      ];
      $element['idproof'] = array(
        '#type' => 'select',
        '#title' => t(''),
        '#title_display' => 'hidden',
        '#id' => $element_idproof_id,
        '#id' => "idproof-js",
        '#default_value' => $idproof,
        '#required' => $required,
        '#description' => $desc,
        '#empty_value' => '',
        '#options' => [
          'Adhaar(UID)' => 'Adhaar(UID)',
          'Passport' => 'Passport',
          'Driving License' => 'Driving License',
          'Income Tax PAN Card' => 'Income Tax PAN Card',
           'Photo Identity Card (of Central Govt. /PSU or State Govt.)' => 'Photo Identity Card (of Central Govt. /PSU or State Govt.)',
          'Current  passbook of Post Office / any scheduled Bank, having Photo' => 'Current  passbook of Post Office / any scheduled Bank, having Photo',
          'Election Commission ID Card' => 'Election Commission ID Card',
          'Ration Card with Photo' => 'Ration Card with Photo',
          'Photo Identity Card issued by Govt. recognize Educational Institutions (for students only)' => 'Photo Identity Card issued by Govt. recognize Educational Institutions (for students only)',
          'Birth Certificate' => 'Birth Certificate',
          'Marriage Certificate' => 'Marriage Certificate',
          'Other' => 'Other',
        ],
        '#required' => false,
        '#empty_option' => '-Please Select-',
        '#prefix' => '<div class="col-md-4">',
        '#suffix' => '</div>'
      );

      $element_idproof_other_id = Html::getUniqueId("edit-{$field_name}-{$delta}-idproofother");
      $idproof_other = isset($items[$delta]->idproofother) ? $items[$delta]->idproofother : NULL;
      $placeholder = $this->getSetting('placeholder');
      $placeholder = isset($placeholder)? $placeholder:"ID Proof Other";
      $element['#element_validate'] = [[get_class($this), 'validateIDproof']];
      $element['idproofother'] = [
        '#type' => 'textfield',
        '#title' => t(''),
        '#id' => $element_idproof_other_id,
        '#id' => "idproofother-js",
        '#title_display' => 'hidden',
        '#default_value' => $idproof_other,
        '#placeholder' => $placeholder,
        '#required' => false,
        '#prefix' => '<div class="col-md-3">',
        '#suffix' => '</div>'
      ];

      $element_iddetails_id = Html::getUniqueId("edit-{$field_name}-{$delta}-iddetails");
      $iddetails = isset($items[$delta]->iddetails) ? $items[$delta]->iddetails : NULL;
      $placeholder = $this->getSetting('placeholder');
      $placeholder = isset($placeholder)? $placeholder:"ID Details";
      $element['#element_validate'] = [[get_class($this), 'validateIDproof']];
      $element['iddetails'] = [
        '#type' => 'textfield',
        '#title' => t(''),
        '#id' => $element_iddetails_id,
        '#id' => "iddetails-js",
        '#title_display' => 'hidden',
        '#default_value' => $iddetails,
        '#placeholder' => $placeholder,
        '#required' => false,
        '#prefix' => '<div class="col-md-3">',
        '#suffix' => '</div>'
      ];

      $element['#attached']['library'][] = 'idproof/identity_proof_field_type_libs';
      return $element;
    }

    public static function validateIDproof($element, FormStateInterface $form_state) {
      //kint($element);
    $is_required = $element['#required'];
    $idproof = isset($element['idproof']['#value']) ? $element['idproof']['#value'] : null;
    $idproofother = isset($element['idproofother']['#value']) ? $element['idproofother']['#value'] : null;
    $iddetails = isset($element['iddetails']['#value']) ? $element['iddetails']['#value'] : null;

    if($is_required && empty($idproof)) {
      $form_state->setError($element, t('@name is required.', ['@name' => $element['#title']]));
    }

    if($is_required && empty($iddetails)) {
      $form_state->setError($element, t('@name is required.', ['@name' => $element['#title']]));
    }

    if($idproof == "Other") {
    if($is_required && empty($idproofother)) {
      $form_state->setError($element, t('@name is required.', ['@name' => $element['#title']]));
    }
    }

    if($idproof == "Adhaar(UID)")
    {
      if(strlen($iddetails) != 12 && $iddetails != null)
      {
       $form_state->setError($element, t('Aadhar Number is not valid.'));
      }
      if(preg_match('/[a-zA-Z]/',$iddetails) || preg_match('/[^\d]/',$iddetails) ) {
        $form_state->setError($element, t('Aadhar Number is not valid.'));
      }
    }

    }


    /**
     * {@inheritdoc}
     */
    public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
      return parent::errorElement($element, $error, $form, $form_state);
    }
  }
