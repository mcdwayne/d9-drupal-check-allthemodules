<?php

namespace Drupal\social_link_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the SocialLinkFieldSettings form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SocialLinkFieldSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('social_link_field.settings');
    $form['attached_fa'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Attach external FontAwesome library'),
      '#default_value' => $config->get('attached_fa'),
      '#description' => $this->t('If you attached FontAwesome in your theme, please switch off this checkbox.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_link_field_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_link_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this
      ->configFactory->getEditable('social_link_field.settings')
      ->set('attached_fa', $form_state->getValue('attached_fa'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
