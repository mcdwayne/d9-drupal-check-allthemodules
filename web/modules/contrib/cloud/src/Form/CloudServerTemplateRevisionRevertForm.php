<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\cloud\Entity\CloudServerTemplateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a Cloud Server Template revision.
 *
 * @ingroup cloud_server_template
 */
class CloudServerTemplateRevisionRevertForm extends ConfirmFormBase {


  /**
   * The Cloud Server Template revision.
   *
   * @var \Drupal\cloud\Entity\CloudServerTemplateInterface
   */
  protected $revision;

  /**
   * The Cloud Server Template storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $CloudServerTemplateStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a new CloudServerTemplateRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The Cloud Server Template storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(EntityStorageInterface $entity_storage,
                              DateFormatterInterface $date_formatter,
                              Messenger $messenger) {
    $this->CloudServerTemplateStorage = $entity_storage;
    $this->dateFormatter = $date_formatter;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('cloud_server_template'),
      $container->get('date.formatter'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloud_server_template_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.cloud_server_template.version_history', [
      'cloud_context' => $this->revision->getCloudContext(),
      'cloud_server_template' => $this->revision->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_server_template_revision = NULL) {
    $this->revision = $this->CloudServerTemplateStorage->loadRevision($cloud_server_template_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->save();

    $this->logger('content')->notice('Cloud Server Template: reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger->addMessage(t('Cloud Server Template %title has been reverted to the revision from %revision-date.', ['%title' => $this->revision->label(), '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)]));
    $form_state->setRedirect(
      'entity.cloud_server_template.version_history', [
        'cloud_context' => $this->revision->getCloudContext(),
        'cloud_server_template' => $this->revision->id(),
      ]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\cloud\Entity\CloudServerTemplateInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\cloud\Entity\CloudServerTemplateInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(CloudServerTemplateInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime(REQUEST_TIME);

    return $revision;
  }

}
