<?php

namespace Drupal\keyvalue_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KeyvalueUiEditForm extends FormBase {

  /**
   * @var
   */
  protected $collection;

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'keyvalue_ui_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $collection = NULL, $name = NULL) {
    $form['header'] = [
      '#type' => 'inline_template',
      '#template' => '<h3>Collection: {{ collection }}</h3><h4>Name: {{ name }}</h4>',
      '#context' => ['collection' => $collection, 'name' => $name],
    ];
    $value = \Drupal::keyValue($collection)->get($name);
    $form['value'] = [
      '#type' => is_bool($value) ? 'checkbox' : 'textfield',
      '#title' => t('Value'),
      '#default_value' => $value,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('keyvalue_ui.details', ['collection' => $collection]),
    ];
    $this->collection = $collection;
    $this->name = $name;
    $this->type = gettype($value);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $value = $formState->getValue('value');
    settype($value, $this->type);
    \Drupal::keyValue($this->collection)->set($this->name, $value);
    $this->messenger()->addMessage(t('Saved collection @collection name @name value @value', ['@collection' => $this->collection, '@name' => $this->name, $value]));
    $formState->setRedirect('keyvalue_ui.details', ['collection' => $this->collection]);
  }

}
