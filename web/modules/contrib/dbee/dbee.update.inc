<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * Prevent from upgrading from version previous to v8.x-2.x
 *
 * Dependency has changed from previous version to v8.x-2.x. No update script.
 *
 *  @return bool
 *   return TRUE if upgrade from version previous to v8.x-2.x
 */
function dbee_update_previous_to_8200() {
  // Retreive de current update number.
  $db_number = drupal_get_installed_schema_version('dbee');
  // From v8.x-1.x
  $dbee_table_exists = \Drupal::database()->schema()->tableExists('dbee');
  // From v7.x-3.x
  $dbee_field_exists = \Drupal::database()->schema()->fieldExists('users', 'dbee_mail');
  return (is_numeric($db_number) && $db_number > 0 && $db_number < 8200 && ($dbee_table_exists || $dbee_field_exists));
}

/*
 * Prevent from uninstalling old aes module.
 *
 * Executed from uninstall module page on upgrading from prior to v8.x-2.x.
 */
function dbee_prevent_uninstall_validate($form,  FormStateInterface $form_state) {
  if (dbee_update_previous_to_8200()) {
    $uninstall = $form_state->getValue('uninstall');
    if ($uninstall && (!empty($uninstall['aes']) || !empty($uninstall['dbee']))) {
      // User on the verge to uninstall aes module. Stop him.
      $form_state->setErrorByName('uninstall[aes]', t('The Dabase Email Encryption module (Bbee) prevent you from uninstalling the AES module. In order to preserve your Users emails addresses, you should rollback to the your Dbee previous version, uninstall it, then reinstall current version and unintall AES module.'));
    }
  }
}