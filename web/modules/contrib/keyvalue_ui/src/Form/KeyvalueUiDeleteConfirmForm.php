<?php

namespace Drupal\keyvalue_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KeyvalueUiDeleteConfirmForm extends ConfirmFormBase {

  /**
   * @var string
   */
  protected $collection;

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $build = [
      '#type' => 'inline_template',
      '#template' => '<h3>Collection: {{ collection }}</h3><h4>Name: {{ name }}</h4><h5>Value: {{ value }}</h5>Are you sure you want to delete it?',
      '#context' => get_object_vars($this),
    ];
    return \Drupal::service('renderer')->renderPlain($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('keyvalue_ui.details', ['collection' => $this->collection]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'keyvalue_ui_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $collection = NULL, $name = NULL, $value = NULL) {
    $this->collection = $collection;
    $this->name = $name;
    $this->value = $value;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    \Drupal::keyValue($this->collection)->delete($this->name);
    $this->messenger()->addMessage($this->t('Deleted @collection @collection name @name, value @value', [
      '@collection' => $this->collection,
      '@name' => $this->name,
      '@value' => $this->value,
    ]));
    $formState->setRedirect('keyvalue_ui.details', ['collection' => $this->collection]);
  }

}
