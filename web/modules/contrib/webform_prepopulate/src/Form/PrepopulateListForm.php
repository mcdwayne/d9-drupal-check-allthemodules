<?php

namespace Drupal\webform_prepopulate\Form;

use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform_prepopulate\WebformPrepopulateStorage;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form that lists prepopulate data for a Webform.
 */
class PrepopulateListForm extends FormBase {

  /**
   * Drupal\webform_prepopulate\WebformPrepopulateStorage definition.
   *
   * @var \Drupal\webform_prepopulate\WebformPrepopulateStorage
   */
  protected $webformPrepopulateStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current Webform entity.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  private $webform;

  /**
   * Constructs a new WebformPrepopulateController object.
   *
   * @param \Drupal\webform_prepopulate\WebformPrepopulateStorage $webform_prepopulate_storage
   *   The Webform prepopulate storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date Formatter service.
   */
  public function __construct(WebformPrepopulateStorage $webform_prepopulate_storage, DateFormatterInterface $date_formatter) {
    $this->webformPrepopulateStorage = $webform_prepopulate_storage;
    $this->dateFormatter = $date_formatter;
    $this->webform = \Drupal::requestStack()->getCurrentRequest()->attributes->get('webform');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_prepopulate.storage'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_prepopulate_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $search = $this->getRequest()->get('search');
    $form['#attributes'] = ['class' => ['search-form']];

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filter hash'),
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['basic']['filter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search hash containing'),
      '#default_value' => $search,
      '#maxlength' => 64,
      '#size' => 25,
    ];
    $form['basic']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#action' => 'filter',
    ];
    if ($search) {
      $form['basic']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#action' => 'reset',
      ];
    }

    $header = [
      ['data' => $this->t('Hash'), 'field' => 'hash'],
      ['data' => $this->t('Data'), 'field' => 'prepopulate_data'],
      ['data' => $this->t('Imported'), 'field' => 'timestamp', 'sort' => 'desc'],
      ['data' => $this->t('Operations')],
    ];

    $rows = [];
    $results = $this->webformPrepopulateStorage->listData($this->webform->id(), $header, $search, 25);
    foreach ($results as $result) {
      $row = [];
      $row['hash'] = $result->hash;
      $row['prepopulate_data'] = implode(', ', unserialize($result->data));
      $row['timestamp'] = $this->dateFormatter->format($result->timestamp, 'short');

      $operations = [];
      $operations['view'] = [
        'title' => $this->t('Prepopulate'),
        'url' => Url::fromRoute('entity.webform.canonical', ['webform' => $this->webform->id()], ['query' => ['hash' => $result->hash]]),
      ];
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];

      $rows[] = $row;
    }

    $form['prepopulate_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->getEmptyMessage($search),
    ];
    $form['prepopulate_pager'] = ['#type' => 'pager'];
    return $form;
  }

  /**
   * Empty list message depending based on the active search.
   *
   * @param string $search
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  private function getEmptyMessage($search) {
    $result = '';
    if (!empty($search)) {
      $result = $this->t('There are no prepopulate data matching this hash.');
    }
    else {
      $uploadUrl = Url::fromRoute('entity.webform.settings_form', ['webform' => $this->webform->id()], ['fragment' => 'prepopulate']);
      $uploadLink = Link::fromTextAndUrl($this->t('Upload a file'), $uploadUrl)->toRenderable();
      $result = $this->t('There are no prepopulate data yet. @link.', [
        '@link' => \Drupal::service('renderer')->renderRoot($uploadLink),
      ]);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#action'] == 'filter') {
      $search = trim($form_state->getValue('filter'));
      \Drupal::messenger()->addMessage($this->t('Searching for <em>@search</em>', [
        '@search' => $search,
      ]));
      $form_state->setRedirect('webform_prepopulate.prepopulate_list_form', ['webform' => $this->webform->id()], ['query' => ['search' => $search]]);
    }
    else {
      $form_state->setRedirect('webform_prepopulate.prepopulate_list_form', ['webform' => $this->webform->id()]);
    }
  }

}
