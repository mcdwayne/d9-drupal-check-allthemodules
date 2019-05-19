<?php

namespace Drupal\term_revision\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a confirmation form to confirm deletion of term revision by id.
 */
class TermRevisionDeleteForm extends ConfirmFormBase {

  protected $id;
  protected $entityId;
  protected $database;
  protected $loggerFactory;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $loggerFactory) {
    $this->database = $database;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
            $container->get('database'), $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "term_revision_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $taxonomy_term = NULL, $id = NULL) {
    $this->id = $id;
    $this->entityId = $taxonomy_term;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $schema = $this->database->schema();
    if ($schema->tableExists('term_revision')) {
      // Query to delete revision data.
      $result = $this->database->delete('term_revision')
        ->condition('trid', intval($this->id))
        ->condition('entity_id', intval($this->entityId))
        ->execute();
    }

    if ($result > 0) {
      $this->loggerFactory->get('term_revision')->info('Term revision deleted tid %tid revision_id %trid', ['%tid' => $this->entityId, '%trid' => $this->id]);
      drupal_set_message($this->t('Revision has been deleted'));
    }
    else {
      drupal_set_message($this->t('Error! Revision Id does not exist for given Term Id'), 'error');
    }
    // Redirect to Revision page of the term.
    $response = new RedirectResponse(Url::fromRoute('term_revision.all', ['taxonomy_term' => $this->entityId])->toString());
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('term_revision.all', ['taxonomy_term' => $this->entityId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete this revision?');
  }

}
