<?php

namespace Drupal\simpleads\Form\Groups;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Groups;

/**
 * New advertisement group form.
 */
class Create extends FormBase {

  /**
   * Set page title.
   */
  public function setTitle() {
    return $this->t('Create new Group');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_group_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $groups = new Groups();
    $form['#attached']['library'][] = 'simpleads/admin.assets';
    $form['name'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Group Name'),
      '#required'    => TRUE,
      '#description' => $this->t('This adminstrative name and visible to advertisement editors only.'),
    ];
    $form['description'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Description'),
      '#description' => $this->t('The value of this field only visible to advertisement editors.'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Create'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type'  => 'link',
      '#title' => $this->t('Cancel'),
      '#url'   => Url::fromRoute('simpleads.groups'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $groups = (new Groups())
      ->setGroupName($form_state->getValue('name'))
      ->setDescription($form_state->getValue('description'))
      ->save();
    $form_state->setRedirect('simpleads.groups');
  }

}
