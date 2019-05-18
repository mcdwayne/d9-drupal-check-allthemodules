<?php

namespace Drupal\pagarme_marketplace\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SplitDeleteForm.
 *
 * @package Drupal\pagarme_marketplace\Form
 */
class SplitDeleteForm extends FormBase {

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * Drupal Routing Match.
   *
   * @var Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route_match;

  public function __construct(Connection $database, CurrentRouteMatch $route_match) {
    $this->database = $database;
    $this->route_match = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'split_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $split_id = $this->route_match->getParameter('split_id');
    $form['split_id'] = array(
      '#type' => 'hidden',
      '#value' => $split_id,
    );

    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
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
    $split_id = $form_state->getValue('split_id');
    $this->database->delete('pagarme_splits')
      ->condition('split_id', $split_id)
      ->execute();
    $this->database->delete('pagarme_split_rules')
      ->condition('split_id', $split_id)
      ->execute();
    drupal_set_message(t('Split rule deleted.'));
  }

  /**
   * Submit callback for cancel.
   */
  public function cancelSubmit(array $form, FormStateInterface $form_state) {
    return;
  }
}
