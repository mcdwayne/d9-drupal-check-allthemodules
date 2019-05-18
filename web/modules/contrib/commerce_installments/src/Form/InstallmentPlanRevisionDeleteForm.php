<?php

namespace Drupal\commerce_installments\Form;

use Drupal\commerce_installments\UrlParameterBuilderTrait;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Installment Plan revision.
 *
 * @ingroup commerce_installments
 */
class InstallmentPlanRevisionDeleteForm extends ConfirmFormBase {

  use StringTranslationTrait;
  use UrlParameterBuilderTrait;

  /**
   * The Installment Plan revision.
   *
   * @var \Drupal\commerce_installments\Entity\InstallmentPlanInterface
   */
  protected $revision;

  /**
   * The Installment Plan storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $InstallmentPlanStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new InstallmentPlanRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->InstallmentPlanStorage = $entity_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('installment_plan'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'installment_plan_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.installment_plan.version_history', ['installment_plan' => $this->revision->id()] + $this->getUrlParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $installment_plan_revision = NULL) {
    $this->revision = $installment_plan_revision;
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->InstallmentPlanStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Installment Plan: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    drupal_set_message($this->t('Revision from %revision-date of Installment Plan %title has been deleted.', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.installment_plan.canonical',
       ['installment_plan' => $this->revision->id()] + $this->getUrlParameters()
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {installment_plan_revision} WHERE plan_id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.installment_plan.version_history',
         ['installment_plan' => $this->revision->id()] + $this->getUrlParameters()
      );
    }
  }

}
