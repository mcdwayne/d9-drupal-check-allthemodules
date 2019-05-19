<?php

/**
     * @file
     * Contains \Drupal\vk_crosspost\Plugin\Field\FieldFormatter\vk_crosspostFormatter.
     */
     
    namespace Drupal\vk_crosspost\Plugin\Field\FieldFormatter;
     
    use Drupal\Core\Field\FieldItemListInterface;
    use Drupal\Core\Field\FormatterBase;
    use Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter;   
   

/** 
* @FieldFormatter(
*    id = "vk_crosspostFormatter",
*   label = @Translation("Boolean"),
*   field_types = {
*     "my_logic_field",
*   }
* )
*/

//class MmFormatter extends FormatterBase {
  
  class vk_crosspostFormatter extends BooleanFormatter {
public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $formats = $this->getOutputFormats();

    foreach ($items as $delta => $item) {
      $format = $this->getSetting('format');

      if ($format == 'custom') {
        $elements[$delta] = ['#markup' => $item->value ? $this->getSetting('format_custom_true') : $this->getSetting('format_custom_false')];
      }
      else {
        $elements[$delta] = ['#markup' => $item->value ? $formats[$format][0] : $formats[$format][1]];
      }
  
    /*  $elements[$delta] =  [
      '#type' => 'html_tag',
      '#tag'  => 'p',
      '#value' => $this->t('my custom field VK'),
      ] + $elements[$delta]; */
     
    }
    
    

    return $elements;
  }

/*
public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        // We create a render array to produce the desired markup,
        // "<p style="color: #hexcolor">The color code ... #hexcolor</p>".
        // See theme_html_tag().
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => array(
          'style' => 'color: ' . $item->value,
        ),
        '#value' => $this->t('The color code in this field is @code', array('@code' => $item->value)),
      );
    }

    return $elements;
  }
*/
    }
