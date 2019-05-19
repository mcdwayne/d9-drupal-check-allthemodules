<?php

namespace Drupal\wizenoze\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wizenoze\Helper\WizenozeAPI;
use GuzzleHttp\json_decode;

/**
 * Configure register form settings for this site.
 */
class WizenozeSearchEnginesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wizenoze_admin_search_engines';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

    $wizenoze = WizenozeAPI::getInstance();
    $collections = $wizenoze->collectionList();
    $sharedCollections = $wizenoze->sharedCollectionList();

    $collectionList = [];
    foreach ($collections as $items) {
      $collectionList[$items['id']] = $items['name'];
    }

    $sharedCollectionList = [];
    foreach ($sharedCollections as $items) {
      $sharedCollectionList[$items['id']] = $items['name'];
    }

    $engine = [];
    $collectionSelectList = [];
    if ($id) {
      // Load search engine.
      $engine = $wizenoze->viewSearchEngine($id);
      // Load source.
      foreach ($engine['sources'] as $source) {
        if ($source['sourceType'] == 'Collection') {
          $collectionSelectList[] = $source['sourceId'];
        }
      }
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Search Engine Name'),
      '#required' => TRUE,
      '#default_value' => (!empty($engine['name'])) ? $engine['name'] : '',
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom Search Engine Description'),
      '#default_value' => (!empty($engine['description'])) ? $engine['description'] : '',
    ];

    $form['collections'] = [
      '#type' => 'select',
      '#options' => $collectionList,
      '#title' => $this->t('Own Collections'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#default_value' => $collectionSelectList,
      "#empty_option" => $this->t('- Select -'),
    ];

    $form['shared_collections'] = [
      '#type' => 'select',
      '#options' => $sharedCollectionList,
      '#title' => $this->t('Shared Collections'),
      '#multiple' => TRUE,
      '#default_value' => $collectionSelectList,
      "#empty_option" => $this->t('- Select -'),
    ];

    $form['search_id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
    ];

    $form['status'] = [
      '#type' => 'select',
      '#options' => [1 => 'Active', 0 => 'InActive'],
      '#title' => $this->t('Status'),
      '#required' => TRUE,
      '#default_value' => (isset($engine['active'])) ? (int) $engine['active'] : '1',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => (empty($engine['id'])) ? $this->t('Add New Custom Search Engine') : $this->t('Update Custom Search Engine'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $wizenoze = WizenozeAPI::getInstance();
    $id = $form_state->getValue('search_id');
    if ($id > 0) {
      $result = $wizenoze->updateSearchEngine(
          [
            'id' => $id,
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
            'status' => $form_state->getValue('status'),
          ]
      );
    }
    else {
      $result = $wizenoze->createSearchEngine(
          [
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
            'status' => $form_state->getValue('status'),
          ]
      );
    }
    if (!empty($result)) {
      $engine = json_decode($result, TRUE);
      $collections = array_merge($form_state->getValue('collections'), $form_state->getValue('shared_collections'));
      $wizenoze->updateSearchEngineSources($id, $engine['customSearchEngine']['sources'], $collections);
      $form_state->setRedirect('wizenoze.config.searchengine.list');
    }
    else {
      drupal_set_message($this->t('Unable to add search engine, please try again', 'error'));
    }
  }

}
