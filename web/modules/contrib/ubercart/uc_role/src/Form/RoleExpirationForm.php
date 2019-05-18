<?php

namespace Drupal\uc_role\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Menu callback for viewing expirations.
 */
class RoleExpirationForm extends FormBase {

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date.formatter service.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_role_expiration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create the header for the pager.
    $header = [
      ['data' => $this->t('Username'), 'field' => 'u.name'],
      ['data' => $this->t('Role'), 'field' => 'e.rid'],
      ['data' => $this->t('Expiration date'), 'field' => 'e.expiration', 'sort' => 'asc'],
      ['data' => $this->t('Operations'), 'colspan' => 2],
    ];

    // Grab all the info to build the pager.
    $query = db_select('uc_roles_expirations', 'e')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->fields('e')
      ->limit(50)
      ->orderByHeader($header);

    $query->join('users', 'u', 'e.uid = u.uid');
    $query->fields('u');

    $results = $query->execute();

    // Stick the expirations into the form.
    $rows = [];
    foreach ($results as $result) {
      $account = User::load($result->id());

      // Each row has username, role, expiration, and edit/delete operations.
      $row = [
        'username' => $account->getUsername(),
        'role' => _uc_role_get_name($result->rid),
        'expiration' => $this->dateFormatter->format($result->expiration, 'short'),
      ];

      $ops = [];
      $ops['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.user.edit_form', ['user' => $result->id()], ['fragment' => 'role-expiration-' . $result->rid, 'query' => ['destination' => 'admin/people/expiration']]),
      ];
      $ops['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('uc_role.expiration', ['user' => $result->id(), 'role' => $result->rid]),
      ];
      $row['ops'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $ops,
        ],
      ];

      $rows[] = $row;
    }

    $form['roles_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No expirations set to occur'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
