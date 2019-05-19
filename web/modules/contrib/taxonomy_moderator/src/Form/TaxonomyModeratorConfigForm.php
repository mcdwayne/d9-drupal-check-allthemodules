<?php

namespace Drupal\taxonomy_moderator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TaxonomyModeratorConfigForm.
 */
class TaxonomyModeratorConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'taxonomy_moderator.taxonomymoderatorconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_moderator_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('taxonomy_moderator.taxonomymoderatorconfig');
    $settings = $this->config('taxonomy_moderator.taxonomymoderatorconfig')->get();
    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $form['taxomonymoderator_roles'] = [
      '#type' => 'radios',
      '#title' => t('Roles'),
      '#options' => $roles,
      '#access' => TRUE,
      '#default_value' => $settings['taxomonymoderator_roles'],
      '#description' => $this->t('Select a User Role to approve suggested taxonomy terms.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('taxonomy_moderator.taxonomymoderatorconfig')
      ->set('taxomonymoderator_roles', $form_state->getvalue('taxomonymoderator_roles'))
      ->save();
  }

}
