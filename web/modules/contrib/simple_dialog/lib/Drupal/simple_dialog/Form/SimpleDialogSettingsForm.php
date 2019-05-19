<?php

/**
 * @file
 * Contains \Drupal\system\Form\SiteMaintenanceModeForm.
 * File should live in <module_root>/lib/Drupal/module_name/Form/ModuleSystemSettingsForm.php (PSR-0)
 */

namespace Drupal\simple_dialog\Form;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class SimpleDialogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simple_dialog_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // $config = $this->config('simple_dialog.settings');
    $config = $this->configFactory->get('simple_dialog.settings');
    // dpm($config->get('js_all'));

    $form['javascript']['js_all'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add simple dialog javscript files to all pages'),
      '#description' => t("This setting is for people who want to limit which pages the simple dialog javscript files are added to. If you disable this option, you will have to add the js files manually (using the function simple_dialog_add_js() ) to every page that you want to be able to invoke the simple dialog using the 'simple-dialog' class. If you are adding simple dialog links to the page using theme('simple_dialog'...) the necessary javascript is added within those functions so you should be okay.'"),
      '#default_value' => $config->get('js_all'),
    );

    $form['classes'] = array(
      '#type' => 'textfield',
      '#title' => t('Additional Classes'),
      '#description' => t("Supply a list of classes, separated by spaces, that can be used to launch the dialog. Do not use any leading or trailing spaces."),
      '#default_value' => $config->get('classes'),
    );

    $form['default_settings'] = array(
      '#type' => 'textfield',
      '#title' => t('Default Dialog Settings'),
      '#description' => t('Provide default settings for the simple dialog. The defaults should be formatted the same as you would in the "rel" attribute of a simple dialog link. See the <a href="/admin/help/simple_dialog">help page</a> under "HTML Implementation" for more information.'),
      '#default_value' => $config->get('defaults.settings'),
    );

    $form['default_target_selector'] = array(
      '#type' => 'textfield',
      '#title' => t('Default Target Selector'),
      '#description' => t('Provide a default html element id for the target page (the page that will be pulled into the dialog). This value will be used if no "name" attribute is provided in a simple dialog link.'),
      '#default_value' => $config->get('defaults.target_selector'),
    );

    $form['default_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Default Dialog Title'),
      '#description' => t('Provide a default dialog title. This value will be used if no "title" attribute is provided in a simple dialog link.'),
      '#default_value' => $config->get('defaults.title'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('simple_dialog.settings')
      ->set('js_all', $form_state['values']['js_all'])
      ->set('classes', $form_state['values']['classes'])
      ->set('defaults.settings', $form_state['values']['default_settings'])
      ->set('defaults.target_selector', $form_state['values']['default_target_selector'])
      ->set('defaults.title', $form_state['values']['default_title'])
      ->save();
    parent::submitForm($form, $form_state);
  }
}
