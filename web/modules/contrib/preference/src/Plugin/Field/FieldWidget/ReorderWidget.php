<?php
namespace Drupal\preference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_reorder' widget.
 *
 * @FieldWidget(
 *   id = "field_reorder",
 *   module = "preference",
 *   label = @Translation("Reorder Widget"),
 *   field_types = {
 *       "entity_reference",
 *       "list_integer",
 *       "list_float",
 *       "list_string"
 *   }
 * )
 */


class ReorderWidget extends WidgetBase {    
    
    public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state)
    {
        
        $node = $items->getEntity();
        if($node->nid->value < 1)
        {    
            $settings = $items->getDataDefinition()->getFieldStorageDefinition()->getSettings();            
            $str = '[';
            if(!empty($settings['allowed_values']))
            {
                $i = 0;
                foreach ($settings['allowed_values'] as $k => $v)
                {    
                    $str .= '["'.$k.'", "'.$v.'"],';                
                    $i++;
                }            
            }    
            $str = substr($str, 0, -1);
            $str .= ']';

            $element['textareaJson'] =  array(
                '#type' => 'textarea',
                '#title' => t('Json Text'),
                '#default_value' => $str,
                '#required' => false,
            );
            //$form_state->setValidationEnforced(FALSE);

            return array('value', $element);
        }
        else
        {
            
            $node_fields = $node->getFieldDefinitions();
            $values = $form_state->getValues();
            $storage = $form_state->getStorage();
            foreach ($node_fields as $fk => $fv)
            {
                $field_type = $storage['form_display']->getComponent($fk)['type'];                  
                if($field_type == 'field_reorder')
                {
                    $options = $node->$fk->getSetting('allowed_values');
                    $fvs = $node->get($fk)->getValue();
                    $str = '[';
                    if(!empty($fvs))
                    {
                        $i = 0;
                        foreach ($fvs as $kk)
                        {
                            if(isset($options[$kk['value']]))
                            {        
                                $str .= '["'.$kk['value'].'", "'.$options[$kk['value']].'"],';                
                            }
                            $i++;
                        }            
                    }                                                            
                }
            }
            
            $str = substr($str, 0, -1);
            $str .= ']';

            $element['textareaJson'] =  array(
                '#type' => 'textarea',
                '#title' => t('Json Text'),
                '#default_value' => $str,
                '#required' => false,
            );
            return array('value', $element);
        }
    }
    
    
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        //$ri = new \ReflectionObject($items);
//        $node = $items->getEntity();
//        if($node->nid->value < 1)
//        {    
//            $settings = $items->getDataDefinition()->getFieldStorageDefinition()->getSettings();
//            $str = '[';
//            if(!empty($settings['allowed_values']))
//            {
//                $i = 0;
//                foreach ($settings['allowed_values'] as $k => $v)
//                {    
//                    $str .= '["'.$k.'", "'.$v.'"],';                
//                    $i++;
//                }            
//            }    
//            $str = substr($str, 0, -1);
//            $str .= ']';
//
//            $element['textareaJson'] =  array(
//                '#type' => 'textarea',
//                '#title' => t('Json Text'),
//                '#default_value' => $str,
//                '#required' => false,
//            );
//            //$form_state->setValidationEnforced(FALSE);
//
//            return $element;
//        }
//        else
//        {
//            $vvv = isset($items[$delta]->value) ? $items[$delta]->value : '';
//            $element[$delta] =  array(
//                '#type' => 'textfield',
//                '#title' => t('Json Text'),
//                '#default_value' => $vvv,
//                '#required' => false,
//            );
//            return $element;
////            $element =  array(
////                '#type' => 'textarea',
////                '#title' => t('Json Text'),
////                '#default_value' => $str,
////                '#required' => false,
////            );
////            return $element;
//        }
//        
//        
    }

    /**
     * Validate the color text field.
     */
    public function validate($element, FormStateInterface $form_state) {
        $value = $element['#value'];
        if (strlen($value) == 0) {
            $form_state->setValueForElement($element, '');
            return;
        }
    }
}
    