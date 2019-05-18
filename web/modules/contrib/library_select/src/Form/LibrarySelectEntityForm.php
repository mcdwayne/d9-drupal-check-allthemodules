<?php

namespace Drupal\library_select\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LibrarySelectEntityForm.
 */
class LibrarySelectEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\library_select\Entity\LibrarySelectEntity $entity */
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for the Library Select."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\library_select\Entity\LibrarySelectEntity::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['files'] = [
      '#type' => 'details',
      '#title' => $this->t('Files'),
      '#open' => $entity->isNew() || !empty($entity->css_files) || !empty($entity->js_files),
    ];

    $form['css_files'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CSS Files'),
      '#description' => $this->t('The list path of css files.'),
      '#rows' => 2,
      '#default_value' => $entity->css_files,
      '#required' => FALSE,
      '#group' => 'files',
    ];

    $form['js_files'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JS Files'),
      '#description' => $this->t('The list path of js files.'),
      '#rows' => 2,
      '#default_value' => $entity->js_files,
      '#required' => FALSE,
      '#group' => 'files',
    ];

    $form['code'] = [
      '#type' => 'details',
      '#title' => $this->t('Code'),
      '#open' => $entity->isNew() || !empty($entity->css_code) || !empty($entity->js_code),
    ];

    $form['css_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CSS Code'),
      '#description' => $this->t('Input custom CSS Code.'),
      '#rows' => 5,
      '#default_value' => $entity->css_code,
      '#required' => FALSE,
      '#group' => 'code',
    ];

    $form['js_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JS Code'),
      '#description' => $this->t('Input custom JS Code.'),
      '#rows' => 5,
      '#default_value' => $entity->js_code,
      '#required' => FALSE,
      '#group' => 'code',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Library Select.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Library Select.', [
          '%label' => $entity->label(),
        ]));
    }
    // Clear library cache.
    /** @var \Drupal\Core\Asset\LibraryDiscoveryCollector $libraryCollector */
    $libraryCollector = \Drupal::service('library.discovery.collector');
    $libraryCollector->clear();
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
