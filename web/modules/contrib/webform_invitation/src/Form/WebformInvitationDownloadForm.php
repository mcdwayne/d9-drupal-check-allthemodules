<?php

namespace Drupal\webform_invitation\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows to download the list of generated codes.
 */
class WebformInvitationDownloadForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_invitation_download_form';
  }

  /**
   * Constructs a new WebformInvitationDownloadForm instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $form['webform'] = [
      '#type' => 'value',
      '#value' => $webform,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $form_state->getValue('webform');
    $webform_id = $webform->id();

    // Get all not used codes from DB table.
    $query = $this->database->select('webform_invitation_codes', 'c')
      ->fields('c');
    $query->condition('webform', $webform_id);
    $query->condition('used', 0);
    $codes = $query->execute();

    // This is the XLS header.
    $xlshead = pack("s*", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
    // This is the XLS footer.
    $xlsfoot = pack("s*", 0x0A, 0x00);

    $data = '';
    $row = 0;
    // Process all codes as rows into file.
    while ($code = $codes->fetchAssoc()) {
      $url = Url::fromRoute('entity.webform.canonical', [
        'webform' => $webform_id,
      ], [
        'query' => [
          'code' => $code['code'],
        ],
        'absolute' => TRUE,
      ])->toString();

      $data .= $this->xlsCell($row, 0, $url);
      $row++;
    }

    // Provide file directly for download.
    $filename = 'webform-invitation-codes-' . $webform_id . '.xls';
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Disposition: attachment;filename=' . $filename);
    header('Content-Transfer-Encoding: binary');
    echo $xlshead . $data . $xlsfoot;
    // This is important.
    exit;
  }

  /**
   * Format single cell.
   */
  private function xlsCell($row, $col, $val) {
    $len = strlen($val);
    return pack("s*", 0x204, 8 + $len, $row, $col, 0x0, $len) . $val;
  }

}
