<?php

namespace Drupal\auto_close_comments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure example settings for this site.
 */
class AutoCloseCommentsSettingsForm extends ConfigFormBase {
  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManager $entity_manager) {
    parent::__construct($config_factory);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_close_comments_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'auto_close_comments.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get config.
    $config = $this->config('auto_close_comments.settings');
    $contentTypes = $this->entityManager->getStorage('node_type')->loadMultiple();
    // Perpare content type list.
    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }

    $form['close_comment'] = [
      '#type' => 'submit',
      '#value' => t('Close Comments Now'),
      '#submit' => ['::closeComments'],
    ];

    $form['description'] = [
      '#markup' => '<p>' . t('This will close all the comments of selected period.') . '</p>',
    ];

    // Content type.
    $form['auto_close_comments_content_type'] = [
      '#type' => 'checkboxes',
      '#options' => $contentTypesList,
      '#title' => $this->t('Select content type on which you need to close the comment?'),
      '#default_value' => $config->get('auto_close_comments_content_type'),
    ];
    // Time period.
    $form['auto_close_comments_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Select time for which you need to close comment?'),
      '#options' => [
        '-7 days' => $this->t('One week'),
        '-14 days' => $this->t('Two week'),
        '-21 days' => $this->t('Three week'),
        '-30 days' => $this->t('Month'),
        '-90 days' => $this->t('Three Month'),
        '-365 days' => $this->t('Year'),
      ],
      '#default_value' => $config->get('auto_close_comments_time'),
    ];
    // Number of item need to proccess on cron run.
    $form['auto_close_comments_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Select number of comment process on cron run?'),
      '#default_value' => $config->get('auto_close_comments_items') ? $config->get('auto_close_comments_items') : 0,
      '#description' => $this->t('Enter 0 to process all at onces'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function closeComments(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('auto_close_comments.settings');
    // Selected content type.
    $content_type = array_values(array_filter($config->get('auto_close_comments_content_type')));
    // Time interval to close comments.
    $interval = $config->get('auto_close_comments_time') ? $config->get('auto_close_comments_time') : '-7 days';
    // Check conetnt type.
    if (empty($content_type)) {
      drupal_set_message(t('Please select atleast one content type.'), 'status', FALSE);
      return;
    }
    // Get all nodes with open comments and with the time range.
    $query = \Drupal::database()->select('node', 'n');
    $query->join('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->join('node__comment', 'nc', 'n.nid = nc.entity_id');
    $query->fields('n', ['nid', 'type']);
    $query->fields('nfd', ['created']);
    $query->condition('nfd.status', '1', '=');
    $query->condition('nc.comment_status', '1', '!=');
    $query->condition('n.type', $content_type, 'IN');
    $query->condition('nfd.created', strtotime($interval), '<=');

    $z_results = $query->execute()->fetchAll();
    $nids = [];
    // Close comments for specify nodes.
    foreach ($z_results as $result) {
      $nids[] = $result->nid;
    }

    $batch = [
      'title' => t('Closing comments...'),
      'operations' => [
        [
          '\Drupal\auto_close_comments\BulkCloseComments::closeComments',
          [$nids],
        ],
      ],
      'finished' => '\Drupal\auto_close_comments\BulkCloseComments::closeCommentsFinishedCallback',
    ];
    batch_set($batch);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('auto_close_comments.settings')
    // Set the submitted configuration setting.
      ->set('auto_close_comments_content_type', $form_state->getValue('auto_close_comments_content_type'))
      ->set('auto_close_comments_time', $form_state->getValue('auto_close_comments_time'))
      ->set('auto_close_comments_items', $form_state->getValue('auto_close_comments_items'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
