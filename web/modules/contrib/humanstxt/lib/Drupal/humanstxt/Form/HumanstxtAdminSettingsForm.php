<?php

/**
 * @file
 * Contains \Drupal\humanstxt\Form\HumanstxtAdminSettingsForm.
 */

namespace Drupal\humanstxt\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Configure maintenance settings for this site.
 */
class HumanstxtAdminSettingsForm extends SystemConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'humanstxt_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('humanstxt.settings');

    $default_value = _humanstxt_get_content();
    if (empty($default_value)) {
      $default_value = <<<VALUE
/* TEAM */

/* SITE */
Tools:Drupal
VALUE;
    }

    $form['about'] = array(
      '#markup' => t('Add here the information about the different people who have contributed to building the website, you can find more info in <a href="@humanstxt">humanstxt.org</a> and use <a href="http://humanstxt.org/humans.txt">this file</a> as base file.', array('@humanstxt' => 'http://humanstxt.org')),
    );
    $form['humanstxt_content'] = array(
      '#type' => 'textarea',
      '#title' => t('Contents of humans.txt'),
      '#default_value' => $default_value,
      '#cols' => 60,
      '#rows' => 20,
      '#wysiwyg' => FALSE,
    );
    $form['humanstxt_display_link'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display humans.txt in head section as a link.'),
      '#description' => t('Activating this setting will make humans.txt file to be linked in the head section of the html code'),
      '#default_value' => $config->get('display_link'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('humanstxt.settings')
      ->set('content', $form_state['values']['humanstxt_content'])
      ->set('display_link', $form_state['values']['humanstxt_display_link'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
