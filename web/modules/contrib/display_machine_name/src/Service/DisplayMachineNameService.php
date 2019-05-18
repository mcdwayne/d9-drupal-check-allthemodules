<?php

namespace Drupal\display_machine_name\Service;

/**
 * General DisplayMachineName service.
 */
class DisplayMachineNameService {

  /**
   * Enable the display machine name for an EntityFormDisplay.
   */
  public function enableDisplayMachineName(&$form_display, $enable = TRUE) {
    $form_display->setThirdPartySetting('display_machine_name', DISPLAY_MACHINE_NAME_ENABLED_ID, $enable)->save();
  }

  /**
   * Return the changed field label.
   */
  public function getChangedFieldLabel($field_label, $field_name) {
    return $field_label . ' [' . $field_name . ']';
  }

  /**
   * Alter the form display form to show the machine name.
   */
  public function alterFormDisplayMachineName(&$form) {
    $field_names = $form['#fields'];

    // Add in the machine name to the human_name.
    foreach ($field_names as $field_name) {
      $field_label = $form['fields'][$field_name]['human_name']['#plain_text'];
      $form['fields'][$field_name]['human_name']['#plain_text'] = $this->getChangedFieldLabel($field_label, $field_name);
    }

    if (isset($form['#fieldgroups'])) {
      foreach ($form['#fieldgroups'] as $field_group_name) {
        $field_label = $form['fields'][$field_group_name]['human_name']['#markup'];
        $form['fields'][$field_group_name]['human_name']['#markup'] = $this->getChangedFieldLabel($field_label, $field_group_name);
      }
    }
  }

}
