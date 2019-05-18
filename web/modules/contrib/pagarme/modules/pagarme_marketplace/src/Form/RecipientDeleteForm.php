<?php

namespace Drupal\pagarme_marketplace\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RecipientDeleteForm.
 *
 * @package Drupal\pagarme_marketplace\Form
 */
class RecipientDeleteForm extends FormBase {

  const PAGARME_RECIPIENT_ARCHIVED = 1;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recipient_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $recipient_id = NULL) {

    $form['recipient_id'] = array(
      '#type' => 'hidden',
      '#value' => $recipient_id,
    );

    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Archive'),
    );

    $form['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => ['::cancelSubmit'],
    );

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->database->update('pagarme_recipients')
      ->fields(array('archived' => self::PAGARME_RECIPIENT_ARCHIVED))
      ->condition('recipient_id', $values['recipient_id'])
      ->execute();
    drupal_set_message(t('Recipient filed.'));
  }

  /**
   * Submit callback for cancel.
   */
  public function cancelSubmit(array $form, FormStateInterface $form_state) {
    return;
  }
}
