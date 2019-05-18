<?php

namespace Drupal\navbar_awesome\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ToolbarSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'navbar_awesome_toolbar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['navbar_awesome.toolbar'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('navbar_awesome.toolbar');

    $form['cdn'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add FrontAwesome library through CDN'),
      '#options' => [
        $this->t('No'),
        $this->t('Yes'),
      ],
      '#default_value' => $config->get('cdn'),
    ];
    $form['roboto'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add Roboto library through Google Fronts CDN'),
      '#options' => [
        $this->t('No'),
        $this->t('Yes'),
      ],
      '#default_value' => $config->get('roboto'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('navbar_awesome.toolbar')
         ->set('cdn', $form_state->getValue('cdn'))
         ->set('roboto', $form_state->getValue('roboto'))
         ->save();

    parent::submitForm($form, $form_state);
  }
}
