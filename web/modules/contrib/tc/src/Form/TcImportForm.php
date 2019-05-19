<?php

namespace Drupal\tc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Import TC data from CSV for a user.
 */
class TcImportForm extends FormBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  public $routeMatch;

  /**
   * Constructs a TcImportForm object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $routeMatch) {
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var RouteMatchInterface $routeMatch */
    $routeMatch = $container->get('current_route_match');
    return new static(
      $routeMatch
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tc_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = [
      '#type' => 'file',
      '#title' => $this->t('CSV file'),
      '#description' => $this->t('Select the CSV file with the format Thingspeak uses for exports. Maximum file size: !size.', ['!size' => format_size(file_upload_max_size())]),
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'import' => [
        '#type' => 'submit',
        '#value' => $this->t('Import'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file = file_save_upload('import', [
      'file_validate_extensions' => ['csv'],
    ], FALSE, 0);
    if ($file) {
      // Move the file into the Drupal file system.
      // @TODO: Move to a unique, temporary filename instead.
      if ($file = file_move($file, 'temporary://tc-imports')) {
        // Save the file for use in the submit handler.
        $storage = &$form_state->getStorage();
        $storage['file'] = $file;
      }
      else {
        $form_state->setErrorByName('import', $this->t("Failed to write the uploaded file to the site's temporary folder."));
      }
    }
    else {
      $form_state->setErrorByName('import', $this->t('No file was uploaded.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = &$form_state->getStorage();
    $file = $storage['file'];
    // We are done with the file, remove it from storage.
    unset($storage['file']);
    $uid = $this->routeMatch->getParameter('user');
    $batch = [
      'operations' => [
        ['tc_import_form_batch_op_progress', [$uid, $file]],
        // As the finished callback cannot get a parameter, we have to delete
        // the file in an operation.
        ['tc_import_form_batch_op_finish', [$file]],
      ],
    ];
    batch_set($batch);
  }
}
