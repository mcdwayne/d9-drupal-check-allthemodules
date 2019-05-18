<?php

namespace Drupal\lightspeed_ecom\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to create and modify Lightspeed eCom Shop entities.
 *
 * @package Drupal\lightspeed_ecom\Form
 */
class ShopEntityForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\lightspeed_ecom\ShopInterface
   */
  protected $entity;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the Lightspeed eCom Shop."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\lightspeed_ecom\Entity\ShopEntity::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['cluster_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cluster ID'),
      '#default_value' => $this->entity->clusterId(),
      '#description' => $this->t("The <code>cluster_id</code> provided by Lightspeed during the installation."),
      '#required' => TRUE,
    );

    $form['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->entity->apiKey(),
      '#description' => $this->t("The API Key provided by Lightspeed customer support."),
      '#required' => TRUE,
    );

    $form['api_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Secret'),
      '#default_value' => $this->entity->apiSecret(),
      '#description' => $this->t("The API Secret provided by Lightspeed customer support."),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->buildEntity($form, $form_state);

    // Validate that a new Shop's ID is unique.
    if ($entity->isNew()) {
      $entityStorage = $this->entityTypeManager->getStorage('lightspeed_ecom_shop');
      $id_exists = $entityStorage->getQuery()
        ->condition('id', $entity->id())
        ->execute();
      if ($id_exists) {
        $form_state->setErrorByName('id', $this->t('Shop machine name must be unique. A shop named %name already exists.', array('%name' => $entity->id())));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Lightspeed eCom Shop.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Lightspeed eCom Shop.', [
          '%label' => $this->entity->label(),
        ]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

}
