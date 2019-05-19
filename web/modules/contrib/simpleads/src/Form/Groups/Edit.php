<?php

namespace Drupal\simpleads\Form\Groups;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Groups;

/**
 * Edit advertisement group form.
 */
class Edit extends FormBase {

  /**
   * Set page title.
   */
  public function setTitle($id = NULL) {
    $group = (new Groups())->setId($id)->load();
    return $this->t('Edit <em>@name</em> group', ['@name' => $group->getGroupName()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $group = (new Groups())->setId($id)->load();
    $form['#attached']['library'][] = 'simpleads/admin.assets';
    $form['id'] = [
      '#type'  => 'hidden',
      '#value' => $id,
    ];
    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Group Name'),
      '#required'      => TRUE,
      '#description'   => $this->t('This adminstrative name and visible to advertisement editors only.'),
      '#default_value' => $group->getGroupName(),
    ];
    $form['description'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Description'),
      '#description'   => $this->t('The value of this field only visible to advertisement editors.'),
      '#default_value' => $group->getDescription(),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Update'),
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
    $group = (new Groups())->setId($form_state->getValue('id'))->load();
    (new Groups())
      ->setId($form_state->getValue('id'))
      ->setName($form_state->getValue('name'))
      ->setDescription($form_state->getValue('description'))
      ->setCreatedAt($group->getCreatedAt())
      ->save();
    $form_state->setRedirect('simpleads.groups');
  }

}
