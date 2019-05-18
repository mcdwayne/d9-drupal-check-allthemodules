<?php

/**
 * @file
 * Contains \Drupal\pines_notify\Form\PinesNotifyForm.
 */

namespace Drupal\pines_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for pines_notify admin settings.
 *
 * @ingroup pines_notify
 */
class PinesNotifyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'pines_notify_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pines_notify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pines_notify.settings');

    $form['title_success'] = array(
      '#title' => t('Success title'),
      '#type' => 'textfield',
      '#default_value' => $config->get('title_success'),
      '#description' => t('Leave empty to hide success titles.'),
    );

    $form['title_error'] = array(
      '#title' => t('Error title'),
      '#type' => 'textfield',
      '#default_value' => $config->get('title_error'),
      '#description' => t('Leave empty to hide error titles.'),
    );

    $form['animation'] = array(
      '#title' => t('Animation'),
      '#type' => 'select',
      '#options' => array(
        'show' => t('Show'),
        'fade' => t('Fade'),
        'slide' => t('Slide'),
      ),
      '#default_value' => $config->get('animation'),
      '#description' => t('The animation style used when displaying and hiding notifications.'),
    );

    $form['delay'] = array(
      '#title' => t('Fadeout delay'),
      '#type' => 'select',
      '#options' => array(
        2000 => t('2 seconds'),
        3000 => t('3 seconds'),
        5000 => t('5 seconds'),
        10000 => t('10 seconds'),
        30000 => t('30 seconds'),
      ),
      '#default_value' => $config->get('delay'),
      '#description' => t('The time it takes to hide user notifications.'),
    );

    $form['opacity'] = array(
      '#title' => t('Opacity'),
      '#type' => 'select',
      '#options' => array(
        '0.25' => t('25%'),
        '0.5' => t('50%'),
        '0.75' => t('75%'),
        '1' => t('100%'),
      ),
      '#default_value' => $config->get('opacity'),
      '#description' => t('The transparency level of the notifications.'),
    );

    $form['shadow'] = array(
      '#title' => t('Show dropshadows'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('shadow'),
      '#description' => t('Display dropshadows around the notifications.'),
    );

    $form['hide'] = array(
      '#title' => t('Automatically hide notices'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('hide'),
      '#description' => t('Uncheck this if you do not want remove notices until the page is reloaded or the user closes them.'),
    );

    $form['nonblock'] = array(
      '#title' => t('Non-blocking notices'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('nonblock'),
      '#description' => t('Check this if you want to be able to click behind notices.'),
    );

    $form['desktop'] = array(
      '#title' => t('Desktop web notifications'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('desktop'),
      '#description' => t('Check this if you want to be enable desktop web notifications.'),
    );

    $form['compression'] = array(
      '#type' => 'radios',
      '#title' => t('Javascript compression level'),
      '#options' => array(
        'minified' => t('Production (Minified)'),
        'source' => t('Development (Uncompressed Code)'),
      ),
      '#default_value' => $config->get('compression'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pines_notify.settings')
      ->set('title_success', $form_state->getValue('title_success'))
      ->set('title_error', $form_state->getValue('title_error'))
      ->set('animation', $form_state->getValue('animation'))
      ->set('delay', $form_state->getValue('delay'))
      ->set('opacity', $form_state->getValue('opacity'))
      ->set('shadow', $form_state->getValue('shadow'))
      ->set('hide', $form_state->getValue('hide'))
      ->set('nonblock', $form_state->getValue('nonblock'))
      ->set('desktop', $form_state->getValue('desktop'))
      ->set('compression', $form_state->getValue('compression'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
