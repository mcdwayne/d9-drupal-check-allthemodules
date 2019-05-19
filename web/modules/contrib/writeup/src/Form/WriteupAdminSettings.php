<?php

/**
 * @file
 * Contains \Drupal\writeup\Form\WriteupAdminSettings.
 */

namespace Drupal\writeup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Checks the existence of the directory specified in $teststr.
 * If validation fails, a non-zero error string is returned
 *
 * @param $teststr
 *   The string containing the name of the directory to check.
 */
function writeup_not_directory($teststr) {
  $directory = rtrim($teststr, '/\\');
  if (!is_dir($directory)) return t('The directory %directory does not exist.', array('%directory' => $directory));
  else return False;
}

/**
 * Validates a number in $form_element.
 * If validation fails, the form element is flagged.
 *
 * @param $form_element
 *   The form element containing the name of the file to check.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 */
function writeup_check_number($form_element, FormStateInterface $form_state) {
  $number = rtrim($form_element['#value'], '/\\');
  if (!is_numeric($number)) {   // Check if directory exists.
    $form_state->setErrorByName($form_element['#parents'][0], t('Not a valid number: %number', array('%number' => $number)));
  }
  return $form_element;
}

/**
 * Returns version of Writeup binary
 *
 * @return
 *   version of Writeup binary, or error message
 */
function _writeup_version() {
  $versionmin = 2.51;
  $err = ' <span style="color:red;font-weight:bold;">';
  $errend = '</span>';
  $writeup = rtrim(\Drupal::config('writeup.settings')->get('writeup_loc'), '/\\') . '/writeup';
  if (!is_file($writeup)) {   // Check if Writeup binary exists.
    $msg =  $err . t('No Writeup binary executable was found in this folder.') . $errend;
  }
  else {
    $msg = shell_exec("$writeup --version");
    if ($versionmin > shell_exec("$writeup --versionnum")) {
      $msg .= $err
      . t('Error: module requires a minimum of version %versionmin of the Writeup binary.', array('%versionmin' => $versionmin))
      . $errend;
    }
  }
  return $msg;
}

class WriteupAdminSettings extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'writeup_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['writeup.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = $this->config('writeup.settings');
    $filters_admin = $this->l($this->t('the Text Format admin page'), Url::fromRoute('filter.admin_overview'));
    $writeup_status_page = $this->l($this->t('the Writeup status page'), Url::fromRoute('writeup.status_page'));

    $form['writeup_admin'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global settings for Writeup input filter'),
      '#description' => $this->t('<p>Here is where common settings for the Writeup input filter are set. Each format can be separately configured on the input format configuration page where different include files can be assigned to each input format. <em>It is essential that this be done for the filter to work.</em></p><ul><li>See: @filters_admin </li><li>All pages that use the Writeup filter in their bodies are listed here: @writeup_status_page</li></ul>',
        array(
          '@filters_admin' => $filters_admin,
          '@writeup_status_page' => $writeup_status_page,
        )
      ),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['writeup_loc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location for Writeup binary (common to all formats).'),
      '#description' => $this->t('The directory containing the Writeup executable binary, e.g. /opt/writeup') . '<br /><strong>' . $this->t('Found version:') . ' ' . _writeup_version() . '</strong>',
      '#default_value' => $config->get('writeup_loc'),
      '#size' => 70,
    ];
    $form['writeup_incdir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory for definitions include file(s) (common to all formats).'),
      '#description' => $this->t('The directory for definitions files that are included in writeup processing.') . '<br />' . $this->t('It is suggested that this be somewhere in your theme directory, e.g. sites/default/themes/<em>mytheme</em>'),
      '#default_value' => $config->get('writeup_incdir'),
      '#size' => 70,
    ];
    $form['writeup_ver'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default version of Writeup (common to all formats).'),
      '#description' => $this->t('The setting $VER=n.nn is prepended to each file before processing. Leave blank if not required.'),
      '#default_value' => $config->get('writeup_ver'),
      //'#after_build' => array('::check_version_number'),
      '#size' => 40,
    ];
    $form['writeup_logerrors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log all Writeup errors'),
      '#description' => $this->t('Create an entry in the log every time there is an error processing a page.
      This makes it easier to ensure that there are no errors in any pages on the site.'),
      '#default_value' => $config->get('writeup_logerrors'),
    ];
    $form['writeup_showerrors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all Writeup errors'),
      '#description' => $this->t('Show all Writeup errors in a block at the top of the content.'),
      '#default_value' => $config->get('writeup_showerrors'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * Note that this only validates new data, not existing values
   */
  public function validateForm(array &$form, FormStateInterface $form_state) { 
    if (!is_numeric($form_state->getValue('writeup_ver'))) {   // Check version number is numeric
      $form_state->setErrorByName('writeup_ver', t('Not a valid number: %number', array('%number' => $form_state->getValue('writeup_ver'))));
    }

    $invalid = writeup_not_directory($form_state->getValue('writeup_loc'));
    if ($invalid) $form_state->setErrorByName('writeup_loc', $invalid);

    $invalid = writeup_not_directory($form_state->getValue('writeup_incdir'));
    if ($invalid) $form_state->setErrorByName('writeup_incdir', $invalid);
  }

  /**
  * Checks the version number specified in $form_element is numeric.
  *
  * @param $form_element
  *   The form element containing the number.
  * @param \Drupal\Core\Form\FormStateInterface $form_state
  *   The current state of the form.
  */
  /* now done in validateForm
  function check_version_number($form_element, FormStateInterface $form_state) { //?? maybe should be private
    $number = rtrim($form_element['#value'], '/\\');
    if (!is_numeric($number)) {   // Check if directory exists.
      $form_state->setErrorByName($form_element['#parents'][0], t('Not a valid number: %number', array('%number' => $number)));
    }
    return $form_element;
  }
  */

  /**
  * Checks the existence of the directory specified in $form_element.
  *
  * @param $form_element
  *   The form element containing the name of the directory to check.
  * @param \Drupal\Core\Form\FormStateInterface $form_state
  *   The current state of the form.
  */

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('writeup.settings')
      ->set('writeup_loc', $form_state->getValue('writeup_loc'))
      ->set('writeup_incdir', $form_state->getValue('writeup_incdir'))
      ->set('writeup_ver', $form_state->getValue('writeup_ver'))
      ->set('writeup_logerrors', $form_state->getValue('writeup_logerrors'))
      ->set('writeup_showerrors', $form_state->getValue('writeup_showerrors'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

}
