<?php

namespace Drupal\nutrition_label_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
// @todo: remove after refactoring out anon class
use Drupal\Core\Url;
use Drupal\Core\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin implementation of the 'nutrition_label' widget.
 *
 * @FieldWidget(
 *   id = "nutrition_label",
 *   label = @Translation("Nutrition Label"),
 *   field_types = {
 *     "nutrition_label"
 *   }
 * )
 */
class NutritionLabelWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // @todo: get default settings from site-wide config page.
    return [
      'showBrandName' => false,
      'showItemName' => false,
      'showServingUnitQuantity' => false,
      'showServingPerContainer' => false,
      'showNutritionValues' => [
        'Calories',
        'FatCalories',
        'TotalFat',
        'SatFat',
        'TransFat',
        'Cholesterol',
        'Sodium',
        'TotalCarb',
        'Fibers',
        'Sugars',
        'AddedSugars',
        'Proteins',
        'Potassium',
        'VitaminA',
        'VitaminC',
        'VitaminD',
        'Calcium',
        'Iron',
      ],
      'showUnits' => false,
      'showDecimalPlaces' => false,
      'showIngredients' => false,
      'showBottomLink' => false,
      'showCustomWidth' => false,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['showBrandName'] = [
      '#type' => 'checkbox',
      '#title' => t('Show brand name'),
      '#default_value' => $this->getSetting('showBrandName'),
      '#description' => t('Allow the user to specify a brand name.'),
    ];
    $element['showItemName'] = [
      '#type' => 'checkbox',
      '#title' => t('Show item name'),
      '#default_value' => $this->getSetting('showItemName'),
      '#description' => t('Allow the user to specify a item name.'),
    ];
    $element['showServingUnitQuantity'] = [
      '#type' => 'checkbox',
      '#title' => t('Show serving unit quantity'),
      '#default_value' => $this->getSetting('showServingUnitQuantity'),
      '#description' => t('Allow the user to specify the quantity of servings shown on the label.'),
    ];
    $element['showServingPerContainer'] = [
      '#type' => 'checkbox',
      '#title' => t('Show servings per container'),
      '#default_value' => $this->getSetting('showServingPerContainer'),
      '#description' => t('Allow the user to specify the quantity of servings per container.'),
    ];
    $element['showUnits'] = [
      '#type' => 'checkbox',
      '#title' => t('Show units'),
      '#default_value' => $this->getSetting('showUnits'),
      '#description' => t('Allow the user to specify units for nutrition values.'),
    ];
    $element['showDecimalPlaces'] = [
      '#type' => 'checkbox',
      '#title' => t('Show decimal places'),
      '#default_value' => $this->getSetting('showDecimalPlaces'),
      '#description' => t('Allow the user to specify decimal place settings for various values.'),
    ];
    $element['showIngredients'] = [
      '#type' => 'checkbox',
      '#title' => t('Show ingredients'),
      '#default_value' => $this->getSetting('showIngredients'),
      '#description' => t('Allow the user to specify ingredients.'),
    ];
    $element['showBottomLink'] = [
      '#type' => 'checkbox',
      '#title' => t('Show bottom link'),
      '#default_value' => $this->getSetting('showBottomLink'),
      '#description' => t('Allow the user to specify a link for the bottom of the label.'),
    ];
    $element['showCustomWidth'] = [
      '#type' => 'checkbox',
      '#title' => t('Show custom width'),
      '#default_value' => $this->getSetting('showCustomWidth'),
      '#description' => t('Allow the user to specify a custom width.'),
    ];
    $element['showNutritionValues'] = [
      '#type' => 'checkboxes',
      '#title' => t('Show nutrition values'),
      '#options' => [
        'Calories' => 'Calories',
        'FatCalories' => 'Fat Calories',
        'TotalFat' => 'Total Fat',
        'SatFat' => 'Sat Fat',
        'TransFat' => 'Trans Fat',
        'PolyFat' => 'Poly Fat',
        'MonoFat' => 'Mono Fat',
        'Cholesterol' => 'Cholesterol',
        'Sodium' => 'Sodium',
        'TotalCarb' => 'Total Carbohydrates',
        'Fibers' => 'Fiber',
        'Sugars' => 'Sugar',
        'AddedSugars' => 'Added Sugars',
        'SugarAlcohol' => 'Sugar Alcohol',
        'Proteins' => 'Protein',
        'Potassium' => 'Potassium',
        'VitaminA' => 'Vitamin A',
        'VitaminC' => 'Vitamin C',
        'VitaminD' => 'Vitamin D',
        'Calcium' => 'Calcium',
        'Iron' => 'Iron',
      ],
      '#default_value' => $this->getSetting('showNutritionValues'),
      '#required' => true,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    // @todo: refactor to avoid anonymous class
    $form_state = new class() implements FormStateInterface {
      public function &getCompleteForm(){} public function setCompleteForm(array &$complete_form){} public function loadInclude($module, $type, $name = NULL){} public function getCacheableArray(){} public function setFormState(array $form_state_additions){} public function setResponse(Response $response){} public function getResponse(){} public function setRedirect($route_name, array $route_parameters = [], array $options = []){} public function setRedirectUrl(Url $url){} public function getRedirect(){} public function setStorage(array $storage){} public function &getStorage(){} public function &get($property){} public function set($property, $value){} public function has($property){} public function setBuildInfo(array $build_info){} public function getBuildInfo(){} public function addBuildInfo($property, $value){} public function &getUserInput(){} public function setUserInput(array $user_input){} public function &getValues(){} public function &getValue($key, $default = NULL){} public function setValues(array $values){} public function setValue($key, $value){} public function unsetValue($key){} public function hasValue($key){} public function isValueEmpty($key){} public function setValueForElement(array $element, $value){} public static function hasAnyErrors(){} public function setErrorByName($name, $message = ''){} public function setError(array &$element, $message = ''){} public function clearErrors(){} public function getErrors(){} public function getError(array $element){} public function setRebuild($rebuild = TRUE){} public function isRebuilding(){} public function setInvalidToken($invalid_token){} public function hasInvalidToken(){} public function prepareCallback($callback){} public function getFormObject(){} public function setFormObject(FormInterface $form_object){} public function setAlwaysProcess($always_process = TRUE){} public function getAlwaysProcess(){} public function setButtons(array $buttons){} public function getButtons(){} public function setCached($cache = TRUE){} public function isCached(){} public function disableCache(){} public function setExecuted(){} public function isExecuted(){} public function setGroups(array $groups){} public function &getGroups(){} public function setHasFileElement($has_file_element = TRUE){} public function hasFileElement(){} public function setLimitValidationErrors($limit_validation_errors){} public function getLimitValidationErrors(){} public function setMethod($method){} public function setRequestMethod($method){} public function isMethodType($method_type){} public function setValidationEnforced($must_validate = TRUE){} public function isValidationEnforced(){} public function disableRedirect($no_redirect = TRUE){} public function isRedirectDisabled(){} public function setProcessInput($process_input = TRUE){} public function isProcessingInput(){} public function setProgrammed($programmed = TRUE){} public function isProgrammed(){} public function setProgrammedBypassAccessCheck($programmed_bypass_access_check = TRUE){} public function isBypassingProgrammedAccessChecks(){} public function setRebuildInfo(array $rebuild_info){} public function getRebuildInfo(){} public function addRebuildInfo($property, $value){} public function setSubmitHandlers(array $submit_handlers){} public function getSubmitHandlers(){} public function setSubmitted(){} public function isSubmitted(){} public function setTemporary(array $temporary){} public function getTemporary(){} public function &getTemporaryValue($key){} public function setTemporaryValue($key, $value){} public function hasTemporaryValue($key){} public function setTriggeringElement($triggering_element){} public function &getTriggeringElement(){} public function setValidateHandlers(array $validate_handlers){} public function getValidateHandlers(){} public function setValidationComplete($validation_complete = TRUE){} public function isValidationComplete(){} public function getCleanValueKeys(){} public function setCleanValueKeys(array $keys){} public function addCleanValueKey($key){} public function cleanValues(){}
    };

    $settings_form = $this->settingsForm([], $form_state);
    foreach (Element::children($settings_form) as $key) {
      $setting = $this->getSetting($key);
      $element = $settings_form[$key];
      if (strpos($key, 'show') === 0 && !empty($setting)) {
        if ($element['#type'] === 'checkbox') {
          $summary[] = $element['#title'];
        }
        elseif ($element['#type'] === 'checkboxes') {
          $summary[] = $element['#title'] . ': ' . implode(', ', array_intersect_key($element['#options'], array_flip($setting)));
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $settings_form = $this->settingsForm($form, $form_state);

    if ($settings['showBrandName']) {
      $element += [
        'brandName' => [
          '#type' => 'textfield',
          '#title' => t('Brand Name'),
          '#default_value' => $items[$delta]->brandName,
        ],
      ];
    }
    if ($settings['showServingUnitQuantity']) {
      $element += [
        'valueServingUnitQuantity' => [
          '#type' => 'number',
          '#title' => t('Serving Unit Quantity'),
          '#min' => 0.1,
          '#max' => 9999,
          '#step' => 0.1,
          '#default_value' => $items[$delta]->valueServingUnitQuantity,
        ],
      ];
    }
    else {
      $element += [
        'valueServingUnitQuantity' => [
          '#type' => 'value',
          '#value' => 1,
        ],
      ];
    }
    $element += [
      'valueServingSizeUnit' => [
        '#type' => 'textfield',
        '#title' => t('Serving Size Unit'),
        '#default_value' => $items[$delta]->valueServingSizeUnit,
      ],
    ];
    if ($settings['showItemName']) {
      $element += [
        'itemName' => [
          '#type' => 'textfield',
          '#title' => t('Item Name'),
          '#default_value' => $items[$delta]->itemName,
        ],
      ];
    }
    $element += [
      'valueServingWeightGrams' => [
        '#type' => 'number',
        '#title' => t('Serving Weight in Grams'),
        '#min' => 0.1,
        '#max' => 9999,
        '#step' => 0.1,
        '#default_value' => $items[$delta]->valueServingWeightGrams,
      ],
    ];
    if ($settings['showServingPerContainer']) {
      $element += [
        'valueServingPerContainer' => [
          '#type' => 'number',
          '#title' => t('Servings Per Container'),
          '#min' => 0.1,
          '#max' => 9999,
          '#step' => 0.1,
          '#default_value' => $items[$delta]->valueServingPerContainer,
        ],
      ];
    }
    foreach (array_filter($settings['showNutritionValues']) as $nutritionValue) {
      $key = 'value' . $nutritionValue;
      $element += [
        $key => [
          '#type' => 'number',
          '#title' => $settings_form['showNutritionValues']['#options'][$nutritionValue],
          '#min' => 0,
          '#max' => 9999,
          '#step' => 0.1,
          '#default_value' => $items[$delta]->$key,
        ],
      ];
      $key = 'na' . $nutritionValue;
      $element += [
        $key => [
          '#type' => 'checkbox',
          '#title' => $settings_form['showNutritionValues']['#options'][$nutritionValue] . ' ' . t('Not Applicable'),
          '#default_value' => $items[$delta]->$key,
        ],
      ];
      $key = 'unit' . $nutritionValue;
      if ($settings['showUnits']) {
        $element += [
          $key => [
            '#type' => 'textfield',
            '#title' => $settings_form['showNutritionValues']['#options'][$nutritionValue] . ' ' . t('Units'),
            '#placeholder' => '<span aria-hidden="true">g</span><span class="sr-only"> grams</span>',
            '#default_value' => $items[$delta]->$key,
          ],
        ];
      }
    }
    if ($settings['showDecimalPlaces']) {
      $element += [
        'decimalPlacesForNutrition' => [
          '#type' => 'number',
          '#title' => t('Decimal Places for Nutrition Values'),
          '#min' => 0,
          '#max' => 5,
          '#step' => 1,
          '#default_value' => $items[$delta]->decimalPlacesForNutrition,
        ],
      ];
      $element += [
        'decimalPlacesForDailyValues' => [
          '#type' => 'number',
          '#title' => t('Decimal Places for Daily Values'),
          '#min' => 0,
          '#max' => 5,
          '#step' => 1,
          '#default_value' => $items[$delta]->decimalPlacesForDailyValues,
        ],
      ];
      $element += [
        'decimalPlacesForQuantityTextbox' => [
          '#type' => 'number',
          '#title' => t('Decimal Places for Quantity Textbox'),
          '#min' => 0,
          '#max' => 5,
          '#step' => 1,
          '#default_value' => $items[$delta]->decimalPlacesForQuantityTextbox,
        ],
      ];
    }
    if ($settings['showIngredients']) {
      $element += [
        'ingredientList' => [
          '#type' => 'textarea',
          '#title' => t('Ingredient List'),
          '#default_value' => $items[$delta]->ingredientList,
        ],
      ];
    }
    if ($settings['showBottomLink']) {
      $element += [
        'nameBottomLink' => [
          '#type' => 'textfield',
          '#title' => t('Bottom Link Text'),
          '#default_value' => $items[$delta]->nameBottomLink,
        ],
      ];
      $element += [
        'urlBottomLink' => [
          '#type' => 'url',
          '#title' => t('Bottom Link URL'),
          '#default_value' => $items[$delta]->urlBottomLink,
        ],
      ];
    }
    if ($settings['showCustomWidth']) {
      $element += [
        'widthCustom' => [
          '#type' => 'textfield',
          '#title' => t('Custom Width'),
          '#default_value' => $items[$delta]->widthCustom,
        ],
      ];
    }

    return $element;
  }

}
  // @todo: other available settings--should these be surfaced, or code-only (via Drupal.settings.nutrition_label).
/*
    //to enabled the google analytics event logging
    allowGoogleAnalyticsEventLog : false,
    gooleAnalyticsFunctionName : 'ga',
    textGoogleAnalyticsEventCategory : 'Nutrition Label',
    textGoogleAnalyticsEventActionUpArrow : 'Quantity Up Arrow Clicked',
    textGoogleAnalyticsEventActionDownArrow : 'Quantity Down Arrow Clicked',
    textGoogleAnalyticsEventActionTextbox : 'Quantity Textbox Changed',
    //enable triggering of user function on quantity change: global function name
    userFunctionNameOnQuantityChange: null,
    //enable triggering of user function on quantity change: handler instance
    userFunctionOnQuantityChange:     null,

    //when set to true, this will hide the values if they are not applicable
    hideNotApplicableValues : false,
    //default calorie intake
    calorieIntake : 2000,

    //these are the recommended daily intake values
    dailyValueTotalFat : 65,
    dailyValueSatFat : 20,
    dailyValueCholesterol : 300,
    dailyValueSodium : 2400,
    dailyValuePotassium : 3500,
    dailyValuePotassium_2018 : 4700,
    dailyValueCarb : 300,
    dailyValueFiber : 25,
    dailyValueCalcium : 1300,
    dailyValueIron : 18,
    dailyValueVitaminD : 20,
    dailyValueAddedSugar : 50,

    //to show the 'amount per serving' text
    showAmountPerServing : true,
    //to show the 'servings per container' data and replace the default 'Serving Size' value (without unit and servings per container text and value)
    showServingsPerContainer : false,
    //to show the calorie diet info at the bottom of the label
    showCalorieDiet : false,
    //to show the customizable footer which can contain html and js codes
    showCustomFooter : false,
    //to show the disclaimer text or not
    showDisclaimer : false,

    //these text settings is so you can create nutrition labels in different languages or to simply change them to your need
    textNutritionFacts : 'Nutrition Facts',
    textDailyValues : 'Daily Value',
    textServingSize : 'Serving Size:',
    textServingsPerContainer : 'Servings Per Container',
    textAmountPerServing : 'Amount Per Serving',
    textCalories : 'Calories',
    textFatCalories : 'Calories from Fat',
    textTotalFat : 'Total Fat',
    textSatFat : 'Saturated Fat',
    textTransFat : '<em>Trans</em> Fat',
    textPolyFat : 'Polyunsaturated Fat',
    textMonoFat : 'Monounsaturated Fat',
    textCholesterol : 'Cholesterol',
    textSodium : 'Sodium',
    textPotassium : 'Potassium',
    textTotalCarb : 'Total Carbohydrates',
    textFibers : 'Dietary Fiber',
    textSugars : 'Sugars',
    textAddedSugars1 : 'Includes ',
    textAddedSugars2 : ' Added Sugars',
    textSugarAlcohol : 'Sugar Alcohol',
    textProteins : 'Protein',
    textVitaminA : 'Vitamin A',
    textVitaminC : 'Vitamin C',
    textVitaminD : 'Vitamin D',
    textCalcium : 'Calcium',
    textIron : 'Iron',
    textNotApplicable : '-',
    textPercentDailyPart1 : 'Percent Daily Values are based on a',
    textPercentDailyPart2 : 'calorie diet',
    textPercentDaily2018VersionPart1 : 'The % Daily Value (DV) tells you how much a nutrient in a serving of food contributes to a daily diet. ',
    textPercentDaily2018VersionPart2 : ' calories a day is used for general nutrition advice.',
		showLegacyVersion : false,
*/

