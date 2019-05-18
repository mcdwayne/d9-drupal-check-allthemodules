<?php

namespace Drupal\chinese_address\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\chinese_address\chineseAddressHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 *
 * @FormElement("chinese_address")
 */
class ChineseAddress extends FormElement
{

    /**
   * {@inheritdoc}
   */
    public function getInfo() 
    {
        $class = get_class($this);
        return [
        '#input' => true,
        '#tree' => true,
        '#process' => [
        [$class, 'processChineseAddress'],
        ],
        '#element_validate' => [
        [$class, 'validateChineseAddress'],
        ],
        '#theme_wrappers' => ['form_element', 'chinese_address_element'],
        '#has_detail' => true,
        '#has_street' => true,
        '#province_limit' => array(),
        '#attached' => [
        'library' => ['chinese_address/drupal.chineseAddress'],
        ],
        ];
    }

    /**
   * {@inheritdoc}
   */
    public static function valueCallback(&$element, $input, FormStateInterface $form_state) 
    {

        $value= array();
        // Find the current value of this field.
        if ($input !== false && $input !== null) {
            return $input;
        }
        else {
            $default_value = [
            'province' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
            'city' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
            'county'=>chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
            'street'=>chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
            'detail' => '',
            ];

            if (isset($element['#default_value'])) {
                $value = $element['#default_value'];
            }
         
            $value += $default_value;
       
        
            return $value;
        }
    }

