'Cancel Account Separate Form' module.

Description:
* This module provides cancel user account separate form and adds it to user
edit form by default if user has cancel account permissions edits his own
account.

Installation & Usage:
* Download and enable this module.
* You can customize this form using hook_form_alter() or
hook_form_cancel_account_form_alter().
* Cancel user account separate form can be added to the any page using
\Drupal::formBuilder()->getForm('Drupal\cancel_account\Form\CancelAccountForm')
