<?php

namespace Drupal\disable_link_rel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ImportContactForm.
 *
 * @package Drupal\module_import_contacts\Form
 */
class SettingDisableLinkRel extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['disable_link_rel.import'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'disable_link_rel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('disable_link_rel.import');

    $form['enable'] = [
      '#title' => $this->t('Remove rel link to head'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable') ? $config->get('enable') : NULL,
    ];

    $form['links'] = [
      '#title' => $this->t('Enter the values for the attribute to delete'),
      '#type' => 'textfield',
      '#default_value' => $config->get('links') ? $config->get('links') : '',
      '#description' => $this->t('Enter the attributes separated by commas. Example: <i>canonical, shortlink, delete-form</i>'),
    ];

    $form['remove_link_attr'] = [
      '#title' => $this->t('Remove attributes from links'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('remove_link_attr') ? $config->get('remove_link_attr') : NULL,
    ];

    $form['remove_link_attr_list'] = [
      '#title' => $this->t('Enter attributes to delete'),
      '#type' => 'textfield',
      '#default_value' => $config->get('remove_link_attr_list') ? $config->get('remove_link_attr_list') : '',
      '#description' => $this->t('Enter the attributes separated by commas. Example: <i>data-drupal-link-system-path, system-path</i>'),
    ];
    $form['cach_clear'] = [
      '#title' => $this->t('Clear cache when saving settings'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('disable_link_rel.import');
    $config->set('enable', $form_state->getValue('enable'));
    $config->set('links', $form_state->getValue('links'));
    $config->set('remove_link_attr', $form_state->getValue('remove_link_attr'));
    $config->set('remove_link_attr_list', $form_state->getValue('remove_link_attr_list'));
    $config->save();
    if ($form_state->getValue('cach_clear', FALSE)) {
      drupal_flush_all_caches();
    }
  }

}