    /**
   * #ajax callback for managed_file upload forms.
   *
   * This ajax callback takes care of the following things:
   *   - Ensures that broken requests due to too big files are caught.
   *   - Adds a class to the response to be able to highlight in the UI, that a
   *     new file got uploaded.
   *
   * @param array                                     $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface      $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
    public static function _chinese_address_change_callback(&$form, FormStateInterface &$form_state, Request $request) 
    {
        $triggering_element = $form_state->getTriggeringElement();
        $parents = $triggering_element['#array_parents'];
        array_pop($parents);
        $element = NestedArray::getValue($form, $parents);
        if (isset($element['_weight'])) {
            hide($element['_weight']);
        }

        $response = new AjaxResponse();
        return $response->addCommand(new ReplaceCommand(null, $element));
    }

    /**
   * Render API callback: Expands the managed_file element type.
   *
   * Expands the file type to include Upload and Remove buttons, as well as
   * support for a default value.
   */
    public static function processChineseAddress(&$element, FormStateInterface $form_state, &$complete_form) 
    {

        $class = ChineseAddress::class;
        $address_value = $element['#value'];
        $province_limit = $element['#province_limit'];

       
        //province
        $province =chineseAddressHelper:: chinese_address_get_location(chineseAddressHelper::CHINESE_ADDRESS_ROOT_INDEX, false, $province_limit);
        $provinceAccess = count($province) >  2 ;
        if(!$provinceAccess) {
            end($province);
            $address_value['province'] =  key($province);
        }
        
        
        //city
        $city = chineseAddressHelper::chinese_address_get_location($address_value['province'], $provinceAccess);
        $filterCity=chineseAddressHelper::chinese_address_filter_none_option($city);
        if(count($filterCity) > 1) {
            $cityAccess  = true;
        } else {
            $cityAccess = false;
        }
        
     
        if(($provinceAccess  || count($filterCity)== 1)  ) {
            if(!isset($address_value['city'])||$address_value['city']==chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX || !array_key_exists($address_value['city'], $filterCity)) {
                $address_value['city'] = key($filterCity);
            }
        }
      
        //county
        if(!$provinceAccess && !$cityAccess) {
             $excludeNoneCounty= false ;
             $countyCompare =1 ;      
        }
        else {
            $excludeNoneCounty= true ;
            $countyCompare =0 ;      
        }
          
         $county = chineseAddressHelper::chinese_address_get_location($address_value['city'], $excludeNoneCounty);
         $filterCounty=chineseAddressHelper::chinese_address_filter_none_option($county);
    
         if (!isset($address_value['county']) ||($provinceAccess ||$cityAccess) &&  ($address_value['county']==chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX) ||!array_key_exists($address_value['county'], $filterCounty)) {
            $address_value['county'] = key($filterCounty);
        }
        if(count($county) > $countyCompare) {
            $countyAccess = true;
        } else { 
            $countyAccess = false;
        }
        
 
        
        
        //street
        $street = chineseAddressHelper:: chinese_address_get_location($address_value['county'], true);
       
       
        if ($element['#has_street']&&(($address_value['street']==chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX) ||!array_key_exists($address_value['street'], chineseAddressHelper::chinese_address_filter_none_option($street)))) {
            $address_value['street'] = key($street);
        }
        if(!$element['#has_street']) {
            $streetAccess= false;
        } else {
            $streetAccess = !empty($street);
        }
         
        if(!$element['#has_detail']) {
            $detailAccess = false;
        } else {
            if($element['#has_street']) {
                $detailAccess= !empty($street);
            } else {
                $detailAccess= !empty($county);
            }
        }

        $element['province'] = [
        '#type' => 'select',
        '#access' => $provinceAccess,
        '#theme_wrappers' => [],
        '#attributes' => [
        'class' => [
          'chinese-address-province',
        ], 
        ],
        '#default_value' => $address_value['province'],
        '#options' => $province,
        '#ajax' => [
        'callback' => [$class, '_chinese_address_change_callback'],
        'wrapper' => $element['#id'],
        'progress' => [
          'type' => 'none',
        ],
        ],
        ];

        $element['city'] = [
        '#type' => 'select',
        "#access" =>$cityAccess,
        '#validated' => true,
        '#theme_wrappers' => [],
        '#options' => $city,
        '#value' => $address_value['city'],
        '#attributes' => [
        'class' => [
          'chinese-address-city',
        ],

        ],
        '#ajax' => [
        'callback' => [$class, '_chinese_address_change_callback'],
        'wrapper' => $element['#id'],
        'progress' => [
          'type' => 'none',
        ],
        ],
        ];

        $element['county'] = [
        '#type' => 'select',
        '#theme_wrappers' => [],
        "#access" => $countyAccess,
        '#options' => $county,
        '#validated' => true,
        '#default_value' => $address_value['county'],
        '#ajax' => [
        'callback' => [$class, '_chinese_address_change_callback'],
        'wrapper' => $element['#id'],
        'progress' => [
          'type' => 'none',
        ],
        ],
        '#attributes' => [
        'class' => [
          'chinese-address-county',
        ],
        ],
        ];

        if($element['#has_street']) {
        $element['street'] = [
        '#type' => 'select',
        '#theme_wrappers' => [],
        "#access" => $streetAccess,
        '#options' => $street,
        '#validated' => true,
        '#default_value' => $address_value['street'],
        '#attributes' => [
        'class' => [
          'chinese-address-street',
        ],
        ],
        ];
        }

        if ($element['#has_detail']) {
            $element['detail'] = [
            '#type' => 'textfield',
            '#theme_wrappers' => [],
            '#access' => $detailAccess,
            '#size' => 20,
            '#default_value' => $address_value['detail'],
            '#maxlength' => 60,
            '#attributes' => [
              'class' => [
            'chinese-address-detail',
              ],
            ],
            ];
        }
      
        return $element;
    }

    /**
   *
   */
    public static function validateChineseAddress(&$element, FormStateInterface $form_state, &$complete_form) 
    {
        $values = $element['#value'];
        
        
        if(isset($values['street'])) {
            $depth = 4 ;
            $lastAddress= $values['street'] ;
        }
        elseif(!$element['#has_street'] && isset($values['county'])) {
            $depth =3 ;
            $lastAddress= $values['county'] ;
        }
        else {
            return;
        }
          
        $result =chineseAddressHelper:: _chinese_address_get_parents($lastAddress, $depth);
        if($depth == 4) {
            $keys = array('street','county','city','province');
        } else {
            $keys = array('county','city','province');
        }
        
        $result = array_combine($keys, array_pop($result));
        if(isset($values['detail'])) {
            $result['detail'] = $values['detail'];
        }
          
          $form_state->setValueForElement($element, $result);
        
        /*
        $values += [
        'province' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        'city' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        'county' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        'street' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        'detail' => '',
        ];*/
        //     $form_state->setValueForElement($element, $values);
    }

}
