<?php

namespace Drupal\wizenoze\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wizenoze\Helper\WizenozeAPI;
use GuzzleHttp\json_decode;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure register form settings for this site.
 */
class WizenozeCollectionForm extends ConfigFormBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new WizenozeCollectionForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wizenoze_admin_collection';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wizenoze.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

    $collectionData = [];
    if ($id) {
      $wizenoze = WizenozeAPI::getInstance();
      $collectionData = $wizenoze->viewCollection($id);
    }
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection Name'),
      '#required' => TRUE,
      '#default_value' => (!empty($collectionData['name'])) ? $collectionData['name'] : '',
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => (!empty($collectionData['description'])) ? $collectionData['description'] : '',
    ];

    $nodeTypes = $this->entityManager->getStorage('node_type')->loadMultiple();
    $nodeTypesList = [];
    foreach ($nodeTypes as $name => $type) {
      $nodeTypesList[$name] = $type->label();
    }

    $form['accessType'] = [
      '#type' => 'select',
      '#options' => WizenozeAPI::$wizenozeCollectionTypes,
      '#title' => $this->t('Collection Type'),
      '#required' => TRUE,
      '#default_value' => (!empty($collectionData['accessType'])) ? array_keys(WizenozeAPI::$wizenozeCollectionTypes, $collectionData['accessType']) : '',
      "#empty_option" => $this->t('- Select -'),
    ];

    $form['content_type'] = [
      '#type' => 'select',
      '#options' => $nodeTypesList,
      '#title' => $this->t('Content Type'),
      '#multiple' => TRUE,
      '#default_value' => (!empty($collectionData['id'])) ? $this->config('wizenoze.settings')->get('collection-id-' . $collectionData['id']) : '',
      "#empty_option" => $this->t('- Select -'),
    ];

    $form['collection_id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => (!empty($collectionData['id'])) ? $this->t('Update Collection') : $this->t('Add New Collection'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $wizenoze = WizenozeAPI::getInstance();
    $collectionId = $form_state->getValue('collection_id');
    if ($collectionId > 0) {
      $result = $wizenoze->updateCollection(
          [
            'id' => $collectionId,
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
            'accessType' => WizenozeAPI::$wizenozeCollectionTypes[$form_state->getValue('accessType')],
          ]
      );
    }
    else {
      $result = $wizenoze->createCollection(
          [
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
            'accessType' => WizenozeAPI::$wizenozeCollectionTypes[$form_state->getValue('accessType')],
          ]
      );
    }

    if (!empty($result)) {
      $result = json_decode($result, TRUE);
      $config = $this->config('wizenoze.settings');
      $config->set('collection-id-' . $result['collection']['id'], $form_state->getValue('content_type'))
        ->save();
      $form_state->setRedirect('wizenoze.config.collection.list');
    }
    else {
      drupal_set_message($this->t('Unable to add search engine, please try again'), 'error');
    }
  }

}
