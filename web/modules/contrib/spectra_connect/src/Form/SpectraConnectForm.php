<?php

namespace Drupal\spectra_connect\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SpectraConnectForm.
 *
 * @ingroup spectra_connect
 *
 * @package Drupal\spectra_connect\Form
 *
 * Form controller for the spectra_connect entity edit forms.
 */
class SpectraConnectForm extends EntityForm {

  /**
   * Constructs an SpectraConnectForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
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
   * Build Bundle Type List.
   *
   * @return array
   *   List of entity types.
   */
  public function createEntityList() {
    $ret = [];
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $type => $info) {
      // Is this a content/front-facing entity?
      if ($info instanceof ContentEntityType) {
        $label = $info->getLabel();
        if ($label instanceof TranslatableMarkup) {
          $label = $label->render();
        }
        $ret[$type] = $label;
      }
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\spectra_connect\Entity\SpectraConnect */
    $form = parent::form($form, $form_state);
    $ent = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ent->label(),
      '#description' => $this->t("Label for the Spectra Connect Entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ent->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$ent->isNew(),
    ];

    $form['plugin'] = [
      '#title' => $this->t('Plugin'),
      '#description' => $this->t('The Spectra plugin to use. Leave blank for the default.'),
      '#type' => 'textfield',
      '#required' => FALSE,
      '#default_value' => isset($ent->plugin) ? $ent->plugin : '',
    ];

    $form['delete_endpoint'] = [
      '#title' => $this->t('Spectra DELETE Endpoint'),
      '#description' => $this->t('The endpoint for sending DELETE requests.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => isset($ent->delete_endpoint) ? $ent->delete_endpoint : '',
    ];

    $form['get_endpoint'] = [
      '#title' => $this->t('Spectra GET Endpoint'),
      '#description' => $this->t('The endpoint for sending GET requests.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => isset($ent->get_endpoint) ? $ent->get_endpoint : '',
    ];

    $form['post_endpoint'] = [
      '#title' => $this->t('Spectra POST Endpoint'),
      '#description' => $this->t('The endpoint for sending POST requests.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => isset($ent->post_endpoint) ? $ent->post_endpoint : '',
    ];

    $form['api_key'] = [
      '#title' => $this->t('API Key'),
      '#description' => $this->t('The API key for accessing the Spectra server. You will need to get a key from the server.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => isset($ent->api_key) ? $ent->api_key : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Redirect to term list after save.
    $form_state->setRedirect('entity.spectra_connect.collection');
    $entity = $this->entity;
    $entity->save();
  }

  /**
   * Check whether an Spectra Connect Entity configuration entity exists.
   *
   * @param string $id
   *   The machine name of the Spectra Connect Entity.
   *
   * @return bool
   *   Whether the entity exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('spectra_connect')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
