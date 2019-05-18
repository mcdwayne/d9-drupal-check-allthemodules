<?php

namespace Drupal\multisite_solr_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Server;


/**
 * Class SelectServerForm.
 */
class SelectServerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'multisite_solr_search.selectserver',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'select_server_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('multisite_solr_search.selectserver');
    $server_list = Server::loadMultiple();
    $options = array();
    foreach ($server_list as $server) {
      $options[$server->get('id')] = $server->get('name');
    }
    $form['select_server'] = [
      '#type' => 'select',
      '#title' => $this->t('Select server'),
      '#description' => $this->t('Select the server from which the data to be searched'),
      '#options' => $options,
      '#default_value' => $config->get('select_server'),
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

    $this->config('multisite_solr_search.selectserver')
      ->set('select_server', $form_state->getValue('select_server'))
      ->save();
  }

}
