<?php

namespace Drupal\simply_signups\Form\Config;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a signup form.
 */
class SimplySignupsTemplatesConfigForm extends FormBase {

  protected $database;
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database_connection, DateFormatter $date_formatter) {
    $this->database = $database_connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_templates_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simply_signups.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $db = $this->database;
    $query = $db->select('simply_signups_templates', 'p');
    $query->fields('p');
    $results = $query->execute()->fetchAll();
    $header = [
      'title' => $this->t('Title'),
      'status' => $this->t('Default'),
      'updated' => $this->t('Updated'),
      'created' => $this->t('Created'),
      'operations' => $this->t('Operations'),
    ];
    $output = [];
    foreach ($results as $result) {
      $links['view'] = [
        'title' => $this->t('manage fields'),
        'url' => Url::fromRoute('simply_signups.templates.fields', ['tid' => $result->id]),
      ];
      $links['edit'] = [
        'title' => $this->t('edit template'),
        'url' => Url::fromRoute('simply_signups.templates.edit', ['tid' => $result->id]),
      ];
      $links['remove'] = [
        'title' => $this->t('remove template'),
        'url' => Url::fromRoute('simply_signups.templates.remove', ['tid' => $result->id]),
      ];
      $status = ($result->status == 1) ? 'Yes' : 'No';
      $output[($result->id)] = [
        'title' => $this->t('@title', ['@title' => $result->title]),
        'status' => $this->t('@status', ['@status' => $status]),
        'updated' => $this->dateFormatter->format($result->updated, 'custom', 'm/d/Y - h:i a'),
        'created' => $this->dateFormatter->format($result->created, 'custom', 'm/d/Y - h:i a'),
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => $links,
          ],
        ],
      ];
    }
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-template-list-form', 'simply-signups-form'],
    ];
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => $this->t('No templates found.'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add template'),
      '#submit' => ['::addTemplate'],
      '#validate' => ['::validateTemplate'],
      '#attributes' => [
        'class' => [
          'button--primary',
          'btn-primary',
        ],
      ],
    ];
    $form['actions']['remove'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove selected'),
      '#attributes' => [
        'class' => [
          'button--danger',
          'btn-link',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $rows = $values['table'];
    $selected = array_filter($rows);
    if (empty($selected)) {
      $form_state->setErrorByName('table', $this->t('Must select at least one item to remove.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, array $items = NULL) {
    $values = $form_state->getValues();
    $rows = $values['table'];
    $rows = array_filter($rows);
    foreach ($rows as $row) {
      $item = $row;
      $db = $this->database;
      $db->delete('simply_signups_templates')
        ->condition('id', $item, '=')
        ->execute();
      $db->delete('simply_signups_templates_fields')
        ->condition('tid', $item, '=')
        ->execute();
    }
    drupal_set_message($this->t('Successfully removed selected template(s).'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateTemplate(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function addTemplate(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('simply_signups.templates.add');
  }

}
