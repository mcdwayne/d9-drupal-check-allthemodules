<?php

namespace Drupal\bibcite_crossref\Form;

use Drupal\bibcite_crossref\CrossrefClientInterface;
use Drupal\bibcite_entity\Entity\Reference;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStore;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Doi lookup form.
 */
class DoiLookupForm extends FormBase {

  /**
   * Serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Module temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Crossref client service.
   *
   * @var \Drupal\bibcite_crossref\CrossrefClientInterface
   */
  protected $crossrefClient;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Doi lookup form constructor.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   Import plugins manager.
   * @param \Drupal\Core\TempStore\PrivateTempStore $temp_store
   *   Module temp store.
   * @param \Drupal\bibcite_crossref\CrossrefClientInterface $crossref_client
   *   Crossref client service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(SerializerInterface $serializer, PrivateTempStore $temp_store, CrossrefClientInterface $crossref_client, ModuleHandlerInterface $module_handler) {
    $this->serializer = $serializer;
    $this->tempStore = $temp_store;
    $this->crossrefClient = $crossref_client;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serializer'),
      $container->get('tempstore.private')->get('bibcite_entity_doi_lookup'),
      $container->get('bibcite_crossref.client'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_crossref_doi_lookup';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['doi'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DOI'),
      '#required' => TRUE,
      '#default_value' => '',
      '#description' => $this->t('Enter a DOI name in the form: <b>10.1000/123456</b>'),
      '#size' => 60,
      '#maxlength' => 255,
      '#weight' => -4,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Populate using DOI'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $doi = $form_state->getValue('doi');

    try {
      // @todo Validate DOI for correct syntax?
      $data = $this->crossrefClient->lookupDoiRaw($doi);

      // Respect contributor and keyword deduplication settings.
      // @todo This should not depend on the bibcite_import module existence.
      if ($this->moduleHandler->moduleExists('bibcite_import')) {
        $config = \Drupal::config('bibcite_import.settings');
        $denormalize_context = [
          'contributor_deduplication' => $config->get('settings.contributor_deduplication'),
          'keyword_deduplication' => $config->get('settings.keyword_deduplication'),
        ];
      }
      else {
        $denormalize_context = [];
      }

      $decoded = $this->serializer->decode($data, 'crossref');
      $entity = $this->serializer->denormalize($decoded, Reference::class, 'crossref', $denormalize_context);
      $form_state->setValue('entity', $entity);
    }
    catch (\Exception $exception) {
      $err_string = $this->t('Error has occured:<br>%ex', ['%ex' => $exception->getMessage()]);
      $form_state->setErrorByName('doi', $err_string);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->getValue('entity');

    if ($entity) {
      $this->tempStore->set(\Drupal::currentUser()->id(), $entity);

      $redirect_url = Url::fromRoute("entity.bibcite_reference.add_form", [
        'bibcite_reference_type' => $entity->bundle(),
      ]);
      $form_state->setRedirectUrl($redirect_url);
    }
  }

}
