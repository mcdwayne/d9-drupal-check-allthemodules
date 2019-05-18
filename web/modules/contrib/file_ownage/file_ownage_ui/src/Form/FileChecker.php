<?php

namespace Drupal\file_ownage_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file_ownage\FindManager;
use Drupal\file_ownage\FindManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controller routines for bulk file checking and repairing.
 */
class FileChecker extends FormBase {

  /**
   * Update manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $findManager;

  /**
   * Starts with a handle on the file checking utility.
   *
   * @param \Drupal\file_ownage\FindManagerInterface $find_manager
   */
  public function __construct(FindManagerInterface $find_manager) {
    $this->findManager = $find_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_ownage.find_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_manager_update_form';
  }

  /**
   * Returns a page about the status of managed files.
   *
   * @return array
   *   A build array reporting on the filesystem.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $summary = $this->findManager->summarizeManagedFiles();
    $table = [
      '#type' => 'table',
      '#header' => ['Type', 'Count'],
    ];
    foreach ($summary as $key => $val) {
      $table['#rows'][$key] = $val;
    }

    $form['help'] = [
      '#markup' => t("
      This utility can scan ALL managed files registered in 
      /admin/content/files
      and check that they exist in their expected places.
      Missing files will have the file_ownage processing rules
      /admin/config/media/file-ownage
      run on them to see if we can fetch or repair them.
      "),
    ];
    $form['table'] = $table;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Verify all files'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Queue a verify task for every registered file.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $files */
    $files = \Drupal::entityQuery('file')->condition('status', 1)->sort('fid', 'DESC')->execute();
    $operations = [];
    foreach ($files as $file_id) {
      $operations[] = ['Drupal\file_ownage_ui\Form\FileChecker::repairFileIdBatch', [$file_id]];
    }

    // For testing
    // $operations = array_slice($operations, 0, 1000);.
    $strings['@count'] = count($operations);

    $batch = [
      'operations' => $operations,
      'finished' => 'Drupal\file_ownage_ui\Form\FileChecker::repairFileIdBatchFinishedCallback',
      'title' => t('Checking @count managed files status', $strings),
      'progress_message' => t('Trying to check registered file status ...'),
      'error_message' => t('Error checking file status.'),
    ];
    batch_set($batch);

  }

  /**
   * A callback suitable for use as a batch operation.
   */
  public static function repairFileIdBatch($file_id, &$context) {
    // Although often a FormBase would have $this->logger available,
    // That's no help when using a static method, like batch API requires today.
    if (empty($context['results']['strings'])) {
      $context['results']['strings'] = ['@ok' => 0, '@repaired' => 0, '@failed' => 0];
    }
    $finder = \Drupal::service('file_ownage.find_manager');

    $uri = $finder->uriFromId($file_id);
    $strings['@uri'] = $uri;
    $strings['@file_id'] = $file_id;

    $actual_status = $finder->pathStatus($uri);
    if (!$actual_status) {
      $success = $finder->repair($uri);
      if ($success) {
        $context['message'] = 'File repaired OK.';
        $context['results']['strings']['@repaired']++;
        \Drupal::logger('file_ownage')->notice('File repaired OK. File: @file_id @uri', $strings);
      }
      else {
        $context['message'] = 'File repair failed.';
        $context['results']['strings']['@failed']++;
        \Drupal::logger('file_ownage')->error('File repair failed. File: @file_id @uri', $strings);
        $context['results']['failures'][$file_id] = $uri;
      }
    }
    else {
      \Drupal::logger('file_ownage')->info('File checked OK. File: @file_id @uri', $strings);
      $context['message'] = 'File checked OK.';
      $context['results']['strings']['@ok']++;
    }
  }

  /**
   *
   */
  public static function repairFileIdBatchFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = t('Results: @ok files were OK, @repaired files were repaired, @failed failures (see logs)', $results['strings']);
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
