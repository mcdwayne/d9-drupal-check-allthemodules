# Form Options Attributes Module

## Overview

This module adds the ability to specify attributes for individual options
on Drupal Form API elements of the types select, checkboxes, and radios.

This is an API module, with no user interface. You would only need this
module if another module you are using requires it or if you are programming
a custom form that requires attributes on select <option> children, radio
buttons within a radios element, or checkbox elements within an checkboxes
element.


## Usage

To add attributes to a form element's options, add an '#options_attributes'
key to the form element definition. The #options_attributes value should be
an array with keys that match the keys in the #options value array. The values
in the #options_attributes array are formatted like the main #attributes array.

## Examples

```
$states = [
  'AL' => $this->t('Alabama'),
  'AK' => $this->t('Alaska'),
  'AZ' => $this->t('Arizona'),
  'AR' => $this->t('Arkansas'),
  // ..
  'WI' => $this->t('Wisconsin'),
  'WY' => $this->t('Wyoming'),
];
$states_attributes = [
  'AL' => ['class' => ['southeast'], 'data-bbq-meat' => 'pork'],
  'AK' => ['class' => ['non-contiguous'], 'data-bbq-meat' => 'salmon'],
  'AZ' => ['class' => ['southwest'], 'data-bbq-meat' => 'rattlesnake'],
  'AR' => ['class' => ['south'], 'data-bbq-meat' => 'beef'],
  // ...
  'WI' => ['class' => ['midwest'], 'data-bbq-meat' => 'cheese'],
  'WY' => ['class' => ['flyover'], 'data-bbq-meat' => 'bison'],
];
$form['states'] = [
  '#type' => 'select',
  '#title' => $this->t('States'),
  '#options' => $states,
  '#options_attributes' => $states_attributes,
  '#attributes' => ['class' => ['states-bbq-selector']],
];

$form['states_radio'] = [
  '#type' => 'radios',
  '#title' => $this->t('States'),
  '#options' => $states,
  '#options_attributes' => $states_attributes,
  '#attributes' => ['class' => ['states-bbq-selector-radios']],
];

$form['states_checkboxes'] = [
  '#type' => 'checkboxes',
  '#title' => $this->t('States'),
  '#options' => $states,
  '#options_attributes' => $states_attributes,
  '#attributes' => ['class' => ['states-bbq-selector-checkboxes']],
];

```

```
$form['states'] = [
  '#type' => 'select',
  '#title' => $this->t('States and Provinces'),
  '#options' => [
    $this->t('United States')->render() => [
      'AL' => $this->t('Alabama'),
      'AK' => $this->t('Alaska'),
      'AZ' => $this->t('Arizona'),
      'AR' => $this->t('Arkansas'),
      // ..
      'WI' => $this->t('Wisconsin'),
      'WY' => t('Wyoming'),
    ],
    $this->t('Canada')->render() => [
      'AB' => $this->t('Alberta'),
      'BC' => $this->t('British Columbia'),
      // ..
      'NU' => $this->t('Nunavut'),
      'YT' => $this->t('Yukon'),
    ],
  ],
  '#options_attributes' => [
    $this->t('United States')->render() => [
      'AL' => ['class' => ['southeast'], 'data-bbq-meat' => 'pork'],
      'AK' => ['class' => ['non-contiguous'], 'data-bbq-meat' => 'salmon'],
      'AZ' => ['class' => ['southwest'], 'data-bbq-meat' => 'rattlesnake'],
      'AR' => ['class' => ['south'], 'data-bbq-meat' => 'beef'],
      // ...
      'WI' => ['class' => ['midwest'], 'data-bbq-meat' => 'cheese'],
      'WY' => ['class' => ['flyover'], 'data-bbq-meat' => 'bison'],
    ],
    $this->t('Canada')->render() => [
      'AB' => ['class' => ['rocky'], 'data-bbq-meat' => 'beaver'],
      'BC' => ['class' => ['coastal'], 'data-bbq-meat' => 'otter'],
      // ..
      'NU' => ['class' => ['arctic'], 'data-bbq-meat' => 'walrus'],
      'YT' => ['class' => ['goldy'], 'data-bbq-meat' => 'moose'],
    ]
  ],
  '#attributes' => array('class' => array('states-bbq-selector')),
];
```
