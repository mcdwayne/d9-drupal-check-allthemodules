<?php

namespace Drupal\bibcite_import\Form;

use Drupal\bibcite\Plugin\BibciteFormatManagerInterface;
use Drupal\bibcite_entity\Entity\Reference;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStore;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Populate values to Reference form.
 */
class PopulateForm extends FormBase {

  /**
   * Serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Format manager service.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManagerInterface
   */
  protected $formatManager;

  /**
   * Module temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Create new PopulateForm class.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   Serializer service.
   * @param \Drupal\bibcite\Plugin\BibciteFormatManagerInterface $format_manager
   *   Format manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStore $temp_store
   *   Module temp store.
   */
  public function __construct(Serializer $serializer, BibciteFormatManagerInterface $format_manager, PrivateTempStore $temp_store) {
    $this->serializer = $serializer;
    $this->formatManager = $format_manager;
    $this->tempStore = $temp_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serializer'),
      $container->get('plugin.manager.bibcite_format'),
      $container->get('tempstore.private')->get('bibcite_entity_populate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_import_populate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => array_map(function ($definition) {
        return $definition['label'];
      }, $this->formatManager->getImportDefinitions()),
      '#required' => TRUE,
    ];

    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paste your bibliographic entry here'),
      '#description' => $this->t('If you paste multiple entries first will be used.'),
      '#rows' => 20,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Populate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $format_id = $form_state->getValue('format');
    $data = $form_state->getValue('data');
    $format = $this->formatManager->getDefinition($format_id);

    try {
      $decoded = $this->serializer->decode($data, $format_id);

      $config = \Drupal::config('bibcite_import.settings');
      $denormalize_context = [
        'contributor_deduplication' => $config->get('settings.contributor_deduplication'),
        'keyword_deduplication' => $config->get('settings.keyword_deduplication'),
      ];

      $entity = $this->serializer->denormalize(reset($decoded), Reference::class, $format_id, $denormalize_context);
      $form_state->setValue('entity', $entity);
    }
    catch (\Exception $exception) {
      $err_string = $this->t('@format entry is not valid. Please check pasted text.<br>%ex', ['@format' => $format['label'], '%ex' => $exception->getMessage()]);
      $form_state->setErrorByName('data', $err_string);
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
