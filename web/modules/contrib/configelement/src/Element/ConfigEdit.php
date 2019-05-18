<?php

namespace Drupal\configelement\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\configelement\EditableConfig\EditableConfigItemFactory;
use Drupal\configelement\EditableConfig\EditableConfigItemInterface;

/**
 * Class ConfigEdit
 *
 * @FormElement("configelement_edit")
 *
 * @package Drupal\configelement
 *
 * Adds an editable config element, #type: configelement_edit
 * Array keys:
 *  - #config_name The config name, like "system.site". Required.
 *  - #config_key The configkey, like "name". Defaults to "".
 *  - #language The config language overide if applicable. Defaults to none.
 *  - #register_submit An array of submit buttons' parents to add submit handler,
 *    defaults to [['actions', 'submit']].
 */
class ConfigEdit extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processConfigEdit'],
      ],
      '#element_validate' => [
        [$class, 'validateConfigEdit'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#config_name' => NULL,
      '#config_key' => '',
      '#language' => NULL,
      '#register_submit' => [['actions', 'submit']],
    ];
  }

  /**
   * #process callback for the config edit element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param array $completeForm
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function processConfigEdit(&$element, FormStateInterface $formState, &$completeForm) {
    /** @var EditableConfigItemFactory $editableConfigItemFactory */
    $editableConfigItemFactory = \Drupal::service('configelement.editable_config_item_factory');
    $editableConfigItem = $editableConfigItemFactory->get($element['#config_name'], $element['#config_key'], $element['#language']);
    self::buildElement($element['widget'], $editableConfigItem);
    $element['widget']['#parents'] = $element['#parents'];

    $editableConfigItem->addCachableDependencyTo($element);

    if ($element['#register_submit']) {
      foreach ($element['#register_submit'] as $submitParents) {
        $submit =& NestedArray::getValue($completeForm, $submitParents);
        array_unshift($submit['#submit'], [static::class, 'submitAllConfigEdit']);
        // Fix a core issue: In FormBuilder::doBuildForm, ::handleInputElement
        // is called before #process callbacks. FormState::setTriggeringElement
        // should save a reference but does not.
        // @todo Upstream this.
        $triggeringElement =& $formState->getTriggeringElement();
        if (isset($triggeringElement['#array_parents']) && $triggeringElement['#array_parents'] === $submitParents) {
          $triggeringElement = $submit;
        }
      }
      $elementParents = $element['#parents'];
      $completeForm['#configelement_edit_submit'][] = $elementParents;
    }
    return $element;
  }

  /**
   * Element builder.
   *
   * @param $element
   *   The form element to process.
   * @param EditableConfigItemInterface $editableConfigItem
   */
  protected static function buildElement(&$element, EditableConfigItemInterface $editableConfigItem) {
    // @see \Drupal\config_translation\Form\ConfigTranslationFormBase::createFormElement
    $label = $editableConfigItem->getLabel();
    if ($formElementType = $editableConfigItem->getFormElementType()) {
      $element = [
        '#type' => $formElementType,
        '#title' => $label,
        '#default_value' => $editableConfigItem->getValue(),
      ];
    }
    elseif ($editableConfigItem->isList()) {
      $element = [
        '#type' => 'fieldset',
        '#title' => $label,
        '#tree' => TRUE,
      ];
      foreach ($editableConfigItem->getElements() as $childName => $childItem) {
        static::buildElement($element[$childName], $childItem);
      }
    }
    else {
      $element = [
        '#type' => 'value',
        '#value' => $editableConfigItem->getValue(),
      ];
    }
  }

  /**
   * #element_validate callback for config edit element.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $formState
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateConfigEdit(&$element, FormStateInterface $formState, &$complete_form) {
    /** @var EditableConfigItemFactory $editableConfigItemFactory */
    $editableConfigItemFactory = \Drupal::service('configelement.editable_config_item_factory');
    $editableConfigItem = $editableConfigItemFactory->get($element['#config_name'], $element['#config_key'], $element['#language']);
    $editableConfigItem->setValue($element['#value']);
    // When we validate on the factory / wrapper level, we get errors for not
    // yet populated elements. So validat on the item level for now.
    // If this will be fixed upstream, consider defering and validating on the
    // factory / wrapper level.
    // @see \Drupal\Core\Form\FormValidator::doValidateForm
    $violations = $editableConfigItem->validate();
    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
    foreach ($violations as $violation) {
      $formState->setError($element, $violation->getMessage());
    }
  }

  /**
   * The auto-added submit callback.
   *
   * @param $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public static function submitAllConfigEdit($form, FormStateInterface $formState) {
    /** @var EditableConfigItemFactory $editableConfigItemFactory */
    $editableConfigItemFactory = \Drupal::service('configelement.editable_config_item_factory');
    foreach ($form['#configelement_edit_submit'] as $arrayParents) {
      $element = NestedArray::getValue($form, $arrayParents);
      $parents = $element['#parents'];
      $editableConfigItem = $editableConfigItemFactory->get($element['#config_name'], $element['#config_key'], $element['#language']);
      $editableConfigItem->setValue($formState->getValue($parents));
    }
    $editableConfigItemFactory->save();
  }

}
