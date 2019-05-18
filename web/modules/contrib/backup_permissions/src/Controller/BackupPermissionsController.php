<?php

namespace Drupal\backup_permissions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\backup_permissions\BackupPermissionsStorageTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Backup permissions routes.
 */
class BackupPermissionsController extends ControllerBase {

  use BackupPermissionsStorageTrait;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a BackupPermissionsController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   */
  public function __construct(FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a form to restore roles.
   *
   * @param int $bid
   *   The backup id.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function restoreRoles($bid) {
    if (is_numeric($bid)) {
      $backup = $this->load(array('id' => $bid));
      if (!empty($backup)) {
        $data = $backup[0]->backup;
        $form = $this->formBuilder
          ->getForm('Drupal\backup_permissions\Form\BackupPermissionsImportForm', $data);
        return $form;
      }
    }

    // We will just show a standard "access denied" page in this case.
    throw new AccessDeniedHttpException();
  }

  /**
   * Provides CSV format of backup.
   *
   * @param int $bid
   *   The backup id.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function downloadCsv($bid) {
    if (is_numeric($bid)) {
      $backup = $this->load(array('id' => $bid));
      if (!empty($backup)) {
        $data = unserialize($backup[0]->backup);
        $rows = $data['permissions'];
        $flat = $this->assocToCsvFormat($rows);
        return $this->arrayToCsv($flat, "backup_permissions.csv");
      }
    }

    // We will just show a standard "access denied" page in this case.
    throw new AccessDeniedHttpException();
  }

  /**
   * Returns associative array of permission data.
   *
   * @param array $data
   *   Array of permissions and roles.
   *
   * @return array
   *   Associative array of permissions and roles.
   */
  public function assocToCsvFormat(array $data) {
    $labels = array_keys($data[0]);
    $data_out = array($labels);
    foreach ($data as $assoc_row) {
      $data_out[] = array_values($assoc_row);
    }
    return $data_out;
  }

  /**
   * Print CSV.
   *
   * @param array $data
   *   Array of  roles and there respective permissions.
   * @param string $filename
   *   Name of the CSV File to be generated.
   * @param string $delimiter
   *   CSV delimiter.
   */
  public function arrayToCsv(array $data, $filename = "export.csv", $delimiter = ",") {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    // Open the "output" stream
    // See http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen('php://output', 'w');

    foreach ($data as $line) {
      fputcsv($f, $line, $delimiter);
    }
    fclose($f);
    die;
  }

}
