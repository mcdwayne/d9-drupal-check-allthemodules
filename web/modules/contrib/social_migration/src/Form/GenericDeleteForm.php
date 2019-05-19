<?php

namespace Drupal\social_migration\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GenericDeleteForm.
 */
class GenericDeleteForm extends ConfirmFormBase {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The migration to confirm.
   *
   * @var \Drupal\migrate_plus\Entity\Migration
   */
  protected $migration;

  /**
   * The URL to return to after the delete operation is complete/canceled.
   *
   * @var \Drupal\Core\Url
   */
  protected $returnUrl;

  /**
   * Constructs a new GenericDeleteForm object.
   */
  public function __construct(
    QueryFactory $query_factory,
    EntityTypeManager $entity_type_manager
  ) {
    $this->entityQueryFactory = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %migration migration?', [
      '%migration' => $this->migration->id(),
    ]);
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
  public function getCancelUrl() {
    return $this->returnUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'soc_mig_admin_generic_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Migration $migration = NULL) {
    $this->migration = $migration;
    $group = $migration->migration_group;
    if (preg_match('/^social_migration_(.*)_feeds_group$/', $group, $matches) === 1) {
      $this->returnUrl = Url::fromRoute('social_migration.' . $matches[1] . '.list');
    }
    else {
      $this->returnUrl = Url::fromRoute('social_migration.main');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migrationId = $this->migration->id();
    $this->migration->delete();
    $form_state->setRedirectUrl($this->returnUrl);
    drupal_set_message($this->t('Successfully removed the %id migration.', [
      '%id' => $migrationId,
    ]));
  }

}
