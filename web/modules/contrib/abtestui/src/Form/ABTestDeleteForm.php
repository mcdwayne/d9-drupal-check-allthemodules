<?php

namespace Drupal\abtestui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class ABTestDeleteForm.
 *
 * @package Drupal\abtestui\Form
 */
class ABTestDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abtestui_test_delete';
  }

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * The loaded test.
   *
   * @var array
   */
  protected $test;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete the "%name" test (ID %id)?', [
      '%id' => $this->id,
      '%name' => $this->test['name'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('abtestui.test_edit_form', [
      'ab_test_id' => $this->id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param string|int|null $ab_test_id
   *   The test ID.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ab_test_id = NULL) {
    $this->id = $ab_test_id;
    /** @var \Drupal\abtestui\Service\TestStorage $testStorage */
    $testStorage = \Drupal::service('abtestui.test_storage');
    $this->test = $testStorage->load($this->id);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\abtestui\Service\TestStorage $testStorage */
    $testStorage = \Drupal::service('abtestui.test_storage');
    $testStorage->delete($this->id);

    drupal_set_message(t('The "@name" test (ID @id) has been deleted.', [
      '@id' => $this->id,
      '@name' => $this->test['name'],
    ]));

    $form_state->setRedirect('abtestui.test_list');
  }

}
