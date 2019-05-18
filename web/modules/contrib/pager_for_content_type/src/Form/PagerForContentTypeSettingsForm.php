<?php

namespace Drupal\pager_for_content_type\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\node\Entity\NodeType;

/**
 * Configure regional settings for this site.
 */
class PagerForContentTypeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pager_for_content_type_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pager_for_content_type.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pager_for_content_type.settings');

    $node_types = NodeType::loadMultiple();

    $more_links_options = array(
      '0' => 'Off',
      '4' => 4,
      '5' => 6,
      '10' => 10,
    );

    $form['pager_for_content_type_general'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('General options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['pager_for_content_type_general']['pager_for_content_type_previous_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('"Previous" text'),
      '#default_value' => $config->get('pager_for_content_type_previous_text'),
      '#size' => 30,
      '#maxlength' => 64,
      '#required' => TRUE,
    );

    $form['pager_for_content_type_general']['pager_for_content_type_next_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('"Next" text'),
      '#default_value' => $config->get('pager_for_content_type_next_text'),
      '#size' => 30,
      '#maxlength' => 64,
      '#required' => TRUE,
    );

    $form['pager_for_content_type_content_type'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Content type options'),
      '#description' => $this->t('Pager will available on checked content types (only in full view mode)'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    foreach ($node_types as $node_type) {

      $form['pager_for_content_type_content_type'][$node_type->get("type")] = array(
        '#type' => 'fieldset',
        '#title' => $node_type->get("name"),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );

      $key = $node_type->get("type") . '_pager_for_content_type_on';
      $form['pager_for_content_type_content_type'][$node_type->get("type")][$key] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('On'),
        '#default_value' => $config->get($key),
      );

      $key = $node_type->get("type") . '_pager_for_content_type_author';
      $form['pager_for_content_type_content_type'][$node_type->get("type")][$key] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Pager by node author'),
        '#default_value' => $config->get($key),
      );

      $key = $node_type->get("type") . '_pager_for_content_type_more_links';
      $form['pager_for_content_type_content_type'][$node_type->get("type")][$key] = array(
        '#type' => 'select',
        '#title' => $this->t('Show more nodes titles after the pager'),
        '#description' => t('First half before pager, second half after pager'),
        '#default_value' => $config->get($key),
        '#options' => $more_links_options,
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_types = NodeType::loadMultiple();

    $config = $this->config('pager_for_content_type.settings');
    $config->set('pager_for_content_type_previous_text', $form_state->getValue('pager_for_content_type_previous_text'));
    $config->set('pager_for_content_type_next_text', $form_state->getValue('pager_for_content_type_next_text'));

    foreach ($node_types as $node_type) {

      $key = $node_type->get("type") . '_pager_for_content_type_on';
      $config->set($key, $form_state->getValue($key));

      $key = $node_type->get("type") . '_pager_for_content_type_author';
      $config->set($key, $form_state->getValue($key));

      $key = $node_type->get("type") . '_pager_for_content_type_more_links';
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
