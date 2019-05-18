# Contents of this file

 * Introduction
 * Requirements
 * How field union works
 * What needs work

## Introduction

This module allows you to create new field types in the UI by joining together existing field types.

e.g. Let's say you're creating a recipe content-type and you want an ingredients field.

Logically that field might consist of values like so:

* A number field for Quantity - e.g. 3)
* A text list item for Unit - e.g. grams, cups if you use the metric, or lbs, [hogsheads](https://en.wikipedia.org/wiki/Hogshead) or some-such if you-don't
* A taxonomy term reference for Ingredient - e.g Flour

In the past, you'd have to either write your own field-type plugin, or use another entity type such as a [paragraph](https://drupal.org/project/paragraphs) or [field collection](https://drupal.org/project/field_collection) and [inline entity form](http://drupal.org/project/inline_entity_form) to achieve this.
Writing your own field-type requires custom coding and using another entity-type can lead to performance issues if you're saving a lot of entities on form-submission, particularly if you nest (e.g. paragraphs on paragraphs). In addition using another entity-type makes POST/PATCH via an API more involved, as you have to create those entities first.

## Requirements

Requires the field module from core, which is normally already installed.

## How field union works

Field-types in Drupal are declared via plugins.

Drupal 8 allows for plugins to be declared using derivatives, e.g. for every view that exposes a block display, there is a Block plugin exposed by Views module.

This is done using derivative discovery.

Field union works in the same way.

When you create a new Field Union configuration entity, this exposes a new field type plugin to core.

A field union configuration entity is basically a definition that comprises several fields.

At present [there is no UI](https://www.drupal.org/project/field_union/issues/3011353) so these can only be create in code.

Here's a sample of creating a new union:

```php
use Drupal\field_union\Entity\FieldUnion;
$union = FieldUnion::create([
  'id' => 'applicant',
  // The field type label.
  'label' => 'Applicant details',
  'description' => 'Applicant details',
  // The fields that make up the union.
  'fields' => [
    'first_name' => [
      'field_type' => 'string',
      'label' => 'First name',
      'name' => 'first_name',
      'description' => 'Enter first name',
      'required' => TRUE,
      'translatable' => FALSE,
      'default_value' => [],
      'settings' => [
        'max_length' => 255,
      ],
    ],
    'surname' => [
      'field_type' => 'string',
      'label' => 'Surname',
      'name' => 'surname',
      'description' => 'Enter surname',
      'required' => TRUE,
      'translatable' => FALSE,
      'default_value' => [],
      'settings' => [
        'max_length' => 255,
      ],
    ],
    'resume' => [
      'field_type' => 'link',
      'label' => 'Resume',
      'name' => 'resume',
      'description' => 'Enter resume',
      'required' => TRUE,
      'translatable' => FALSE,
      'default_value' => [],
      'settings' => [],
      'instance_settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
      ],
    ],
    'bio' => [
      'field_type' => 'text',
      'label' => 'Bio',
      'name' => 'bio',
      'description' => 'Enter your bio',
      'required' => TRUE,
      'translatable' => FALSE,
      'default_value' => [],
      'settings' => [
        'max_length' => 255,
      ],
    ],
    'category' => [
      'field_type' => 'entity_reference',
      'label' => 'Category',
      'name' => 'category',
      'description' => 'Select category',
      'required' => TRUE,
      'translatable' => FALSE,
      'default_value' => [],
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ],
  ],
]);
$union->save();
```

As you can see, this is just a normal config-entity. The ID, label and description are used to identify the new field.
The fields key is just an array of sub-field definitions. So you can use any field-type. Once a UI is added, this will just be like adding new fields to an entity-bundle.

Once you've saved that config-entity, you should be able to add the new field 'Applicant details' using the Field UI.

Using the field. There is no [widget](https://www.drupal.org/project/field_union/issues/3011348) or [formatter](https://www.drupal.org/project/field_union/issues/3011347) support yet, however the type-data model is solid.

Working with the field values is nice! This example assumes you created a new instance of the field and named it 'applicant'.

```php
$accepted = Term::create([
  'vid' => $this->vocabulary->id(),
  'name' => 'Accepted',
]);
/** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
$entity = EntityTest::create([
  'name' => 'entity',
  'applicant' => [
    'first_name' => 'Jerry',
    'surname' => 'Johnson',
    'resume' => [
      'uri' => 'http://example.com/jerry.johnson',
      'title' => 'My resume',
    ],
    'bio' => [
      'value' => '<p>My bio<strong>is amazing!</strong></p><div>but divs are not allowed</div>',
      'format' => 'filtered_html',
    ],
    'category' => $accepted,
  ],
]);
$entity->save();
```

As you can see, you just specify the applicant data values using a nested-array.

Accessing the data is just as nice!

```php
$entity->applicant->first_name->value; // Value would be 'Jerry'
$entity->applicant->surname->value; // Value would be 'Johnson'
$entity->applicant->surname = 'Smith'; // Update a value.
$entity->applicant->category->entity->id(); // Chain through entity-reference fields - value would be the term ID.
$entity->applicant->category->entity->label(); // Value would be 'Accepted'
$entity->applicant->resume->uri; // Works with multi-column fields - value would be 'http://example.com/jerry.johnson'
$entity->applicant->bio->processed; // And computed fields work too! - value would be '<p>My bio<strong>is amazing!</strong></p>but divs are not allowed'
```


## What needs work

- [A UI for creating the field union configuration entities](https://www.drupal.org/project/field_union/issues/3011353)
- [Support for formatters](https://www.drupal.org/project/field_union/issues/3011347)
- [Support for widgets](https://www.drupal.org/project/field_union/issues/3011348)
- [Serializer support](https://www.drupal.org/project/field_union/issues/3011921)
- [Views support](https://www.drupal.org/project/field_union/issues/3011925)
- [Tests for translation support](https://www.drupal.org/project/field_union/issues/3032001)
- [Tests for revision support](https://www.drupal.org/project/field_union/issues/3032002)
- [Test for constraint validation support](https://www.drupal.org/project/field_union/issues/3011350)
