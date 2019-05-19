<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\serialization\Normalizer\ConfigEntityNormalizer;
use Drupal\webform\Entity\Webform;

/**
 * Normalizes/denormalizes Drupal config entity objects into an array structure.
 */
class WebformEntityNormalizer extends ConfigEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = Webform::class;

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /* @var $object \Drupal\webform\Entity\Webform */

    $elements = $this->cleanUpElements($object->getElementsInitializedAndFlattened());
    $fields = [];
    $layout = [];
    $layoutById = [];

    foreach ($elements as $element) {
      $id = $element['#webform_key'];
      $jsonSchema = $this->elementJsonMapping($element);
      if (!empty($jsonSchema['field'])) {
        if (!empty($jsonSchema['field']['id'])) {
          $id = $jsonSchema['field']['id'];
          unset($jsonSchema['field']['id']);
        }
        $fields[$id] = $jsonSchema['field'];
      }

      if (empty($jsonSchema['layout'])) {
        $jsonSchema['layout'] = $id;
      }

      $layoutById[$id] = $jsonSchema['layout'];

      if (count($element['#webform_parents']) > 1) {
        $parent = $element['#webform_parents'][count($element['#webform_parents']) - 2];
        if (isset($layoutById[$parent])) {
          $layoutById[$parent]['fieldGroup'][] =& $layoutById[$id];
        }
        else {
          die('error handling here!');
        }
      }
      else {
        $layout[] =& $layoutById[$id];
      }

    }

    $return = [
      'uuid' => $object->uuid(),
      'langcode' => $object->getLangcode(),
      'status' => $object->status(),
      'id' => $object->id(),
      'json_schema' => ['layout' => $layout, 'fields' => $fields],
      'settings' => [
        'confirmation_type' => $object->getSetting('confirmation_type'),
        'confirmation_title' => $object->getSetting('confirmation_title'),
        'confirmation_message' => $object->getSetting('confirmation_message'),
        'confirmation_url' => $object->getSetting('confirmation_url'),
        'confirmation_back' => $object->getSetting('confirmation_back'),
        'confirmation_back_label' => $object->getSetting('confirmation_back_label'),
      ],
    ];

    if (!empty($context['cacheability'])) {
      $context['cacheability']->addCacheableDependency($object);
    }

    return $return;
  }

  /**
   * Get json schema type by element type.
   *
   * @param array $element
   *   Element type.
   *
   * @return array
   *   Json schema type.
   */
  private function elementJsonMapping(array $element) {
    $return = [];
    $return['field']['name'] = $element['#webform_key'];
    switch ($element['#type']) {
      case 'webform_flexbox':
        $return['layout'] = [
          'fieldGroupType' => 'Flexbox',
          'fieldGroup' => [],
        ];
        unset($return['field']);
        break;

      case 'webform_actions':
        $return['layout'] = [
          'fieldGroupType' => 'Container',
          'fieldGroup' => ['actions'],
          'className' => 'actions',
        ];

        $return['field']['type'] = 'submit';
        $return['field']['label'] = $element['#submit__label'];

        break;

      case 'email':
        $return['field']['type'] = 'email';
        $return['field']['rules'][] = 'email';
        $return['field']['rules'][] = 'string';
        break;

      case 'managed_file':
        $return['field']['type'] = 'file';
        break;

      case 'textfield':
        $return['field']['type'] = 'text';
        $return['field']['rules'][] = 'string';
        break;

      case 'tel':
        $return['field']['type'] = 'tel';
        break;

      case 'checkbox':
        $return['field']['type'] = $element['#type'];
        if (!empty($element['#required'])) {
          $return['field']['rules'][] = 'accepted';
        }
        break;

      case 'radios':
        $return['field']['name'] = $element['#webform_key'] . '[]';
        $return['field']['type'] = $element['#type'];
        foreach ($element['#options'] as $key => $value) {
          $return['field']['extra']['options'][] = [
            'value' => $key,
            'label' => $value,
          ];
        }
        break;

      case 'checkboxes':
        $return['field']['name'] = $element['#webform_key'];
        $return['field']['type'] = $element['#type'];
        $return['field']['fields'] = [];
        foreach ($element['#options'] as $key => $value) {
          $return['field']['fields'][$key] = [
            'type' => 'checkbox',
            'name' => $key,
            'label' => $value,
          ];
        }
        break;

      case 'select':
        $return['field']['type'] = 'select';
        $return['field']['extra']['options'] = [];
        foreach ($element['#options'] as $key => $value) {
          $return['field']['extra']['options'][] = [
            'value' => $key,
            'label' => $value,
          ];
        }
        break;

      case 'date':
        $return['field']['type'] = $element['#type'];
        $return['field']['extra']['format'] = 'DD-MM-YYYY';
        break;

      case 'textarea':
        $return['field']['type'] = $element['#type'];
        $return['field']['rules'][] = 'string';
        $return['field']['extra']['rows'] = !empty($element['#rows']) ? $element['#rows'] : 5;
        break;

      default:
        $return['field']['type'] = $element['#type'];
        $return['field']['rules'][] = 'string';
        break;
    };

    if (!empty($element['#title']) && empty($return['field']['label'])) {
      $return['field']['label'] = $element['#title'];
    }

    if (!empty($element['#title_display'])) {
      switch ($element['#title_display']) {
        case 'invisible':
          $return['field']['notitle'] = TRUE;
          break;
      }
    }
    if (!empty($element['#placeholder'])) {
      $return['field']['placeholder'] = $element['#placeholder'];
    }

    if (!empty($element['#required'])) {
      $return['field']['rules'][] = 'required';
    }

    if (!empty($return['field']['rules'])) {
      $return['field']['rules'] = implode('|', $return['field']['rules']);
    }

    return $return;
  }

  /**
   * Cleanup form elements.
   *
   * @param array $elements
   *   Elements.
   *
   * @return array
   *   Return clean elements.
   */
  private function cleanUpElements(array &$elements) {
    foreach ($elements as $key => &$element) {
      unset($element['#admin_title']);
      unset($element['#test']);
      unset($element['#webform_id']);
    }

    return $elements;
  }

}
