<?php

namespace Drupal\signature_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;

/**
 * Plugin implementation of the 'field_signature_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_signature_field_widget",
 *   module = "signature_field",
 *   label = @Translation("Signature Data"),
 *   field_types = {
 *     "field_signature"
 *   }
 * )
 */
class SignatureWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    
    
    $file_upload_help = array(
        '#theme' => 'signature',
        '#sign_src' => $value,
      );
     $sign_thumb = array( '#type'   => 'html_tag',
                          '#tag' => 'img',
                          '#attributes' => array('src' => '', 'id' => 'signature_thumb', 'class'=>array('align-right'), 'width' =>'120px', 'height' => '60px' ));

      
    $element += array(
      '#type' => 'textarea',
      '#default_value' => $value,
      '#attributes' => array(
        'id' => array('signature_data'),
                        ),
      '#attached' => array(
        'library' => array('signature_field/signature_pad'),
                        ),
    );
    $element['#attached']['library'][] = 'signature_field/signature_pad';
    $element['#description'] = \Drupal::service('renderer')->renderPlain($file_upload_help);
    $element['#suffix'] = \Drupal::service('renderer')->renderPlain($sign_thumb);
       
    return array('value' => $element);
  }

}
