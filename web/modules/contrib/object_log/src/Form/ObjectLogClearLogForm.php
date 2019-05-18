<?php

/**
 * @file
 * Contains ObjectLogClearLogForm.
 */

namespace Drupal\object_log\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the form that clears the Object Log table.
 */
class ObjectLogClearLogForm extends FormBase {

  /**
   * The database connection service.
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * @param Connection $database
   *   The database connection service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'object_log_clear_log_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['clear'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clear log'),
    );
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->database->delete('object_log')->execute();
    drupal_set_message($this->t('Object log cleared.'));
  }

}
