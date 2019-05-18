# Machine Name Widget

Widget for a Machine Name form element.

## Introduction
Machine Name Widget provides a field for the
[machine_name](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!MachineName.php/class/MachineName/8)
render element. This module does not provide a UI for configuring the widget,
but rather is intended to be used by other modules (i.e. content entities).

## Installation
Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.

## Setup
The widget accepts the same configuration as
[machine_name](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!MachineName.php/class/MachineName/8).
Simply use the type `machine_name` and pass the same config into `settings`.

## Example
Here's an example from
[Colossal Menu](https://www.drupal.org/project/colossal_menu):
```
$fields['machine_name'] = BaseFieldDefinition::create('string')
  ->setLabel(t('Machine name'))
  ->setDescription(t('Machine name of the menu link'))
  ->setRequired(TRUE)
  ->setSetting('max_length', 255)
  ->addConstraint('UniqueField', [])
  ->setDisplayOptions('form', [
    'type' => 'machine_name',
    'weight' => -4,
    'settings' => [
      'source' => [
        'title',
        'widget',
        0,
        'value',
      ],
      'exists' => '\Drupal\colossal_menu\Entity\Link::loadByMachineName',
    ],
  ]);
```

## Maintainers
Current maintainers:
* David Barratt ([davidwbarratt](https://www.drupal.org/u/davidwbarratt))

## Sponsors
Current sponsors:
* [Golf Channel](https://www.drupal.org/node/2374873)
