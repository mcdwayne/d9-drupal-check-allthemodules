<?php

namespace Drupal\filebrowser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filebrowser\Entity\FilebrowserMetadataEntity;

class DescriptionForm extends FormBase {

  /**
   * @var array
   * Array of ids of the entities to be edited
   */
  protected $e_ids;

  /**
   * @var array
   * Array of entities to be edited.
   */
  protected $entities;

  protected $nid;
  protected $queryFid;
  /**
   * @var \Drupal\filebrowser\Services\Common
   */
  protected $common;

  /**
   * @var \Drupal\filebrowser\Services\FilebrowserStorage
   */
  protected $storage;

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'filebrowser_form_edit_file_description';
  }

  /**
   * @param int $nid
   * @param int $query_fid
   * @param array $fids
   * @param $form
   * @param $form_state
   * @param $ajax
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = null, $query_fid = null, $fids = null, $ajax = null) {
    $fid_array = explode(',', $fids);
    $this->nid = $nid;
    $this->queryFid = $query_fid;
    $this->common = \Drupal::service('filebrowser.common');

    // we need to load the fileData to retrieve the filename
    $this->storage = \Drupal::service('filebrowser.storage');
    $file_data = $this->storage->nodeContentLoadMultiple($fid_array);

    // Load the description-metadata
    $ids = \Drupal::entityQuery('filebrowser_metadata_entity')
      ->condition('fid', $fid_array, "IN")
      ->condition('name', 'description')
      ->execute();
    $this->entities = \Drupal::entityTypeManager()->getStorage('filebrowser_metadata_entity')->loadMultiple($ids);
    $descriptions = [];
    foreach ($this->entities as $entity) {
      $descriptions[$entity->fid->value] = unserialize($entity->content->value)['title'];
    }

    // if this form is opened by ajax add a close link.
    if ($ajax) {
      $form['#attributes'] = [
        'class' => [
          'form-in-slide-down'
        ],
      ];
      $form['close'] = $this->common->closeButtonMarkup();
    }
    $form['items'] = [
      '#title' => $this->t('Edit description'),
      '#type' => 'fieldset',
      '#tree' => true,
    ];

    foreach ($descriptions as $key => $description) {
      $properties = unserialize($file_data[$key]['file_data']);
      //debug($properties);
      $form['items'][$key] = [
        '#type' => 'textarea',
        '#title' => $properties->filename,
        '#rows' => 3,
        '#default_value' => $description,
      ];
    }

    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var FilebrowserMetadataEntity $entity */
    $values = $form_state->getValue('items');
    foreach($this->entities as $entity) {
      $content = unserialize($entity->content->value);
      $fid = $entity->fid->value;
      $content['title'] = $values[$fid];
      $entity->setContent(serialize($content));
      $entity->save();
    }

    if ($this->nid) {
      $route = $this->common->redirectRoute($this->queryFid, $this->nid);
      $form_state->setRedirect($route['name'], $route['node'], $route['query']);
    }
  }

}