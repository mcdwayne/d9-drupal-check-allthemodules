<?php

namespace Drupal\simplemeta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for SimpleMeta edit forms.
 *
 * @ingroup simplemeta
 */
class SimplemetaEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\simplemeta\Entity\SimplemetaEntity */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $data = ($entity->isNew()) ? [] : $entity->get('data')->get(0)->getValue();

    $form['data'] = array(
      '#tree' => TRUE,
    );
    // Meta title.
    $form['data']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#maxlength' => 255,
      '#default_value' => (isset($data['title'])) ? $data['title'] : '',
    );
    // Meta description.
    $form['data']['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#resizable' => FALSE,
      '#default_value' => (isset($data['description'])) ? $data['description'] : '',
    );
    $form['data']['keywords'] = array(
      '#type' => 'textfield',
      '#title' => t('Keywords'),
      '#description' => t('Comma-separated list of keywords.'),
      '#maxlength' => 255,
      '#default_value' => (isset($data['keywords'])) ? $data['keywords'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label SimpleMeta.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label SimpleMeta.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.simplemeta.canonical', ['simplemeta' => $entity->id()]);
  }

}
