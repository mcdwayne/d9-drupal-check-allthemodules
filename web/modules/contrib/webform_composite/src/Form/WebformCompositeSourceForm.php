<?php

namespace Drupal\webform_composite\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Composite source editing form.
 */
class WebformCompositeSourceForm extends EntityForm {

  /**
   * List of supported  element properties.
   *
   * @var array
   */
  protected $supportedProperties = [
    'key' => 'key',
    'type' => 'type',
    'title' => 'title',
    'help' => 'help',
    'description' => 'description',
    'options' => 'options',
    'required' => 'required',
  ];

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs an WebformCompositeForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(QueryFactory $entity_query, AccountInterface $current_user, WebformElementManagerInterface $element_manager) {
    $this->entityQuery = $entity_query;
    $this->currentUser = $current_user;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('current_user'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $composite = $this->entity;

    $description = $this->t('Key-value pairs MUST be specified as "safe_key: \'Some readable option\'". Use of only alphanumeric characters and underscores is recommended in keys. One option per line. Option groups can be created by using just the group name followed by indented group options.');
    $description .= ' ' . $this->t("Descriptions, which are only applicable to radios and checkboxes, can be delimited using ' -- '.");

    $form['source'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Source (YAML)'),
      '#description' => $description,
      '#default_value' => $composite->getElementsRaw(),
    ];
    $form['#attached']['library'][] = 'webform/webform.codemirror.yaml';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for duplicate keys.
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $composite = $this->entity;

    // Update the values stored on the composite.
    $composite->set('elements', $form_state->getValue('source'));
    $status = $composite->save();

    if ($status) {
      drupal_set_message($this->t('Composite %label has been saved.', [
        '%label' => $composite->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('Composite %label was not saved.', [
        '%label' => $composite->label(),
      ]), 'error');
    }

    $form_state->setRedirect('entity.webform_composite.list');
  }

  /**
   * Helper function to check whether an composite id is already in use.
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('webform_composite')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
