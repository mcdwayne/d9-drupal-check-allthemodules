<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\config_entity_revisions\ConfigEntityRevisionsControllerInterface;

/**
 * Provides a form for reverting a config entity revision.
 */
abstract class ConfigEntityRevisionsDeleteFormBase extends ConfirmFormBase Implements ContainerInjectionInterface {

  /**
   * The config entity.
   *
   * @var ConfigEntityRevisionsInterface;
   */
  protected $config_entity;

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $connection;

  /**
   * The Date Formatter service.
   *
   * @var DateFormatter
   */
  protected $dateFormatter;

  /**
   * The revision to be deleted.
   *
   * @var ContentEntityInterface
   */
  protected $revision;

  /**
   * The content entity storage.
   *
   * @var ContentEntityStorageInterface
   */
  protected $configEntityRevisionsStorage;

  /**
   * The controller.
   *
   * @var ConfigEntityRevisionsControllerInterface
   */
  protected $controller;

  /**
   * Constructs a new ConfigEntityRevisionsRevisionDeleteForm.
   *
   * @param EntityStorageInterface $storage
   *   The ConfigEntityRevisions storage.
   * @param Connection $connection
   *   The database connection.
   * @param DateFormatter $dateFormatter
   *   The date formatter service.
   * @param ConfigEntityRevisionsControllerInterface
   *   The controller interface.
   */
  public function __construct(EntityStorageInterface $storage, Connection $connection, $dateFormatter, ConfigEntityRevisionsControllerInterface $controller) {
    $this->connection = $connection;
    $this->dateFormatter = $dateFormatter;

    $match = \Drupal::service('router')->matchRequest(\Drupal::request());
    $this->config_entity = $match['config_entity']->revisioned_entity();

    $this->configEntityRevisionsStorage = $storage;
    $this->revision = $storage->loadRevision($match['revision_id']);

    $this->controller = $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->config_entity->module_name() . '_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url("entity." . $this->config_entity->getEntityTypeId() . ".revisions", [
      $this->config_entity->getEntityTypeId() => $this->config_entity->id(),
    ]);
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
  public function getDescription() {
    if ($this->config_entity->has_own_content()) {
      $count = $this->config_entity->contentEntityCount($this->revision->revision->value);
      if ($count) {
        return \Drupal::service('string_translation')
          ->formatPlural($count, '1 submission will be deleted.', '@count submissions will be deleted.');
      }
    }

    return '';
  }

  /**
   * Redirect the user back to the revisions overview page.
   *
   * @param FormStateInterface $form_state
   *   The form state to be modified.
   */
  public function setRevisionsFormRedirect(FormStateInterface &$form_state) {
    $form_state->setRedirect(
      'entity.' . $this->config_entity->config_entity_name() . '.revisions',
      [$this->config_entity->config_entity_name() => $this->config_entity->id()]
    );
  }

  /**
   * Delete the current revision.
   */
  public function performDeletion() {
    $this->controller->deleteRevision($this->revision);
  }

  /**
   * Log a revision deletion.
   */
  public function logUpdate() {
    $this->logger('content')
      ->notice('Deleted %label revision %revision.', [
        '%label' => $this->config_entity->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]);
  }

  /**
   * Display a message to the user.
   */
  public function displayUpdate() {
    drupal_set_message(t('Revision from %revision-date of %form has been deleted.', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%form' => $this->config_entity->label(),
    ]));
  }

  /**
   * Redirect the user back to the config entity build page.
   *
   * @param FormStateInterface $form_state
   *   The form state to be modified.
   */
  public function setBuildFormRedirect(FormStateInterface &$form_state) {
    $form_state->setRedirect(
      'entity.' . $this->config_entity->config_entity_name() . '.edit_form',
      [$this->config_entity->config_entity_name() => $this->config_entity->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Someone else may have deleted a revision so we can't assume the state
    // when the form was rendered is still valid.
    $revisions = $this->controller->getRevisionIds($this->config_entity->getContentEntityID());
    if (count($revisions) == 1) {
      drupal_set_message(t('There is only one revision remaining. You may not delete it, sorry.'), 'error');
    }
    else {
      if ($this->config_entity->has_own_content()) {
        $this->config_entity->deleteRelatedContentEntities($this->revision->revision->value);
      }
      $this->controller->deleteRevision($this->revision);
      $this->logUpdate();
      $this->displayUpdate();
    }

    if (count($revisions) > 2) {
      $this->setRevisionsFormRedirect($form_state);
    }
    else {
      $this->setBuildFormRedirect($form_state);
    }
  }

}
