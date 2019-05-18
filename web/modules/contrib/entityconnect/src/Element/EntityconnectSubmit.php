<?php

namespace Drupal\entityconnect\Element;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Drupal\entityconnect\EntityconnectNestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Submit;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a custom form submit button for entityconnect.
 *
 * Submit buttons are processed the same as regular buttons, except they trigger
 * the form's submit handler.
 *
 * Properties:
 * - #submit: Specifies an alternate callback for form submission when the
 *   submit button is pressed.  Use '::methodName' format or an array containing
 *   the object and method name (for example, [ $this, 'methodName'] ).
 * - #value: The text to be shown on the button.
 * - #key: 'all' |  The delta of the item within a multi-item field.
 * - #field: The field name.
 * - #entity_type_target: The target entity type.
 * - #acceptable_types: List of acceptable target bundles.
 * - #add_child: Boolean - Whether or not an entity is being added.
 *
 * Usage Example:
 * @code
 * $form['actions']['submit'] = array(
 *   '#type' => 'entityconnect_submit,
 *   '#value' => $this->t('Save'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Submit
 *
 * @FormElement("entityconnect_submit")
 */
class EntityconnectSubmit extends Submit {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#key' => '',
      '#field' => '',
      '#entity_type_target' => 'node',
      '#acceptable_types' => array(),
      '#add_child' => FALSE,
      '#validate' => array(
        array($class, 'validateSubmit'),
      ),
      '#submit' => array(
          array($class, 'addEditButtonSubmit'),
      ),
      '#weight' => 1,
      '#limit_validation_errors' => array(),
    ) + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderButton($element) {
    $element = parent::preRenderButton($element);

    // Attach entityconnect assets.
    $element['#attached']['library'][] = 'entityconnect/entityconnect';

    // Support Clientside Validation.
    $element['#attributes']['class'][] = 'cancel';
    if (empty($element['#attributes']['title'])) {
      $element['#attributes']['title'] = $element['#add_child'] ? t('Add') : t('Edit');
    }

    return $element;
  }

  /**
   * Form #validate callback for the entityconnect_submit element.
   *
   * Used to bypass validation of the parent form.
   *
   * @param array $form
   *   The parent form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public static function validateSubmit(array $form, FormStateInterface $form_state) {
    // Ignore all validation.
    // @todo: Probably should validate the fields that were entered.
  }

  /**
   * Button #submit callback: Call when an entity is to be added or edited.
   *
   * We cache the current state and form
   * and redirect to the add or edit page with an append build_cached_id.
   *
   * @param array $form
   *   Buttons will be added to this form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function addEditButtonSubmit(array $form, FormStateInterface $form_state) {
    $cacheId = "entityconnect-" . $form['#build_id'];
    $triggeringElement = $form_state->getTriggeringElement();
    $field = $triggeringElement['#field'];
    $key = $triggeringElement['#key'];

    $entityType = $triggeringElement['#entity_type_target'];
    $acceptableTypes = isset($triggeringElement['#acceptable_types']) ? $triggeringElement['#acceptable_types'] : NULL;

    /** @var Entity $source_entity */
    $source_entity = $form_state->getFormObject()->getEntity();
    $fieldInfo = FieldStorageConfig::loadByName($source_entity->getEntityTypeId(), $field);

    // Get the list of all parents element for the clicked button.
    $parents = isset($triggeringElement['#parents']) ? $triggeringElement['#parents'] : NULL;
    $keyExists = NULL;

    // The button is nested at the same level as the reference field.
    // This gets the target_id wherever button is nested via parents.
    $fieldContainer = EntityconnectNestedArray::getValue($form_state->getUserInput(), $parents, $keyExists);

    // Initialize target_id.
    $target_id = '';

    // Get the target id from the reference field container.
    if ($keyExists) {
      if (is_array($fieldContainer)) {
        foreach ($fieldContainer as $key1 => $value) {
          if (is_array($value)) {
            foreach ($value as $key2 => $value2) {
              if (!is_null($value2)) {
                $target_id[$key2] = $value2;
              }
            }
          }
          elseif (is_numeric($key1)) {
            $target_id[] = $value;
          }
          elseif ($key1 === 'target_id') {
            $target_id = $value;
            break;
          }
        }
      }
      else {
        $target_id = $fieldContainer;
      }
    }

    // Autocomplete tags style target.
    if (is_string($target_id) && !is_numeric($target_id)) {
      $target_id = explode(',', $target_id);
    }

    if (is_array($target_id)) {
      $target_id = array_filter($target_id);
      if (count($target_id) == 1) {
        $target_id = array_shift($target_id);
      }
    }

    // If no entity has been chosen to edit, redirect to the original node.
    if (!$triggeringElement['#add_child'] && (!$target_id || $target_id == '_none')) {
      drupal_set_message(
        t('You must select at least one entity to update.'),
        'error'
      );
      $form_state->setRedirectUrl(Url::fromRoute('<current>'));
      return;
    }

    // Setup the data of the current form for caching.
    $data = array(
      'form'       => $form,
      'form_state' => $form_state,
      'dest'       => \Drupal::routeMatch(),
      'params'     => \Drupal::request()->query->all(),
      'field'      => $field,
      'field_info' => $fieldInfo,
      'key'        => $key,
      'add_child'  => $triggeringElement['#add_child'],
      'target_id'  => $target_id,
      'target_entity_type' => $entityType,
      'acceptable_types' => $acceptableTypes,
      'field_container' => $fieldContainer,
      'field_container_key_exists' => $keyExists,
    );

    // Give other modules the chance to change it.
    \Drupal::moduleHandler()->alter('entityconnect_add_edit_button_submit', $data);

    // Store the form data in the cache.
    $tempStore = \Drupal::getContainer()->get('entityconnect.cache');
    $tempStore->set($cacheId, $data);

    // Replace the destination with the add/edit form of the connecting entity.
    \Drupal::request()->query->remove('destination');

    if ($data['add_child']) {
      $form_state->setRedirect('entityconnect.add', array('cache_id' => $cacheId));
    }
    else {
      if ($data['target_id']) {
        $form_state->setRedirect('entityconnect.edit', array('cache_id' => $cacheId));
      }
    }

  }

}
