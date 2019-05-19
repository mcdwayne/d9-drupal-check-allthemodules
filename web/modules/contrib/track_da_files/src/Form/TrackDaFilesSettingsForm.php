<?php

namespace Drupal\track_da_files\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure book settings for this site.
 */
class TrackDaFilesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'track_da_files_admin_settings';
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
  	return ['track_da_files.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('track_da_files.settings');

    // Variable which stores options to show in main reports.
    $displays_datas = $config->get('displays_datas'/*, array(
      'total_ips',
      'average_by_ip',
      'last_display',
    )*/
    );

    // Variable which stores datas that will appear in files displays reports.
    $files_datas = $config->get('files_datas'/*, array(
      'timestamp',
      'filesize',
      'filemime',
    )*/
    );

    // Variable which stores stores options to show in file specific report.
    $single_file_datas = $config->get('single_file_datas'/*, array(
      'id',
      'referer',
      'browser',
      'browser_version',
      'browser_platform',
      'ip',
      'uid',
    )*/
    );

    // Variable which stores value for enabling user specific report.
    $user_report_enabled = $config->get('user_report_enabled');

    // Variable which stores stores options to show in user specific report.
    $single_user_datas = $config->get('single_user_datas'/*, array(
      'referer',
      'browser',
      'browser_version',
      'browser_platform',
      'ip',
    )*/
    );

    // Variable storing value for enabling showing tracking datas on the site.
    $file_field_links_show_enabled = $config->get('file_field_links_show_enabled');

    //if (module_exists('colorbox')) {
    if (\Drupal::moduleHandler()->moduleExists('colorbox')) {
      $colorbox_enabled = $config->get('colorbox_enabled');
    }


    $form['track_da_files'] = array(
      /* Some bug with vertical tabs, so temporary I put nested fieldsets */
      '#type' => 'details',
      /* '#type' => 'vertical_tabs', */
      '#title' => 'Reports availables datas',
    );

    // Main report configuration section.
    $form['track_da_files']['main_report'] = array(
      '#type' => 'details',
      '#title' => $this->t('Main report'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['track_da_files']['main_report']['displays_datas'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Displays datas to show in main report'),
      '#options' => array(
        'total_ips' => $this->t('Total ips'),
        'average_by_ip' => $this->t('Average count by ip'),
        'last_display' => $this->t('Last display date'),
      ),
      '#default_value' => $displays_datas,
      '#description' => $this->t('Datas that will appear in main files displays reports.'),
    );

    $form['track_da_files']['main_report']['files_datas'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Files datas to show in main report'),
      '#options' => array(
        'created' => $this->t('Created'),
        'filesize' => $this->t('File size'),
        'filemime' => $this->t('File mime'),
      ),
      '#default_value' => $files_datas,
      '#description' => $this->t('Specific files datas that will appear in main report.'),
    );

    // File specific report configuration section.
    $form['track_da_files']['file_report'] = array(
      '#type' => 'details',
      '#title' => $this->t('File report'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['track_da_files']['file_report']['single_file_datas'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Displays datas to show in file report'),
      '#options' => array(
        'id' => $this->t('Related content'),
        'browser' => $this->t('Browser'),
        'browser_version' => $this->t('Browser version'),
        'browser_platform' => $this->t('Browser platform'),
        'referer' => $this->t('Displayed from URL'),
        'ip' => $this->t('Ip'),
        'uid' => $this->t('User who displayed file'),
      ),
      '#default_value' => $single_file_datas,
      '#description' => $this->t('Datas that will appear in file specific report.'),
    );

    // User specific report configuration section.
    $form['track_da_files']['user_report'] = array(
      '#type' => 'details',
      '#title' => $this->t('User report'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['track_da_files']['user_report']['user_report_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable user specific report'),
      '#default_value' => $user_report_enabled,
    );

    $form['track_da_files']['user_report']['single_user_datas'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Displays datas to show in user report'),
      '#options' => array(
        'browser' => $this->t('Browser'),
        'browser_version' => $this->t('Browser version'),
        'browser_platform' => $this->t('Browser platform'),
        'referer' => $this->t('Displayed from URL'),
        'ip' => $this->t('Ip'),
      ),
      '#default_value' => $single_user_datas,
      '#description' => $this->t('Datas that will appear in users displays reports.'),
      '#states' => array(
        'visible' => array(
          ':input[name=user_report_enabled]' => array('checked' => TRUE),
        ),
      ),
    );


    // Configure to show datas near from file field links.
    $form['track_da_files_file_field_links_show'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('File field links'),
    );

    $form['track_da_files_file_field_links_show']['file_field_links_show_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Provide counter near file field links'),
      '#default_value' => $config->get('file_field_links_show_enabled'),
      '#description' => $this->t('A counter showing number of times a file has been displayed will appear in a near from files links'),
    );


    if (\Drupal::moduleHandler()->moduleExists('colorbox')) {

    $form['track_da_files_colorbox'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Colorbox'),
    );

    $form['track_da_files_colorbox']['track_da_files_colorbox_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Track da files colorbox compatibility'),
      '#default_value' => $track_da_files_colorbox_enabled,
      '#description' => $this->t('When enabled, in content interface select display "tdf : Colorbox image" for desired fields'),
    );

  }

  // Render the role overview.
  $form['track_da_files_roles']['role_settings'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Roles'),
  );

  $form['track_da_files_roles']['role_settings']['roles'] = array(
    '#type' => 'radios',
    '#title' => $this->t('Add tracking for roles'),
    '#options' => array(
      $this->t('Add to the selected roles only'),
      $this->t('Add to every role except the selected ones'),
    ),
    '#default_value' => $config->get('roles'),
  );

  $role_options_array = user_roles();
  foreach($role_options_array as $key => $value) {
    $role_options[] = $key;
  }

  $form['track_da_files_roles']['role_settings']['specific_roles'] = array(
    '#type' => 'checkboxes',
    '#title' => $this->t('Roles'),
    '#default_value' => $config->get('specific_roles'),
    '#options' => $role_options,
    '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
  );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
 /* public function validateForm(array &$form, array &$form_state) {

    parent::validateForm($form, $form_state);
  }*/

  /**
   * {@inheritdoc}
   */
public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $config = $this->config('track_da_files.settings');
    $config->set('displays_datas', $values['displays_datas'])->save();
    $config->set('files_datas', $values['files_datas'])->save();
    $config->set('single_file_datas', $values['single_file_datas'])->save();
    $config->set('user_report_enabled', $values['user_report_enabled'])->save();
    $config->set('single_user_datas', $values['single_user_datas'])->save();
    $config->set('file_field_links_show_enabled', $values['file_field_links_show_enabled'])->save();
    $config->set('roles', $values['roles'])->save();
    $config->set('specific_roles', $values['specific_roles'])->save();

    parent::submitForm($form, $form_state);
  }

}

