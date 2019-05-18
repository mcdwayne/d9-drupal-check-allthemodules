<?php

namespace Drupal\file_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;

/**
 * Provides the view, add pages and title callbacks for file entities.
 */
class FileManagementController extends ControllerBase {

  /**
   * Provides the file view page.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to edit.
   *
   * @return array
   *   The file edit form.
   */
  public function viewFilePage(FileInterface $file = NULL) {
    if (empty($file)) {
      // drupal_set_message
      // return to previous page or file overview page (use route)
    }

    $file_information = \Drupal::service('file_management')->getFileInformation($file);

    if (!empty(\Drupal::request()->query->get('destination'))) {
      $file_information['actions']['#type'] = 'actions';
      \Drupal::service('file_management')->addBackButton($file_information['actions'], $this->t('Back'));
    }

    return $file_information;
  }

  /**
   * Provides the title for the file view page.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to edit.
   *
   * @return string
   *   The page title.
   */
  public function viewFilePageTitle(FileInterface $file = NULL) {
    if (empty($file)) {
      return $this->t('View file');
    }

    return $this->t('<em>View file</em> @filename', ['@filename' => $file->label()]);
  }

  /**
   * Provides the file edit page.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to edit.
   *
   * @return array
   *   The file edit form.
   */
  public function addFilePage(FileInterface $file = NULL) {
    $build = [
      'form' => \Drupal::formBuilder()->getForm('Drupal\file_management\Form\FileManagementEditFileForm', $file),
    ];

    return $build;
  }

  /**
   * Provides the file edit page.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to edit.
   *
   * @return array
   *   The file edit form.
   */
  public function deleteFilePage(FileInterface $file = NULL) {
    $build = [
      'form' => $this->formBuilder()->getForm('Drupal\file_management\Form\FileManagementDeleteFileConfirmForm', $file),
    ];

    return $build;
  }

  /**
   * Provides the title for the file edit page.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to edit.
   *
   * @return string
   *   The page title.
   */
  public function addFilePageTitle(FileInterface $file = NULL) {
    if (empty($file)) {
      return $this->t('Edit file');
    }

    return $this->t('<em>Edit file</em> @filename', ['@filename' => $file->label()]);
  }

  /**
   * Provides the title for the file edit page.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to edit.
   *
   * @return string
   *   The page title.
   */
  public function deleteFilePageTitle(FileInterface $file = NULL) {
    if (empty($file)) {
      return $this->t('Delete file');
    }

    return $this->t('<em>Delete file</em> @filename', ['@filename' => $file->label()]);
  }
}
