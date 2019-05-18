<?php

namespace Drupal\cdek_api\Element;

use Drupal\Core\Render\Element\CompositeFormElementTrait;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use CdekSDK\Requests\PvzListRequest;
use CdekSDK\Common\Pvz;

/**
 * Provides a form element for selecting the pickup point.
 *
 * @FormElement("cdek_select")
 */
class CdekSelect extends FormElement {

  use CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [[$class, 'processCdekSelect']],
      '#after_build' => [[$class, 'afterBuildCdekSelect']],
      '#pre_render' => [[$class, 'preRenderCompositeFormElement']],
      '#theme_wrappers' => ['container__cdek_select'],
      '#cdek_request' => NULL,
      '#cdek_hide_country' => FALSE,
      '#cdek_hide_region' => FALSE,
      '#cdek_hide_city' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $value = [];

    if ($input === FALSE) {
      if (isset($element['#default_value'])) {
        // Trying to find the pickup point.
        $point = static::getCdek()->getPickupPoint($element['#default_value']);

        if ($point !== NULL) {
          $value = [
            'country' => $point->CountryCode,
            'region' => $point->RegionCode,
            'city' => $point->CityCode,
            'point' => $point->Code,
          ];
        }
      }
    }
    elseif (is_array($input)) {
      $value = $input;
    }
    return $value;
  }

  /**
   * Processes a cdek_select form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processCdekSelect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = is_array($element['#value']) ? $element['#value'] : [];
    $is_value_locked = FALSE;

    // Get the request object.
    $request = $element['#cdek_request'];
    if ($request instanceof PvzListRequest) {
      $request = clone $request;
    }
    else {
      $request = new PvzListRequest();
    }

    $params = $request->getParams();
    $element['#cdek_hide_country'] = $element['#cdek_hide_country'] || isset($params['cityid']) || isset($params['regionid']) || isset($params['countryid']);
    $element['#cdek_hide_region'] = $element['#cdek_hide_region'] || isset($params['cityid']) || isset($params['regionid']);
    $element['#cdek_hide_city'] = $element['#cdek_hide_city'] || isset($params['cityid']);

    // Generate a unique wrapper HTML ID.
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');
    $ajax_settings = [
      'callback' => [get_called_class(), 'ajaxUpdateCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
    ];

    if (!$element['#cdek_hide_country']) {
      // The country selection does not depend on other elements.
      $countries = static::getCdek()->getCountries($request) ?: [];
      $country = isset($value['country']) && isset($countries[$value['country']]) ? $value['country'] : '';

      // Element to select the country.
      $element['country'] = [
        '#type' => 'select',
        '#title' => t('Country'),
        '#options' => $countries,
        '#empty_value' => '',
        '#value' => $country,
        '#ajax' => $ajax_settings,
      ];

      // If the country is not yet selected, block further selection.
      // Otherwise, add the country to the parameters.
      if ($country === '') {
        $is_value_locked = TRUE;
      }
      else {
        $request->setCountryId($value['country']);
      }
    }

    if (!$element['#cdek_hide_region']) {
      // The region selection may depend on the selected country.
      $regions = !$is_value_locked ? (static::getCdek()->getRegions($request) ?: []) : [];
      $region = isset($value['region']) && isset($regions[$value['region']]) ? $value['region'] : '';

      // Element to select the region.
      $element['region'] = [
        '#type' => 'select',
        '#title' => t('Region'),
        '#options' => $regions,
        '#empty_value' => '',
        '#value' => $region,
        '#ajax' => $ajax_settings,
      ];

      // If the region is not yet selected, block further selection.
      // Otherwise, add the region to the parameters.
      if ($region === '') {
        $is_value_locked = TRUE;
      }
      else {
        $request->setRegionId($value['region']);
      }
    }

    if (!$element['#cdek_hide_city']) {
      // The city selection may depend on the selected country and region.
      $cities = !$is_value_locked ? (static::getCdek()->getCities($request) ?: []) : [];
      $city = isset($value['city']) && isset($cities[$value['city']]) ? $value['city'] : '';

      // Element to select the city.
      $element['city'] = [
        '#type' => 'select',
        '#title' => t('City'),
        '#options' => $cities,
        '#empty_value' => '',
        '#value' => $city,
        '#ajax' => $ajax_settings,
      ];

      // If the city is not yet selected, block further selection.
      // Otherwise, add the city to the parameters.
      if ($city === '') {
        $is_value_locked = TRUE;
      }
      else {
        $request->setCityId($value['city']);
      }
    }

    // The point selection may depend on the selected country, region and city.
    $points = !$is_value_locked ? (static::getCdek()->getPickupPoints($request) ?: []) : [];
    $points = array_map([get_called_class(), 'getPointLabel'], $points);
    $point = isset($value['point']) && isset($points[$value['point']]) ? $value['point'] : '';

    // Element to select the point.
    $element['point'] = [
      '#type' => 'select',
      '#title' => t('Point'),
      '#options' => $points,
      '#empty_value' => '',
      '#value' => $point,
    ];

    // Prefix and suffix used for Ajax replacement.
    if (isset($element['country']) || isset($element['region']) || isset($element['city'])) {
      $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
      $element['#suffix'] = '</div>';
    }
    return $element;
  }

  /**
   * After-build callback.
   *
   * @param array $element
   *   The element structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element structure.
   */
  public static function afterBuildCdekSelect(array $element, FormStateInterface $form_state) {
    $element['#value'] = $element['point']['#value'];
    $form_state->setValueForElement($element, $element['#value']);
    return $element;
  }

  /**
   * Ajax callback to update element.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The element to be rendered.
   */
  public static function ajaxUpdateCallback(array $form, FormStateInterface $form_state, Request $request) {
    // Get element parents from the request.
    $element_parents = explode('/', $request->query->get('element_parents'));
    // Sanitize element parents before using them.
    $element_parents = array_filter($element_parents, [Element::class, 'child']);
    // Retrieve the element to be rendered.
    return NestedArray::getValue($form, $element_parents);
  }

  /**
   * Gets the cdek_api service.
   *
   * @return \Drupal\cdek_api\Cdek
   *   The cdek_api service.
   */
  protected static function getCdek() {
    return \Drupal::service('cdek_api');
  }

  /**
   * Gets the pickup point label.
   *
   * @param \CdekSDK\Common\Pvz $point
   *   The pickup point object.
   *
   * @return string
   *   The pickup point label.
   */
  protected static function getPointLabel(Pvz $point) {
    return $point->Name;
  }

}
