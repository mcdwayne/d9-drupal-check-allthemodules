<?php

/**
 * @file
 * Contains \Drupal\userqueue\Form\UserQueueDeletConfirmForm.
 */
namespace Drupal\userqueue\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before deleting userqueue.
 */
class UserQueueDeletConfirmForm extends ConfirmFormBase {

  /**
   * The UQID of the item to delete.
   *
   * @var string
   */
  protected $uqid;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new UserQueueDeletConfirmForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
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
    return 'userqueue_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $queue = userqueue_load($this->uqid);
    return $this->t('Are you sure you want to delete the user queue "%title" ?', array('%title' => $queue['title']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('userqueue.admin_userqueue.list');
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uqid = NULL) {
    $this->uqid = $uqid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queue = userqueue_load($this->uqid);
    $this->connection->delete('userqueue')->condition('uqid', $this->uqid, '=')->execute();
    drupal_set_message($this->t('User Queue %title deleted.', array('%title' => $queue['title'])));
    $this->logger('userqueue')->notice('Deleted user queue %title.', array('%title' => $queue['title']));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
