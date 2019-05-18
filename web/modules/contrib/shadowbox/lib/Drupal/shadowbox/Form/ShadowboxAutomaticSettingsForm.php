<?php

/**
 * @file
 * Contains \Drupal\shadowbox\Form\ShadowboxAutomaticSettingsForm.
 */
namespace Drupal\shadowbox\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure automatic settings for this site.
 */
class ShadowboxAutomaticSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shadowbox_automatic_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('shadowbox.automatic');

    $form['shadowbox_auto_enable_all_images'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable for all image links'),
      '#description' => t('Enable this option to automatically add the <code>rel="shadowbox"</code> attribute to all links pointing to an image file.'),
      '#default_value' => $config->get('shadowbox_auto_enable_all_images'),
    );
    $form['shadowbox_auto_gallery'] = array(
      '#type' => 'checkbox',
      '#title' => t('Group images as a shadowbox gallery'),
      '#description' => t('Enable this options to open all images in a shadowbox gallery rather than individually.'),
      '#default_value' => $config->get('shadowbox_auto_gallery'),
    );
    $form['shadowbox_enable_globally'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable shadowbox globally'),
      '#description' => t('Add shadowbox library on all nodes.'),
      '#default_value' => $config->get('shadowbox_enable_globally'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    $this->config('shadowbox.automatic')
      ->set('shadowbox_auto_enable_all_images', $form_state['values']['shadowbox_auto_enable_all_images'])
      ->set('shadowbox_auto_gallery', $form_state['values']['shadowbox_auto_gallery'])
      ->set('shadowbox_enable_globally', $form_state['values']['shadowbox_enable_globally'])
      ->save();
  }
}