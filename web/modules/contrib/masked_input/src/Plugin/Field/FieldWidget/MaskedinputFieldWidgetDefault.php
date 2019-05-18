<?php

namespace Drupal\masked_input\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
//use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

use Drupal\Core\Url;

/**
 * Plugin implementation of the 'masked_input_field_widget_default default' widget.
 *
 * @FieldWidget(
 *   id = "masked_input_field_widget_default default",
 *   label = @Translation("Masked input"),
 *   field_types = {
 *     "text",
 *     "string"
 *   },
 * )
 */

class MaskedinputFieldWidgetDefault extends StringTextfieldWidget {
public static function defaultSettings() {
 return array(
      'size' => 60,
      'placeholder' => '',
      'mask_placeholder' => '',
      'mask' => '',
    ) + parent::defaultSettings();
  }

/**
 * {@inheritdoc}
 */
public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $definitions = masked_input_view_configured_definitions();

    $header = array(
    $this->t('Character'),
    $this->t('Regular expression'),
    $this->t('Description'),
   );

  $element['size'] = array(
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    
    $element['mask_placeholder'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maks placeholder'),
      '#default_value' => '_',
      '#description' => $this->t('Optionally, if you are not satisfied with the underscore ("_") character as a placeholder, you may pass an optional argument to the masked_input method.'),
    );
    
    $element['mask'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mask'),
      '#default_value' => $this->getSetting('mask'),
      '#description' => $this->t('Add mask.'),
    );

  $url = Url::fromRoute('masked_input.settings');
  $admin_link = \Drupal::l($this->t('admin/config/user-interface/masked_input'), $url);

  $caption = "A mask is defined by a format made up of mask literals and mask definitions. Any character not in the definitions list below is considered a mask literal. Mask literals will be automatically entered for the user as they type and will not be able to be removed by the user. Here is a list of definitions that already exist, you can create more at link $admin_link";
  
 $element['masktable'] = array(
  '#type' => 'table',
  '#caption' => $this->t($caption),
  '#header' => $header,
);	

foreach ($definitions as $i=>$rows) {

  $element['masktable'][$i]['character'] = array(
  '#markup' => $rows['0']['data'], 
  );
  
  $element['masktable'][$i]['rgx'] = array(
  '#markup' => $rows['1']['data'], 
  );
  $element['masktable'][$i]['dec'] = array(
  '#markup' => $rows['2']['data'], 
  ); 

}

    return $element;
  }
  
 public function settingsSummary() {
    $summary = array();

    $summary[] = $this->t('Textfield size: @size', array('@size' => $this->getSetting('size')));
    $placeholder = $this->getSetting('placeholder');
    $mask_placeholder = $this->getSetting('mask_placeholder');
    
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    
    if (!empty($mask_placeholder)) {
      $summary[] = $this->t('mask_placeholder: @mask_placeholder', array('@mask_placeholder' => $mask_placeholder));
    }
         
    $mask = $this->getSetting('mask');    
    if (!empty($mask)) {
      $summary[] = $this->t('Mask: @mask', array('@mask' => $mask));
    }

    return $summary;
  }  
  
/**
  * {@inheritdoc}
  */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
   // $element = [];
   $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $label = $this->fieldDefinition->getLabel();
   
    $data['masked_input']['definitions'] = array_merge(_masked_input_default_definitions(),[$this->getSetting('mask')]);
    $element_id = strtolower(str_replace(array(' ','(',')'),'-',$element['#title'])) . '-' . $element['#delta'];
    $data['masked_input']['elements'][$element_id] = array(
    'id' => $element_id,
    'mask' => $this->getSetting('mask'),
    'placeholder' => $this->getSetting('mask_placeholder'),
    ); 
    
    $element['value'] += array(
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getSetting('max_length'),
      '#mask' => $this->getSetting('mask'),
      '#default_value' => $value,
      '#id' => $element_id,
      '#attached' => array(
        'library' => array(
          'masked_input/drupal.masked_input',
        ),
        'drupalSettings' => $data,
      ),
    );
   return $element; 
  }

}
