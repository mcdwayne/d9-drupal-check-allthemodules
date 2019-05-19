<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\Task;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformExporterManagerInterface;
use Drupal\webform\WebformSubmissionExporterInterface;
use Drupal\webform_scheduled_tasks\Exception\HaltScheduledTaskException;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\TaskPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A task which emails an export of a list of webforms.
 *
 * @WebformScheduledTask(
 *   id = "export_email_results",
 *   label = @Translation("Export and email results"),
 * )
 */
class EmailedExport extends TaskPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The submission exporter.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $exporter;

  /**
   * The exporter manager.
   *
   * @var \Drupal\webform\Plugin\WebformExporterManagerInterface
   */
  protected $exporterManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * A storage type of sending an email.
   */
  const STORAGE_TYPE_EMAIL = 'email';

  /**
   * A storage type of writing to the file system.
   */
  const STORAGE_TYPE_FILESYSTEM = 'filesystem';

  /**
   * A destination directory for saved files.
   */
  const DESTINATION_DIRECTORY = 'private://scheduled-exports';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WebformSubmissionExporterInterface $exporter, WebformExporterManagerInterface $exporterManager, MailManagerInterface $mailManager, FileUsageInterface $fileUsage) {
    $this->exporter = $exporter;
    $this->exporterManager = $exporterManager;
    $this->mailManager = $mailManager;
    $this->fileUsage = $fileUsage;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('webform_submission.exporter'),
      $container->get('plugin.manager.webform.exporter'),
      $container->get('plugin.manager.mail'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $exporter_options = $this->exporterManager->getOptions();
    return [
      'exporter_settings' => [],
      'exporter' => key($exporter_options),
      'email_addresses' => '',
      'storage_type' => static::STORAGE_TYPE_FILESYSTEM,
      'delete_submissions' => FALSE,
      'include_attachments' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['email_addresses'] = [
      '#title' => $this->t('Email addresses'),
      '#required' => TRUE,
      '#description' => $this->t('Enter a list of email addresses to notify when the export is complete. The list should be coma separated values.'),
      '#type' => 'textarea',
      '#attributes' => [
        'placeholder' => 'foo@example.com, bar@example.com',
      ],
      '#default_value' => $this->configuration['email_addresses'],
    ];
    $form['storage_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Storage type'),
      '#options' => [
        static::STORAGE_TYPE_FILESYSTEM => $this->t('Save to private filesystem'),
        static::STORAGE_TYPE_EMAIL => $this->t('Send as email attachment'),
      ],
      '#default_value' => $this->configuration['storage_type'],
      '#required' => TRUE,
      '#description' => $this->t('Select how the resulting file will be delivered to the configured users. Saving the file to the file system will generate a private file which only privileged roles will have access to.'),
    ];
    $form['delete_submissions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete submissions after export'),
      '#default_value' => $this->configuration['delete_submissions'],
      '#description' => $this->t('Delete submissions after this task has been run.'),
    ];
    $form['include_attachments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include attachments'),
      '#default_value' => $this->configuration['include_attachments'],
      '#description' => $this->t('Include attachments uploaded by users in the exported archive.'),
    ];

    $this->buildExportPluginForm($form, $form_state);
    return $form;
  }

  /**
   * Build the export plugin form.
   */
  protected function buildExportPluginForm(&$form, FormStateInterface $form_state) {
    $form['exporter'] = [
      '#title' => $this->t('Export format'),
      '#type' => 'select',
      '#options' => $this->exporterManager->getOptions(),
      '#default_value' => $this->configuration['exporter'],
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => 'exporter-settings',
      ],
    ];
    $chosen_exporter = $form_state->getValue('exporter', $this->configuration['exporter']);

    $plugin = $this->exporterManager->createInstance($chosen_exporter, $this->getConfiguration()['exporter_settings']);
    $form['exporter_settings'] = [
      '#prefix' => '<div id="exporter-settings">',
      '#suffix' => '</div>',
    ];
    $subform_state = SubformState::createForSubform($form['exporter_settings'], $form, $form_state);
    $form['exporter_settings'] += $plugin->buildConfigurationForm($form['exporter_settings'], $subform_state);
  }

  /**
   * An AJAX callback for the exporter_settings container.
   */
  public static function ajaxCallback($form, FormStateInterface $form_state) {
    return $form['task_settings']['exporter_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function executeTask(\Iterator $submissions) {
    $exporter = $this->initializeExporter();

    $exporter->writeHeader();
    $processed_submission_ids = [];
    foreach ($submissions as $submission) {
      // @todo, ask that ::writeRecords accepts an iterator.
      $exporter->writeRecords([$submission]);
      $processed_submission_ids[] = $submission->id();
    }
    $exporter->writeFooter();
    // This is required to add actual submission data file into the associated
    // archive when files have been included inside the export.
    if ($exporter->isArchive()) {
      $exporter->writeExportToArchive();
    }

    // If no submissions were present while running this scheduled task, there
    // is no need to send an export.
    if (empty($processed_submission_ids)) {
      return;
    }

    // Defensively catch errors in an export early to prevent any data loss,
    // check the file exists and the file has some data in it.
    if (!file_exists($this->getExportFileOrArchivePath($exporter)) || filesize($this->getExportFileOrArchivePath($exporter)) === 0) {
      throw new HaltScheduledTaskException('Export files are failing to generate or are empty.');
    }
    // Archive tar can throw warnings and produce a corrupt tar file without
    // ever throwing an exception, see d.o/project/drupal/issues/3026470.
    // Attempt to be a bit more defensive about this and try to verify a valid
    // archive was produced. With the php internal functions used to build the
    // archiver throwing warnings, a big risk is silent failure. The current
    // verification is a "best guess", given complexities around the number and
    // size of various files which should be in the archive.
    if ($exporter->isArchive() && !$this->verifyArchive($this->getExportFileOrArchivePath($exporter))) {
      throw new HaltScheduledTaskException('An invalid archive file was generated.');
    }

    // @todo, build this option.
    // https://www.drupal.org/project/webform_scheduled_tasks/issues/3031237.
    if ($this->configuration['storage_type'] === static::STORAGE_TYPE_EMAIL) {
      throw new HaltScheduledTaskException('The email storage type has not been implemented yet.');
    }

    // If we have chosen to store the submissions on the file system, copy the
    // generated temp file into the private file system.
    if ($this->configuration['storage_type'] === static::STORAGE_TYPE_FILESYSTEM) {
      $target_directory = static::DESTINATION_DIRECTORY;
      if (!file_prepare_directory($target_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        throw new HaltScheduledTaskException('Could not create a directory for the exported files to be written to.');
      }
      $attempted_file_destination_uri = sprintf('%s/%s', static::DESTINATION_DIRECTORY, $this->getExportFileOrArchiveName($exporter));
      if (!$unique_destination_file_uri = file_unmanaged_copy($this->getExportFileOrArchivePath($exporter), $attempted_file_destination_uri)) {
        throw new HaltScheduledTaskException('Could not write the generated file to the private file system.');
      }

      // Save a file entity with the data so the file can be deleted from the UI
      // if it has been downloaded.
      $file = File::create([
        'uri' => $unique_destination_file_uri,
        'filename' => $this->getExportFileOrArchiveName($exporter),
        'status' => FILE_STATUS_PERMANENT,
      ]);
      $file->save();
      // Register a file usage that tells Drupal the file URI was sent in an
      // email, to prevent the make_unused_managed_files_temporary setting from
      // deleting it. This will ensure the file sticks around until the user
      // explicitly decides to delete the export.
      $this->fileUsage->add($file, 'webform_scheduled_tasks', 'email_uri', $this->getScheduledTask()->id());

      $this->emailRecipients('export_summary_filesystem', [
        'file_url' => file_create_url($unique_destination_file_uri),
        'task_id' => $this->getScheduledTask()->id(),
      ]);
    }

    if ($this->configuration['delete_submissions']) {
      foreach ($processed_submission_ids as $submission_delete_id) {
        WebformSubmission::load($submission_delete_id)->delete();
      }
    }
  }

  /**
   * Verify a tar file.
   *
   * @param string $file
   *   The path to the archive.
   *
   * @return bool
   *   Check if an archive is empty.
   */
  protected function verifyArchive($file) {
    // Reading the contents of the archive is enough to raise an exception in
    // some cases and archives should always contain at least one file.
    $archive = new ArchiveTar($file);
    $content = $archive->listContent();
    return !empty($content);
  }

  /**
   * Get the file or archive path, whichever is appropriate.
   *
   * @parram \Drupal\webform\WebformSubmissionExporterInterface $initializedExporter
   *   The initialized exporter.
   *
   * @return string
   *   A path to an archive or file.
   */
  protected function getExportFileOrArchivePath(WebformSubmissionExporterInterface $initializedExporter) {
    if ($initializedExporter->isArchive()) {
      return $initializedExporter->getArchiveFilePath();
    }
    return $initializedExporter->getExportFilePath();
  }

  /**
   * Get the file or archive name, whichever is appropriate.
   *
   * @parram \Drupal\webform\WebformSubmissionExporterInterface $initializedExporter
   *   The initialized exporter.
   *
   * @return string
   *   The name of a file.
   */
  protected function getExportFileOrArchiveName(WebformSubmissionExporterInterface $initializedExporter) {
    if ($initializedExporter->isArchive()) {
      return $initializedExporter->getArchiveFileName();
    }
    return $initializedExporter->getExportFileName();
  }

  /**
   * Email recipients of this task.
   *
   * @param string $key
   *   The mail key.
   * @param array $params
   *   The mail params.
   */
  protected function emailRecipients($key, array $params) {
    foreach ($this->getEmailAddresses() as $email_address) {
      $this->mailManager->mail('webform_scheduled_tasks', $key, $email_address, LanguageInterface::LANGCODE_DEFAULT, $params);
    }
  }

  /**
   * Get a list of email addresses configured for this plugin.
   *
   * @return string[]
   *   A list of email addresses to send the content to.
   */
  protected function getEmailAddresses() {
    return array_map('trim', explode(',', $this->configuration['email_addresses']));
  }

  /**
   * Get an exporter with initialized settings.
   *
   * @return \Drupal\webform\WebformSubmissionExporterInterface
   *   The submissions exporter.
   */
  protected function initializeExporter() {
    // Set the scheduled task as the source entity so temporary files generated
    // will be with the context of the scheduled task. This ensures if someone
    // is generating an export from the UI, it does not use the same temporary
    // filenames.
    $this->exporter->setWebform($this->getScheduledTask()->getWebform());
    $this->exporter->setSourceEntity($this->getScheduledTask());
    $exporter_options = $this->configuration['exporter_settings'] + [
      'exporter' => $this->configuration['exporter'],
      'files' => $this->configuration['include_attachments'],
    ];
    $this->exporter->setExporter($exporter_options);
    return $this->exporter;
  }

}
