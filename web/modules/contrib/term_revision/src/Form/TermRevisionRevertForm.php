<?php

namespace Drupal\term_revision\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Defines a confirmation form to confirm reverting to a term revision by id.
 */
class TermRevisionRevertForm extends ConfirmFormBase {

  protected $id;
  protected $entityId;
  protected $database;
  protected $loggerFactory;
  protected $time;
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $loggerFactory, Time $time, EntityTypeManager $entityTypeManager) {
    $this->database = $database;
    $this->loggerFactory = $loggerFactory;
    $this->time = $time;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
            $container->get('database'), $container->get('logger.factory'), $container->get('datetime.time'), $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "term_revision_revert_form";
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
      // Query to restore data of a previous revision.
      $result = $this->database->select('term_revision', 'tr')
        ->fields('tr', ['revision_data'])
        ->condition('tr.trid', intval($this->id))
        ->condition('tr.entity_id', intval($this->entityId))
        ->execute()
        ->fetchAll();

      if (!empty($result)) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($this->entityId);

        $serialized_data = $result[0]->revision_data;
        // Field's data to be restored.
        $trdata = unserialize($serialized_data);
        // Field's data currently stored in the term.
        $termFields = $term->getFields();

        // Check for fields that donot exist in stored revision.
        foreach ($termFields as $key => $value) {
          if (!array_key_exists($key, $trdata)) {
            $term->set($key, [NULL]);
          }
        }

        // Restoring Field's values.
        foreach ($trdata as $key => $value) {
          if ($term->hasField($key)) {
            $term->set($key, $value->getValue());
          }
        }
        $changed = $this->time->getCurrentTime();
        // Set Term's Changed Time to Current Time.
        $term->setChangedTime($changed);
        $term->save();

        $this->loggerFactory->get('term_revision')->info('Term reverted tid %tid revision_id %trid', ['%tid' => $this->entityId, '%trid' => $this->id]);
        drupal_set_message($this->t('This term has been reverted'));
      }
      else {
        drupal_set_message($this->t('Error! Revision Id does not exist for given Term Id'), 'error');
      }
    }
    // Redirect to Revision Page of the term.
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
    return $this->t('Do you want to revert to this revision?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('You can always revert to current revision.');
  }

}
