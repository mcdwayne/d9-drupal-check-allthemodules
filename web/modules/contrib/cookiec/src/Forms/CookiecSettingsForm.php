<?php

namespace Drupal\Cookiec\Forms;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Cookiec configuration form
 */
class CookiecSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cookiec_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cookiec.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('cookiec.settings');

    $form['config'] = array(
      '#type' => 'details',
      '#title' => $this->t('General Config'),
    );

    $form['popup_enabled'] = array(
      '#type' => 'checkbox',
      '#group' => 'config',
      '#title' => $this->t('Enable popup'),
      '#default_value' => $config->get('popup_enabled'),
    );

    $form['popup_agreed_enabled'] = array(
      '#type' => 'checkbox',
      '#group' => 'config',
      '#title' => $this->t('Enable Agreed'),
      '#default_value' => $config->get('popup_agreed_enabled'),
    );

    $form['popup_hide_agreed'] = array(
      '#type' => 'checkbox',
      '#group' => 'config',
      '#title' => $this->t('popup_hide_agreed'),
      '#default_value' => $config->get('popup_hide_agreed'),
    );

    $form['popup_height'] = array(
      '#type' => 'textfield',
      '#group' => 'config',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('popup_height'),
    );

    $form['popup_width'] = array(
      '#type' => 'textfield',
      '#group' => 'config',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('popup_width'),
    );

    $form['popup_delay'] = array(
      '#type' => 'textfield',
      '#group' => 'config',
      '#title' => $this->t('popup_delay'),
      '#default_value' => $config->get('popup_delay'),
      '#description' => $this->t('Deley popup in second'),
    );

    $form['popup_position'] = array(
      '#type' => 'select',
      '#group' => 'config',
      '#options' => array(
        TRUE => $this->t('Top'),
        NULL => $this->t('Bottom'),
      ),
      '#title' => $this->t('popup_position'),
      '#default_value' => $config->get('popup_position'),
    );

    $languages = \Drupal::languageManager()->getLanguages();

    foreach ($languages as $language) {

      $form[$language->getId()] = array(
        '#type' => 'details',
        '#title' => $language->getName(),
      );

      $form[$language->getId() . "_popup_title"] = array(
        '#group' => $language->getId(),
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $config->get($language->getId() . "_popup_title"),
      );

      $form[$language->getId() . "_popup_info"] = array(
        '#group' => $language->getId(),
        '#type' => 'textarea',
        '#title' => $this->t('Info'),
        '#default_value' => $config->get($language->getId() . "_popup_info"),
      );

      $form[$language->getId() . "_popup_agreed"] = array(
        '#group' => $language->getId(),
        '#type' => 'textfield',
        '#title' => $this->t('Agreed value'),
        '#default_value' => $config->get($language->getId() . "_popup_agreed"),
      );

      $form[$language->getId() . "_popup_link"] = array(
        '#group' => $language->getId(),
        '#type' => 'textfield',
        '#title' => $this->t('Link'),
        '#default_value' => $config->get($language->getId() . "_popup_link"),
      );

      $form[$language->getId() . "_popup_p_private"] = array(
        '#group' => $language->getId(),
        '#type' => 'textarea',
        '#title' => $this->t('Politycy private'),
        '#default_value' => $config->get($language->getId() . "_popup_p_private"),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $languages = \Drupal::languageManager()->getLanguages();

    $config = \Drupal::service('config.factory')
      ->getEditable('cookiec.settings');
    $config->set('popup_enabled', $form_state->getValue('popup_enabled'))
      ->save();
    $config->set('popup_agreed_enabled', $form_state->getValue('popup_agreed_enabled'))
      ->save();
    $config->set('popup_hide_agreed', $form_state->getValue('popup_hide_agreed'))
      ->save();
    $config->set('popup_width', $form_state->getValue('popup_width'))->save();
    $config->set('popup_height', $form_state->getValue('popup_height'))->save();
    $config->set('popup_delay', $form_state->getValue('popup_delay'))->save();
    $config->set('popup_position', $form_state->getValue('popup_position'))
      ->save();

    foreach ($languages as $language) {
      $config = \Drupal::service('config.factory')
        ->getEditable('cookiec.settings');
      $config->set($language->getId() . "_popup_title", $form_state->getValue($language->getId() . "_popup_title"))->save();
      $config->set($language->getId() . "_popup_info", $form_state->getValue($language->getId() . "_popup_info"))->save();
      $config->set($language->getId() . "_popup_agreed", $form_state->getValue($language->getId() . "_popup_agreed"))->save();
      $config->set($language->getId() . "_popup_link", $form_state->getValue($language->getId() . "_popup_link"))->save();
      $config->set($language->getId() . "_popup_p_private", $form_state->getValue($language->getId() . "_popup_p_private"))->save();
    }
    parent::submitForm($form, $form_state);
  }
}
