<?php
namespace Drupal\mail_safety\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class DashboardForm.
 *
 * @package Drupal\mail_safety\Form
 */
class DashboardForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'mail_safety_dashboard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $table_structure = array();
    // Create the headers.
    $table_structure['header'] = array(
      array('data' => t('Subject')),
      array('data' => t('Date sent'), 'field' => 'sent', 'sort' => 'desc'),
      array('data' => t('To')),
      array('data' => t('CC')),
      array('data' => t('Module')),
      array('data' => t('Key')),
      array('data' => t('Details')),
      array('data' => t('Send to original')),
      array('data' => t('Send to default mail')),
      array('data' => t('Delete')),
    );

    $connection = \Drupal::database();
    // Create the query.
    $query = $connection->select('mail_safety_dashboard', 'msd')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(50)
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($table_structure['header'])
      ->fields('msd', array(
        'mail_id',
        'sent',
        'mail',
      ));

    $results = $query->execute();

    // Fill the rows for the table.
    $table_structure['rows'] = [];

    foreach ($results as $row) {
      $mail = unserialize($row->mail);

      // Build the links for the row.
      $view_url = Url::fromRoute('mail_safety.view', array('mail_safety' => $row->mail_id));
      $details_url = Url::fromRoute('mail_safety.details', array('mail_safety' => $row->mail_id));
      $send_original_url = Url::fromRoute('mail_safety.send_original', array('mail_safety' => $row->mail_id));
      $send_default_url = Url::fromRoute('mail_safety.send_default', array('mail_safety' => $row->mail_id));
      $delete_url = Url::fromRoute('mail_safety.delete', array('mail_safety' => $row->mail_id));

      $table_structure['rows'][$row->mail_id] = array(
        'data' => array(
          Link::fromTextAndUrl($mail['subject'], $view_url),
          \Drupal::service('date.formatter')->format((int) $row->sent, 'short'),
          $mail['to'],
          (isset($mail['headers']['CC']) ? $mail['headers']['CC'] : t('none')),
          $mail['module'],
          $mail['key'],
          Link::fromTextAndUrl($this->t('Details'), $details_url),
          Link::fromTextAndUrl($this->t('Send to original'), $send_original_url),
          Link::fromTextAndUrl($this->t('Send to default mail'), $send_default_url),
          Link::fromTextAndUrl($this->t('Delete'), $delete_url),
        ),
      );
    }

    // Let other modules change the table structure to add or remove
    // information to be shown. E.g. attachments that need to be downloaded.
    \Drupal::moduleHandler()->alter('mail_safety_table_structure', $table_structure);

    $form['mails']['table'] = array(
      '#theme' => 'table',
      '#header' => $table_structure['header'],
      '#rows' => $table_structure['rows'],
      '#caption' => 'Mail Safety Dashboard',
      '#sticky' => TRUE,
      '#empty' => t('No mails found'),
    );

    $form['mails']['pager'] = array(
      '#type' => 'pager',
      '#tags' => array(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
  }

}
