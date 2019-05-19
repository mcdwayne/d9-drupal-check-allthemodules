<?php

namespace Drupal\simple_integrations\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Integrations entity configuration form.
 *
 * @ingroup simple_integrations
 */
class IntegrationEntityForm extends EntityForm {

  /**
   * Entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Constructs an Integration object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An entity query factory for the Integration entity type.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * Get the title of the integration.
   *
   * @return string
   *   The label of the entity.
   */
  public function getTitle() {
    return $this->t('Edit %integration', [
      '%integration' => $this->entity->label,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Return a specific entity type ID instead.
    return 'integration_' . $this->entity->id() . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $integration = $this->entity;

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#description' => $this->t('Human-readable label for this integration.'),
      '#default_value' => $integration->label,
    ];

    $form['external_end_point'] = [
      '#title' => $this->t('External end point'),
      '#type' => 'textfield',
      '#default_value' => $integration->get('external_end_point'),
      '#required' => TRUE,
    ];

    $form['active'] = [
      '#title' => $this->t('Active'),
      '#type' => 'checkbox',
      '#description' => $this->t('Is this integration active?'),
      '#default_value' => $integration->isActive(),
    ];

    $form['debug_mode'] = [
      '#title' => $this->t('Debug mode'),
      '#type' => 'checkbox',
      '#description' => $this->t('When in debug mode, certain actions may cause a log entry to be created in the Drupal logs.'),
      '#default_value' => $integration->isDebugMode(),
    ];

    $form['auth_details'] = [
      '#title' => $this->t('Authentication details'),
      '#type' => 'fieldset',
    ];

    $form['auth_details']['auth_type'] = [
      '#title' => $this->t('Authentication type'),
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
        'headers' => $this->t('Headers'),
        'basic_auth' => $this->t('Basic auth'),
        'certificate' => $this->t('Certificate'),
      ],
      '#default_value' => $integration->get('auth_type'),
      '#required' => TRUE,
    ];

    $form['auth_details']['certificate'] = [
      '#title' => $this->t('Certificate'),
      '#description' => $this->t('The location of a certificate file. This is only used if the auth type is set to certificate.'),
      '#type' => 'textfield',
      '#default_value' => $integration->get('certificate'),
    ];

    $form['auth_details']['auth_user'] = [
      '#title' => $this->t('Auth user'),
      '#description' => $this->t('The auth username (or equivalent) for this callback.'),
      '#type' => 'textfield',
      '#default_value' => $integration->get('auth_user'),
    ];

    $form['auth_details']['auth_key'] = [
      '#title' => $this->t('Auth key'),
      '#description' => $this->t('The auth key (or equivalent) for this callback.'),
      '#type' => 'textfield',
      '#default_value' => $integration->get('auth_key'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $integration = $this->getEntity();
    $status = $integration->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label integration.', [
        '%label' => $integration->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('There was an error while saving the %label integration.', [
        '%label' => $integration->label(),
      ]));
    }

    $form_state->setRedirect('entity.integration.collection');
  }

}
