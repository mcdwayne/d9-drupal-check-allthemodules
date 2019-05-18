<?php

namespace Drupal\add_more_alternate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure settings for the add_more_alternate module.
 */
class AddMoreAlternateSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_more_alternate_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['add_more_alternate.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('add_more_alternate.settings');

    $form['add_item_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add item button label'),
      '#description' => $this->t('The label to show on the button that adds more items.'),
      '#default_value' => $config->get('add_item_label'),
    ];

    $form['add_item_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add item button classes'),
      '#description' => $this->t('CSS class(es) to apply to the button that adds more items.'),
      '#default_value' => $config->get('add_item_classes'),
    ];

    $form['remove_item_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remove item button label'),
      '#description' => $this->t('The label to show on the button that removes items.'),
      '#default_value' => $config->get('remove_item_label'),
    ];

    $form['remove_item_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remove item button classes'),
      '#description' => $this->t('CSS class(es) to apply to the button that removes items.'),
      '#default_value' => $config->get('remove_item_classes'),
    ];

    $form['admin_disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Administrator disable'),
      '#description' => $this->t('If enabled, users with the administrator role will see the default widget.'),
      '#default_value' => $config->get('admin_disable', TRUE),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('add_more_alternate.settings')
      ->set('add_item_label', $values['add_item_label'])
      ->set('add_item_classes', $values['add_item_classes'])
      ->set('remove_item_label', $values['remove_item_label'])
      ->set('remove_item_classes', $values['remove_item_classes'])
      ->set('admin_disable', $values['admin_disable'])
      ->save();

    drupal_set_message($this->t('Settings saved.'));
  }

}
