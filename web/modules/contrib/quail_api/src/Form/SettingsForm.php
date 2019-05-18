<?php

namespace Drupal\quail_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures quail_api settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quail_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'quail_api.settings',
    ];
  }

 /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('quail_api.settings');

    $filter_format_options = [NULL => '<Fallback>'];
    $formats = filter_formats();
    foreach ($formats as $format_name => $format_interface) {
      $filter_format_options[$format_name] = $format_interface->label();
    }
    unset($formats);

    $form['filter_format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Filter Format'),
      '#description' => $this->t('Select a text format filter to use when presenting quail api validation results. This is recommended to be a filter format in which HTML is not converted into plain text.'),
      '#default_value' => $config->get('filter_format'),
      '#options' => $filter_format_options,
    );

    $title_block_options = [
      'h1' => 'Heading 1',
      'h2' => 'Heading 2',
      'h3' => 'Heading 3',
      'h4' => 'Heading 4',
      'h5' => 'Heading 5',
      'h6' => 'Heading 6',
      'div' => 'Divider',
      'span' => 'Spanner',
      'p' => 'Paragraph',
    ];

    $form['title_block'] = array(
      '#type' => 'select',
      '#title' => $this->t('Title Block'),
      '#description' => $this->t('Specify an HTML tag structure to use for the title block in the validation results. This may default to Heading 3.'),
      '#default_value' => $config->get('title_block'),
      '#options' => $title_block_options,
    );

    if (empty($form['title_block']['#default_value'])) {
      $form['title_block']['#default_value'] = 'h3';
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('quail_api.settings')
      ->set('filter_format', $values['filter_format'])
      ->set('title_block', $values['title_block'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
