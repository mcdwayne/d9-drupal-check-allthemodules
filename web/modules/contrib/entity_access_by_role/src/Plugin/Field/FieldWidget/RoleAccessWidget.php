<?php

namespace Drupal\entity_access_by_role\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'role_access_widget' widget.
 *
 * @FieldWidget(
 *   id = "role_access_widget",
 *   module = "entity_access_by_role",
 *   label = @Translation("Role Access Widget"),
 *   field_types = {
 *     "role_access"
 *   },
 *   multiple_values = TRUE
 * )
 */
class RoleAccessWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += [
      '#type' => 'select',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $this->getSelectedOptions($items),
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->options) > 1,
    ];

    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {

    if (!isset($this->options)) {

      $options = $this->getAvailableRoles();

      // Add an empty option if the widget needs one.
      if ($empty_label = $this->getEmptyLabel()) {
        $options = ['_none' => $empty_label] + $options;
      }

      // Options might be nested ("optgroups"). If the widget does not support
      // nested options, flatten the list.
      if (!$this->supportsGroups()) {
        $options = OptGroup::flattenOptions($options);
      }

      $this->options = $options;
    }

    return $this->options;
  }

  private function getAvailableRoles() {

    $alwaysAllowed = array_filter($this->getFieldSetting("always_allowed"), function ($role) {

      return !empty($role);
    });

    return array_diff_key(entity_access_by_role_roles_without_bypass_access(), $alwaysAllowed);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {

    if ($this->multiple) {
      return FALSE;
    }

    // Single select: add a 'none' option for non-required fields,
    // and a 'select a value' option for required fields that do not come
    // with a value selected.

    return t('- Select a value -');
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {

    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

}
