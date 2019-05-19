<?php

namespace Drupal\html_head_meta_and_link\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HtmlHeadMetaAndLinkForm.
 */
class HtmlHeadMetaAndLinkForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'html_head_meta_and_link.htmlheadmetaandlink',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'html_head_meta_and_link_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('html_head_meta_and_link.htmlheadmetaandlink');
    $form['hhml_metas'] = [
      '#type' => 'details',
      '#title' => $this->t('Meta tags'),
      '#description' => $this->t('Select all the meta tags you want to remove:'),
      '#open' => TRUE,
    ];
    $form['hhml_metas']['hhml_metas_generator'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generator'),
      '#default_value' => $config->get('hhml_metas_generator'),
    ];
    $form['hhml_metas']['hhml_metas_mobile_optimized'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mobile Optimized'),
      '#default_value' => $config->get('hhml_metas_mobile_optimized'),
    ];
    $form['hhml_metas']['hhml_metas_handheld_friendly'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Handheld Friendly'),
      '#default_value' => $config->get('hhml_metas_handheld_friendly'),
    ];
    $form['hhml_links'] = [
      '#type' => 'details',
      '#title' => $this->t('Link tags'),
      '#description' => $this->t('Select all the link tags you want to remove:'),
      '#open' => TRUE,
    ];
    $form['hhml_links']['hhml_links_shortlink'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Shortlink'),
      '#default_value' => $config->get('hhml_links_shortlink'),
    ];
    $form['hhml_links']['hhml_links_delete_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete form'),
      '#default_value' => $config->get('hhml_links_delete_form'),
    ];
    $form['hhml_links']['hhml_links_edit_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Edit form'),
      '#default_value' => $config->get('hhml_links_edit_form'),
    ];
    $form['hhml_links']['hhml_links_version_history'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Version history'),
      '#default_value' => $config->get('hhml_links_version_history'),
    ];
    $form['hhml_links']['hhml_links_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revision'),
      '#default_value' => $config->get('hhml_links_revision'),
    ];
    $form['hhml_links']['hhml_links_replicate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replicate'),
      '#default_value' => $config->get('hhml_links_replicate'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('html_head_meta_and_link.htmlheadmetaandlink')
      ->set('hhml_metas', $form_state->getValue('hhml_metas'))
      ->set('hhml_metas_generator', $form_state->getValue('hhml_metas_generator'))
      ->set('hhml_metas_mobile_optimized', $form_state->getValue('hhml_metas_mobile_optimized'))
      ->set('hhml_metas_handheld_friendly', $form_state->getValue('hhml_metas_handheld_friendly'))
      ->set('hhml_links', $form_state->getValue('hhml_links'))
      ->set('hhml_links_shortlink', $form_state->getValue('hhml_links_shortlink'))
      ->set('hhml_links_delete_form', $form_state->getValue('hhml_links_delete_form'))
      ->set('hhml_links_edit_form', $form_state->getValue('hhml_links_edit_form'))
      ->set('hhml_links_version_history', $form_state->getValue('hhml_links_version_history'))
      ->set('hhml_links_revision', $form_state->getValue('hhml_links_revision'))
      ->set('hhml_links_replicate', $form_state->getValue('hhml_links_replicate'))
      ->save();
  }

}
