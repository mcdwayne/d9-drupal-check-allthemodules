<?php


namespace Drupal\node_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DeleteConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_delete_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['node_delete.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_delete.settings');
    $form['node_delete_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable node delete module'),
      '#default_value' => $config->get('enable'),
      '#description' => $this->t('Enable the module for deleting expired node.'),
    );
    $form['delete_unpublished'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Delete/Unpublished'),
      '#default_value' => $config->get('delete_or_unpublished'),
      '#description' => $this->t('If check node will delete after expired. Otherwise node will be unpublished.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('node_delete.settings')
      ->set('enable', $form_state->getValue('node_delete_enable'))
      ->set('delete_or_unpublished', $form_state->getValue('delete_unpublished'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}