<?php

namespace Drupal\inlinemanual;

use Drupal\Core\Form\FormBase;

class InlineManualAdminList extends FormBase {
	public function getFormId() {
    // Unique ID of the form.
    return 'inlinemanual_admin_list';
  }

  /**
   * Menu callback: content administration.
   */
	public function buildForm(array $form, array &$form_state) {
    $form['admin'] = $this->adminTopics();
    return $form;
  }

  /**
   * Form builder: Builds the node administration overview.
   */
  public function adminTopics() {
    // Build the sortable table header.
    $header = array(
      'title' => array('data' => $this->t('Title'), 'field' => 'title', 'sort' => 'asc'),
      'description' => $this->t('Description'),
      'status' => array('data' => $this->t('Status'), 'field' => 'status'),
      'version' => array('data' => $this->t('Updated'), 'field' => 'version'),
      'operations' => $this->t('Operations'),
    );

    $query = db_select('inm_topics', 't')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->extend('Drupal\Core\Database\Query\TableSortExtender');

    $topics = $query
      ->fields('t',array('tid', 'title', 'description', 'status', 'version'))
      ->limit(50)
      ->orderByHeader($header)
      ->execute()
      ->fetchAll();
    
    // Prepare the list of topics.
    $options = array();
    $selection = array();
    foreach ($topics as $topic) {
      $operations = array(
        l($this->t('edit permissions'), 'admin/config/services/inlinemanual/topic/'. $topic->tid .'/permissions'),
        l($this->t('remove'), 'admin/config/services/inlinemanual/topic/'. $topic->tid .'/remove'),
      );
      $options[$topic->tid] = array(
        'title' => check_plain($topic->title),
        'description' => check_plain($topic->description),
        'status' => $topic->status ? $this->t('enabled') : $this->t('disabled'),
        'version' => $topic->version,
        'operations' => implode(' | ', $operations), 
      );
      $selection[$topic->tid] = $topic->status;
    }
    
    // Only use a tableselect when the current user is able to perform any
    // operations.
    $form['topics'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No content available.'),
      '#default_value' => $selection,
    );

    $form['pager'] = array('#theme' => 'pager');
    
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    
    return $form;
  }

  public function submitForm(array &$form, array &$form_state) {
    // Handle submitted form data.
    $topics = $form_state['values']['topics'];
  
    // Enable checked topics 
    $enable = array_filter($topics);
    if (!empty($enable)) {
      inlinemanual_topics_update_status(1, array_keys($enable));
    }
    
    // Disable unchecked topics
    $disable = array_diff_assoc($topics, $enable);
    if (!empty($disable)) {
      inlinemanual_topics_update_status(0, array_keys($disable));
    }
    
    drupal_set_message($this->t('Topic updated.'));
  }
}