<?php

namespace Drupal\contactlist\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'contact_group_checkboxes' widget.
 *
 * @FieldWidget(
 *   id = "contact_group_checkboxes",
 *   label = @Translation("Contact Group Checkboxes"),
 *   description = @Translation("A checkbox widget for contact groups."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 *
 * @todo This widget needs to be completed.
 */
class ContactGroupCheckboxWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $widget = array (
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#element_validate' => [[$this, '_contactlist_group_widget_validate']],
      '#collapsible' => TRUE,
      '#title' => t('Contact Groups'),
      '#description' => t('Specify existing or new contact groups.'),
    );

    $widget['typed_input'] = array (
      '#type' => 'textfield',
      '#title' => t('New Groups'),
      '#description' => t('Type in new groups here (comma-separated).'),
      '#size' => 40,
      '#maxlength' => 255,
    );

    $all = array_keys(contact_groups_all_groups($user->uid, 'name'));
    $widget['checked_input'] = array (
      '#type' => 'checkboxes',
      '#title' => t('Existing Groups'),
      '#description' => t('Click here to choose existing contact groups.'),
      '#options' => count($all) ? array_combine($all, $all) : array(),
      '#value' => array_flip(array_keys(contact_groups_get_groups($ctid, 'name'))),
    );
    return $widget;
  }

  /**
   * Contact groups widget form
   * @see entitygroup_field_widget_form()
   *
   * @param unknown_type $contact
   * @param unknown_type $fieldname
   * @param unknown_type $type
   * @return form arr
   */
  function _contactlist_group_widget($contact, $fieldname, $type = 'autocomplete') {
    $instance = field_info_instance('contactlist_entry', $fieldname, 'contactlist_entry');
    $field = field_info_field($fieldname);
    // @todo Need to decouple the entitygroup dependency here!!!
    $type = entitygroup_type_load($field['settings']['entitygrouptype']);  // XXX Decouple the entitygroup dependency
    $widget['new_groups'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('New Groups'),
      '#description' => $this->t('Type in new groups here (comma-separated).'),
      '#size' => 40,
      '#maxlength' => 255,
    );

    $groups = array();
    foreach (entitygroup_filter_access(entitygroup_load_by_type($type->name), $type) as $group) {
      $groups[$group->egid] = $group->name; // . ' - ' . l($this->t('Delete (implement destination here)'), $basepath . '/' . $group->egid . '/delete', array('destination' => drupal_get_destination()));
    }
    $widget['old_groups'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Existing Groups'),
      '#description' => $this->t('Click here to choose existing contact groups.'),
      '#options' => $groups,
    );

    return $widget;
  }

  /**
   * Validate callback for the contact groups widget
   * @see _entitygroup_widget_validate()
   *
   * @param unknown_type $element
   * @param unknown_type $form_state
   * @param unknown_type $form
   */
  function _contactlist_group_widget_validate(&$element, &$form_state, $form) {
    $values = NestedArray::getValue($form_state['values'], $element['#parents']);
    $groups = '';
    if (isset($values['groups'])) {
      $groups = $values['groups'];
    }
    elseif (isset($values['new_groups'])) {
      $groups = $values['new_groups'];
    }
    if (isset($values['old_groups'])) {
      $old = array_filter($values['old_groups']);
      $groups .= ':' . implode('|', $old);
    }
    $form_state->setValueForElement($element, array('groups' => $groups));
  }

}
