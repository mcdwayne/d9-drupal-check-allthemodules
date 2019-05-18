<?php

namespace Drupal\commerce_epayco\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for adding and editing.
 *
 * @ingroup commerce_epayco
 */
class CommerceEpaycoApiDataFormBase extends EntityForm {

  /**
   * The entityQueryFactory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Construct method.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An entity query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $api_data = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $api_data->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $api_data->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$api_data->isNew(),
    ];
    $form['secret_keys'] = [
      '#type' => 'details',
      '#title' => $this->t('Secret keys'),
      '#description' => $this->t('Values you will need for basic features (for example, when adding a payment gateway).'),
      '#open' => TRUE,
    ];
    $form['secret_keys']['p_cust_id_client'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#maxlength' => 15,
      '#default_value' => $api_data->getIdClient(),
      '#description' => $this->t('This is also known as "p_cust_id_cliente"'),
      '#required' => TRUE,
    ];
    $form['secret_keys']['p_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#maxlength' => 255,
      '#default_value' => $api_data->getPkey(),
      '#description' => $this->t('This is also known as "p_key"'),
      '#required' => TRUE,
    ];

    // API rest options.
    $form['secret_keys_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Secret keys Api Rest'),
      '#description' => $this->t('Following are values you will need for advanced features (API callbacks using the ePayco library).'),
      '#open' => TRUE,
    ];
    $form['secret_keys_api']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#maxlength' => 255,
      '#default_value' => $api_data->getApiKey(),
      '#required' => TRUE,
    ];
    $form['secret_keys_api']['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#maxlength' => 255,
      '#default_value' => $api_data->getPrivateKey(),
      '#required' => TRUE,
    ];
    $form['language'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language code'),
      '#maxlength' => 2,
      '#size' => 10,
      '#default_value' => $api_data->getLanguageCode(),
      '#description' => $this->t('Language code needed for the gateway. Examples: ES, EN.'),
      '#required' => TRUE,
    ];
    $form['test'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $api_data->isTestMode(),
    ];

    return $form;
  }

  /**
   * Checks if there is already a record with given id.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if entity already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    $query = $this->entityQueryFactory->get('commerce_epayco_api_data');

    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Save');

    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $api_data = $this->getEntity();

    $status = $api_data->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Configuration <em>%label</em> was updated.', ['%label' => $api_data->label()]));
    }
    else {
      drupal_set_message($this->t('New configuration <em>%label</em> was added.', ['%label' => $api_data->label()]));
    }

    $form_state->setRedirect('entity.commerce_epayco_api_data.list');
  }

}
