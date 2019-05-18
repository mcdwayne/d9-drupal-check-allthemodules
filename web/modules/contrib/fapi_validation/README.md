# Form API Validation

This module extends the form API to include convenient access to common for submission filters and validation checks. The core form API has no built in validators available to you, nor filters, and all have to be manually placed in a validation function. With this module you simply add the basic filers and/or validators you want to have in your form render area.

You can use the existent filters and rules or create your own. This module is fully extensible using hooks, allowing you to easily add your own and reuse them throughout the site. Abstraction at it's finest!


## Available Validators

|Rule|Usage|Description|
|----|-----|-----------|
|numeric|`numeric`|Must contains only numbers.|
|alpha|`alpha`|Must contains only alpha characters.|
|length|`length[<total>]`, `length[<min>, <max>]`, `length[<min>, *]`|
|chars|`chars[<char 1>, <char 2>, ..., <char N>]`|Accept only specified characters.|
|email|`email`|Valid email|
|url|`url`, `url[absolute]`|Valid URL. If absolute parameter is specified, the field value must have to be a full URL.|
|ipv4|`ipv4`|Valid IPv4|
|alpha_numeric|`alpha_numeric`|Accept only Alpha Numeric characters|
|alpha_dash|`alpha_dash`|Accept only Alpha characters and Dash ( - )|
|digit|`digit`|Checks wheter a string consists of digits only (no dots or dashes).|
|decimal|`decimal`, `decimal[<digits>,<decimals>]`| |
|regexp|`regexp[/^regular expression$/]`|PCRE Regular Expression|
|match_field|`match_field[otherfield]`|Check if the field has same value of otherfield.|
|range|`range[<min>, <max>]`|Check if the field value is in defined range.|

## Available Filters

|Filter|Description|
|------|-----------|
|`numeric`|Remove all non numeric characters.|
|`trim`|Remove all spaces before and after value.|
|`uppercase`|Transform all characters to upper case.|
|`lowercase`|Transform all characters to lower case.|
|`strip_tags`|Strips out ALL html tags.|
|`html_entities`|Decodes all previously encoded entities, and then encodes all entities.|

## Usage

Example:

```php
<?php
//...

$form['myfield'] = array(
  '#type' => 'textfield',
  '#title' => 'My Field',
  '#required' => TRUE,
  '#validators' => array(
    'email', 
    'length[10, 50]', 
    array('rule' => 'alpha_numeric', 'error' => 'Please, use only alpha numeric characters at %field.'),
    array('rule' => 'match_field[otherfield]', 'error callback' => 'mymodule_validation_error_msg'),
  ),
  '#filters' => array('trim', 'uppercase')
);

//...

function mymodule_validation_error_msg($rule, $params, $element, $form_state) {
  return t("My custom error message for %field", array("%field" => $element['#title']));
}

```

## Custom Validator / Filter

The Validator or Filter was built using the Drupal Plugin API, so to create your own
custom validator or filter you should use the `FapiValidationValidator` or 
`FapiValidationFilter` Annontation and it respective implementation class.

### Validators

First you have to create a file at src/**Plugin/FapiValidationValidator**/MyCustomValidator.php
path of your module with the class implementation.

```php
<?php

namespace Drupal\my_module\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Provides a custom validation.
 *
 * Field must have JonhDoe as value.
 *
 * @FapiValidationValidator(
 *   id = "custom_validator",
 *   error_message = "Type the text 'custom value' at field %field."
 * )
 */
class MyCustomValidator implements FapiValidationValidatorsInterface {
  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    return $validator->getValue() == 'custom value';
  }
}

```

Now you are able to use at your form definiton.

```php

//...

    $form['custom_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Field'),
      '#description' => $this->t('The Value should be "custom value".'),
      '#validators' => [
        'custom_validator',
      ],
      '#required' => TRUE,
    ];

//...
```

#### Processed Error Messages

If you need create a error message programaticaly, change the `error_message` key at 
annotation to `error_callback` with the **public static** method name.

```php
//...

/**
 * @FapiValidationValidator(
 *   id = "custom_validator",
 *   error_callback = "processError"
 * )
 */
class MyCustomValidator implements FapiValidationValidatorsInterface {
  //...
  
  /**
   * Process custom error.
   *
   * @param Drupal\fapi_validation\Validator $validator
   *   Validator.
   * @param array $element
   *   Form element.
   *
   * @return string
   *   Error message.
   */
  public static function processError(Validator $validator, array $element) {
    $params = [
      '%value' => $validator->getValue(),
      '%field' => $element['#title'],
    ];
    return \t("You must enter 'custom value' as value and not '%value' at field %field", $params);
  }
}
```

### Filters

First you have to create a file at src/**Plugin/FapiValidationFilter**/MyCustomFilter.php
path of your module with the class implementation.

```php
<?php

namespace Drupal\my_module\Plugin\FapiValidationFilter;

use Drupal\fapi_validation\FapiValidationFiltersInterface;

/**
 * Fapi Validation Plugin for remove Numeric filter.
 *
 * @FapiValidationFilter(
 *   id = "custom_filter"
 * )
 */
class MyCustomFilter implements FapiValidationFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function filter($value) {
    return preg_replace('/[^0-5]+/', '', $value);
  }
}

```

Now you are able to use at your form definiton.

```php

//...

    $form['custom_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Field'),
      '#filters' => [
        'custom_filter',
      ],
      '#required' => TRUE,
    ];

//...
```
