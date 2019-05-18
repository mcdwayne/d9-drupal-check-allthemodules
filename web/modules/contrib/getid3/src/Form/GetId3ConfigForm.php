<?php

/**
 * @file
 * Contains \Drupal\getid3\Form\GetId3ConfigForm.
 */

namespace Drupal\getid3\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\String;

class GetId3ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'getid3_systemconfigformbase';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = getid3_get_path();
    $form['getid3_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#default_value' => $path,
      '#description' => $this->t('The location where getID3() is installed. Relative paths are from the Drupal root directory.'),
    );

    $form['getid3_path']['#after_build'][] = array(get_class($this), 'afterBuild');

    if ($version = getid3_get_version()) {
      $form['getid3_version'] = array(
        '#type' => 'item',
        '#title' => $this->t('Version'),
        '#markup' => '<pre>' . String::checkPlain($version) . '</pre>',
        '#description' => $this->t("If you're seeing this it indicates that the getID3 library was found."),
      );

      // Check for existence of the 'demos' folder, contained in the getID3
      // library. The contents of this folder create a potential securtiy hole,
      // so we recommend that the user delete it.
      $getid3_demos_path = $path . '/../demos';
      if (file_exists($getid3_demos_path)) {
        drupal_set_message($this->t("Your getID3 library is insecure! The demos distributed with getID3 contains code which creates a huge security hole. Remove the demos directory (%path) from beneath Drupal's directory.", array('%path' => realpath($getid3_demos_path))), 'error');
      }
    }
    $show_warnings = $this->config('getid3.settings')->get('getid3_show_warnings');
    if(empty($show_warnings)){
      $show_warnings = FALSE;
    }
    $form['getid3_show_warnings'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Display Warnings"),
      '#default_value' => $show_warnings,
      '#description' => $this->t("Check this to display the warning messages from the getID3 library when reading and writing ID3 tags. Generally it's a good idea to leave this unchecked, getID3 reports warnings for several trivial problems and the warnings can be confusing to users. This setting can be useful when debugging problems with the ID3 tags."),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Save the new value.
    $this->config('getid3.settings')
      ->set('path', $form_state->getValue('getid3_path'))
      ->set('getid3_show_warnings', $form_state->getValue('getid3_show_warnings'))
      ->save();
  }

  /**
   * Verifies that getid3 is in the directory specified by the form element.
   *
   * Checks that the directory in $form_element exists and contains files named
   * 'getid3.php' and 'write.php'. If validation fails, the form element is
   * flagged with an error.
   *
   * @param array $form_element
   *   The form element containing the name of the directory to check.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public static function afterBuild(array $form_element, FormStateInterface $form_state) {
    $path =  $form_state->getValue('getid3_path');
    if (!is_dir($path) || !(file_exists($path . '/getid3.php') && file_exists($path . '/write.php'))) {
      drupal_set_message(t('The getID3 files <em>getid3.php</em> and <em>write.php</em> could not be found in the %path directory.', array('%path' => $path)),'error');
    }
    return $form_element;
  }
}
