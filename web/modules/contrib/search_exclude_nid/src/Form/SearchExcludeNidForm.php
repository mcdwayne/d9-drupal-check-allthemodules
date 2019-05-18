<?php

namespace Drupal\search_exclude_nid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The form to collect nids to be excluded.
 */
class SearchExcludeNidForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_exclude_nid_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('search_exclude_nid.settings');
    $excluded_nids = $config->get('excluded_nids');

    // Source text field.
    $form['excluded_nids'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Nodes to be excluded'),
      '#default_value' => !empty($excluded_nids) ? implode(',', $excluded_nids) : '',
      '#description' => $this->t('Comma separated list of node ids (node:nid) to be excluded, eg: 1,4,8,23 etc.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('search_exclude_nid.settings');

    $excluded_nids = array();

    if (!empty($form_state->getValue('excluded_nids'))) {
      $excluded_nids_arr = explode(',', $form_state->getValue('excluded_nids'));
      foreach ($excluded_nids_arr as $excluded_nid) {
        $excluded_nid = intval($excluded_nid);
        $node = node_load($excluded_nid);

        // Check if node exists for given nid and avoid duplicates.
        if ($excluded_nid && !in_array($excluded_nid, $excluded_nids) && !empty($node)) {
          $excluded_nids[] = $excluded_nid;
        }
        else {
          drupal_set_message(t('nid: %nid has been removed from exclusion list as no node exists with that id or it is a duplicate.', array('%nid' => $excluded_nid)), 'warning');
        }
      }
    }

    $config->set('excluded_nids', $excluded_nids);
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'search_exclude_nid.settings',
    ];
  }

}
