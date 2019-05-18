<?php

namespace Drupal\job;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for job group forms.
 */
class JobGroupForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $group = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add job group');
    }
    else {
      $form['#title'] = $this->t('Edit %label job group', ['%label' => $group->label()]);
    }

    $form['label'] = [
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $group->label(),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $group->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\job\Entity\JobGroup', 'load'],
        'source' => ['label'],
      ],
      '#description' => t('A unique machine-readable name for this job group. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %job-add page, in which underscores will be converted into hyphens.', [
        '%job-add' => t('Add job'),
      ]),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $group->getDescription(),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $group = $this->entity;
    $group->set('id', trim($group->id()));
    $group->set('label', trim($group->label()));

    $status = $group->save();

    $t_args = ['%name' => $group->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The job group %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The job group %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $group->link($this->t('View'), 'collection')]);
      $this->logger('job')->notice('Added job group %name.', $context);
    }
  }

}
