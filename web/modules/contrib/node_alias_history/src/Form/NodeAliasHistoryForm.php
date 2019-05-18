<?php

namespace Drupal\node_alias_history\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements the Node Alias History form controller.
 */
class NodeAliasHistoryForm extends FormBase {

  /**
   * Build the Form.
   *
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $get_nid = \Drupal::request()->query->get('nid');
    $get_alias = \Drupal::request()->query->get('alias');
    $url = Url::fromRoute('node_alias_history.form');
    $link = Drupal::l($this->t('Reset'), $url);
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search Alias'),
    ];
    $form['fieldset']['nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nid'),
      '#description' => $this->t('Please enter nid'),
      '#default_value' => isset($get_nid) ? $get_nid : '',
      '#element_validate' => ['element_validate_integer_positive'],
    ];
    $form['fieldset']['alias'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alias'),
      '#description' => $this->t('Please enter Alias'),
      '#default_value' => isset($get_alias) ? $get_alias : '',
    ];
    $form['fieldset']['display_button'] = [
      '#prefix' => '<div class="submit">',
      '#suffix' => $link . '</div">',
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    $node_alias_history_rows = node_alias_history_rows();
    $form['markup'] = [
      '#markup' => Drupal::service('renderer')->render($node_alias_history_rows),
    ];
    return $form;
  }

  /**
   * Get the form_id.
   *
   * @inheritDoc
   */
  public function getFormId() {
    return 'node_alias_history_form';
  }

  /**
   * Add submit handler.
   *
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = $form_state->getValue('nid');
    $alias = $form_state->getValue('alias');
    $form_state->setRedirect(
      'node_alias_history.form',
      [
        'nid' => $nid,
        'alias' => $alias,
      ]
    );
  }

}
