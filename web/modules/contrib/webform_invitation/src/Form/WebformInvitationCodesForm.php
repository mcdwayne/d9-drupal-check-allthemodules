<?php

namespace Drupal\webform_invitation\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides list of all invitation codes for the current webform.
 */
class WebformInvitationCodesForm extends FormBase {

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
    return 'webform_invitation_codes_form';
  }

  /**
   * Constructs a new WebformInvitationCodesForm instance.
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
    $webform_id = $webform->id();

    // Get all codes from DB table.
    $query = $this->database->select('webform_invitation_codes', 'c')
      ->fields('c');
    $query->condition('webform', $webform_id);
    $codes = $query->execute()->fetchAll();

    $rows = [];
    // Create table if there are some codes.
    foreach ($codes as $row) {
      $rows[] = [
        'code' => $row->code,
        'used' => empty($row->used) ? $this->t('no') : $this->t('yes'),
        'sid' => empty($row->sid) ? '' : Link::createFromRoute($row->sid, 'entity.webform_submission.canonical', [
          'webform' => $webform_id,
          'webform_submission' => $row->sid,
        ]),
      ];
    }

    $form['webform_invitation'] = [
      '#type' => 'details',
      '#title' => $this->t('Webform Invitation'),
      '#open' => TRUE,
    ];
    $form['webform_invitation']['codes'] = [
      '#type' => 'table',
      '#header' => [
        'code' => $this->t('Code'),
        'used' => $this->t('Used'),
        'sid' => $this->t('Submission ID'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No codes present, yet. Click on "Generate" above to create codes.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
