<?php

namespace Drupal\filemime\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Implements the file MIME apply settings form.
 */
class FileMimeApplyForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filemime_apply_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Apply MIME type mapping to all uploaded files?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to apply the configured MIME type mapping to all previously uploaded files? The MIME type for @count uploaded files will be regenerated.', ['@count' => self::count()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Apply');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('filemime.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    batch_set([
      'operations' => [['\Drupal\filemime\Form\FileMimeApplyForm::process', []]],
      'finished' => '\Drupal\filemime\Form\FileMimeApplyForm::finished',
      'title' => $this->t('Processing File MIME batch'),
      'init_message' => $this->t('File MIME batch is starting.'),
      'progress_message' => $this->t('Please wait...'),
      'error_message' => $this->t('File MIME batch has encountered an error.'),
      'file' => drupal_get_path('module', 'filemime') . '/src/Form/FileMimeApplyForm.php',
    ]);
  }

  /**
   * Returns count of files in file_managed table.
   */
  public static function count() {
    return \Drupal::database()->select('file_managed')->countQuery()->execute()->fetchField();
  }

  /**
   * Batch process callback.
   */
  public static function process(&$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['updated'] = 0;
      $context['sandbox']['count'] = self::count();
      $context['sandbox']['schemes'] = \Drupal::service('stream_wrapper_manager')->getWrappers(StreamWrapperInterface::LOCAL);
    }
    $files = \Drupal::database()
      ->select('file_managed')
      ->fields('file_managed', ['fid', 'filemime', 'uri'])
      ->range($context['results']['processed'], 1)
      ->execute();
    foreach ($files as $file) {
      // Only operate on local stream URIs, which should represent file names.
      $scheme = \Drupal::service('file_system')->uriScheme($file->uri);
      if ($scheme && isset($context['sandbox']['schemes'][$scheme])) {
        $filemime = \Drupal::service('file.mime_type.guesser')->guess($file->uri);
        if ($file->filemime != $filemime) {
          $variables = [
            '%old' => $file->filemime,
            '%new' => $filemime,
            '%url' => $file->uri,
          ];
          // Fully load file object.
          $file = File::load($file->fid);
          $file->filemime = $filemime;
          $file->save();
          $context['results']['updated']++;
          $context['message'] = t('Updated MIME type from %old to %new for %url.', $variables);
          \Drupal::logger('filemime')->notice('Updated MIME type from %old to %new for %url.', $variables);
        }
      }
      $context['results']['processed']++;
      $context['finished'] = $context['results']['processed'] / $context['sandbox']['count'];
    }
  }

  /**
   * Batch finish callback.
   */
  public static function finished($success, $results, $operations) {
    $variables = ['@processed' => $results['processed'], '@updated' => $results['updated']];
    if ($success) {
      drupal_set_message(t('Processed @processed files and updated @updated files.', $variables));
    }
    else {
      drupal_set_message(t('An error occurred after processing @processed files and updating @updated files.', $variables), 'warning');
    }
  }

}
