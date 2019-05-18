<?php

namespace Drupal\help_text\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HelpTextSettingsForm.
 */
class HelpTextSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'help_text_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['help_text.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('help_text.settings');
    $form['font_awesome_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Awesome Icon'),
      '#description' => $this->t("Icon to use for toggling help text display."),
      '#default_value' => $config->get('font_awesome_icon'),
    ];

    $form['icon_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon Size'),
      '#options' =>[' ' => t('Small'),'fa-lg' => t('Medium'),'fa-2x'=> t('Large'), 'fa-3x'=> t('X-Large'), 'fa-4x'=> t('XX-Large'), 'fa-5x'=> t('XXX-Large')],
      '#description' => $this->t("Font Awesome size to use for the icon."),
      '#default_value' => $config->get('icon_size'),
    ];

    $form['icon_title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Title Text'),
      '#description' => $this->t("Title text for Font Awesome Icon."),
      '#default_value' => $config->get('icon_title_text'),
    ];

    $form['icon_alt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Alt Text'),
      '#description' => $this->t("Alt text for Font Awesome Icon."),
      '#default_value' => $config->get('icon_alt_text'),
    ];

    $form['node_form_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only Apply Help Text to Node Forms'),
      '#description' => $this->t("Apply Help Text toggling only to node forms."),
      '#default_value' => $config->get('node_form_only'),
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $config = $this->config('help_text.settings');
    // Load submitted values
    $form_icon= $form_state->getValue('font_awesome_icon');
    $form_icon_size= $form_state->getValue('icon_size');
    $form_icon_title = $form_state->getValue('icon_title_text');
    $form_icon_alt = $form_state->getValue('icon_alt_text');
    $form_node_only = $form_state->getValue('node_form_only');

    //set config to submitted values
    $config->set('font_awesome_icon', $form_icon)->save();
    $config->set('icon_size', $form_icon_size)->save();
    $config->set('icon_title_text', $form_icon_title)->save();
    $config->set('icon_alt_text', $form_icon_alt)->save();
    $config->set('node_form_only', $form_node_only)->save();   
    

    parent::submitForm($form, $form_state);
  }

}

