<?php

namespace Drupal\bulk_user_registration\Form;

use Drupal\bulk_user_registration\Controller\UserImportController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Implement Class BulkUserImport for import form.
 */
class BulkUserImport extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_user_registration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bulk_user_registration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get listing of all user role.
    $form['bulk_user_registration_user_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Select User Role'),
      '#options' => UserImportController::getAllUserRoleTypes(),
      '#default_value' => $this->t('Select'),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'callback' => '::userImportcallback',
        'wrapper' => 'user_import_fields_change_wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['file_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Import CSV File'),
      '#size' => 40,
      '#description' => $this->t('Select the CSV file to be imported.'),
      '#required' => FALSE,
      '#autoupload' => TRUE,
      '#upload_validators' => ['file_validate_extensions' => ['csv']],
    ];

    $form['override_role'] = [
      '#type' => 'radios',
      '#options' => [
        '1' => 'True',
        '0' => 'False',
      ],
      '#default_value' => '0',
      '#title' => $this->t('Override Role From CSV'),
      '#size' => 40,
      '#description' => $this->t('Override role from CSV and create role if not exist in Durpal.'),
    ];

    $form['loglink'] = [
      '#type' => 'link',
      '#title' => $this->t('Check Log..'),
      '#url' => Url::fromUri('base:sites/default/files/userimportlog.txt'),
    ];

    $form['import_ct_markup'] = [
      '#suffix' => '<div id="user_import_fields_change_wrapper"></div>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * User Import Sample CSV Creation.
   */
  public function userImportcallback(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $result = '';
    $ajax_response = new AjaxResponse();
    $userRoleType = $form_state->getValue('bulk_user_registration_user_role');
    $username = 'username,';
    $username .= 'email,';
    $username .= 'status,';
    $username .= 'role,';
    $userFields = substr($username, 0, -1);
    $result .= '</tr></table>';
    $sampleFile = $userRoleType . '.csv';
    $handle = fopen("sites/default/files/" . $sampleFile, "w+") or die("There is no permission to create log file. Please give permission for sites/default/file!");
    fwrite($handle, $userFields);
    $result = '<a class="button button--primary" style="float:left;" href="' . $base_url . '/sites/default/files/' . $sampleFile . '">Click here to download Sample CSV</a>';
    $ajax_response->addCommand(new HtmlCommand('#user_import_fields_change_wrapper', $result));
    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $userList = $form_state->getValue('bulk_user_registration_user_role');
    // Get override role status.
    $override_role = $form_state->getValue('override_role');
    BulkUserImport::createUser($_FILES, $userList, $override_role);
  }

  /**
   * To get user information based on emailIds.
   */
  public static function getUserInfo($userArray, $role_type, $override_role) {
    // Get current user language.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    foreach ($userArray as $value) {
      $user_status = user_load_by_mail($value['email']);
      if ($user_status == FALSE) {
        $user = User::create();
        $user->uid = '';
        $user->setUsername($value['username']);
        $user->setEmail($value['email']);
        $user->set("init", $value['email']);
        $user->set("langcode", $language);
        $user->set("preferred_langcode", $language);
        $user->set("preferred_admin_langcode", $language);
        $user->enforceIsNew();
        $user->activate();
        if ($role_type !== 'authenticated' && $role_type !== 'anonymous' || ((int) $override_role == 1)) {
          if ((int) $override_role) {
            // List out all roles.
            $list_of_roles = user_role_names();
            $user_role = str_replace(' ', '_', $value['role']);
            if (!in_array($user_role, $list_of_roles) && (array_key_exists($user_role, $list_of_roles) === FALSE)) {
              // Create Role.
              $role = Role::create([
                  'id' => str_replace(' ', '_', strtolower($user_role)),
                  'label' => str_replace('_', ' ', ucwords($value['role'])),
              ]);

              $role->save();
              // New role created and assign to user.
              $user->addRole($role->id());
            }
            else {
              // If role is exist in drupal.
              $user->addRole($user_role);
            }
          }
          else {
            // Assign role to user for anonymous and authenticate.
            $user->addRole($role_type);
          }
        }
        $user->save();

        // Notify to user via mail.
        _user_mail_notify('register_no_approval_required', $user);
      }
    }
  }

  /**
   * To import data as Content type nodes.
   */
  public function createUser($filedata, $role_type, $override_role) {
    drupal_flush_all_caches();
    global $base_url;
    // CSV label row for indexing.
    $fieldNames = ['username', 'email', 'status', 'role'];
    // Code for import csv file.
    $mimetype = 1;
    if ($mimetype) {
      $location = $filedata['files']['tmp_name']['file_upload'];
      if (($handle = fopen($location, "r")) !== FALSE) {
        $keyIndex = [];
        while (($data = fgetcsv($handle)) !== FALSE) {
          // $line is an array of the csv elements.
          $csv_user_data[] = $data;
        }
        foreach ($csv_user_data as $key => $user_value) {
          foreach ($user_value as $key => $value) {
            if ($fieldNames[$key] != $value) {
              $keyIndex[$fieldNames[$key]] = $value;
            }
          }
          $user_index[] = $keyIndex;
        }
        $user_data = array_filter($user_index);

        // Get usr info and create if user not exist in drupal.
        BulkUserImport::getUserInfo($user_data, $role_type, $override_role);
        fclose($handle);
        $url = $base_url . "/admin/people";
        header('Location:' . $url);
        exit;
      }
    }
  }

}
