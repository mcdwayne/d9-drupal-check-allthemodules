<?php
/**
 * @file
 * Contains \Drupal\vk_crosspost\Plugin\Field\FieldWidget\vk_crosspostWidget
 */
 
namespace Drupal\vk_crosspost\Plugin\Field\FieldWidget;
 
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;

/**
*@FieldWidget(
*  id = "vk_crosspostWidget",
*  label = @Translation("Single on/off checkbox"),
*  field_types = {
*    "my_logic_field"
*  },
*  multiple_values = TRUE
* )
*/

class vk_crosspostWidget extends BooleanCheckboxWidget {

 public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'checkbox',
      '#default_value' => !empty($items[0]->value),
    ];

    // Override the title from the incoming $element.
    if ($this->getSetting('display_label')) {
      $element['value']['#title'] = $this->fieldDefinition->getLabel();
    }
    else {
      $element['value']['#title'] = $this->fieldDefinition->getSetting('on_label');
    }

    return $element;
  }


}

