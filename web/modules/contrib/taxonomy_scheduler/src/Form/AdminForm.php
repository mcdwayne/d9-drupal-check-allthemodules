<?php

namespace Drupal\taxonomy_scheduler\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\taxonomy_scheduler\Service\TaxonomySchedulerFieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem;

/**
 * Class AdminForm.
 */
class AdminForm extends ConfigFormBase {

  /**
   * VocabularyStorage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  private $vocabularyStorage;

  /**
   * TaxonomySchedulerFieldManager.
   *
   * @var \Drupal\taxonomy_scheduler\Service\TaxonomySchedulerFieldManager
   */
  private $fieldManager;

  /**
   * ConfigImmutable.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $configImmutable;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $configEditable;

  /**
   * AdminForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabularyStorage
   *   The vocabulary storage.
   * @param \Drupal\taxonomy_scheduler\Service\TaxonomySchedulerFieldManager $fieldManager
   *   The field service.
   * @param \Drupal\Core\Config\ImmutableConfig $configImmutable
   *   The immutable config settings.
   * @param \Drupal\Core\Config\Config $configEditable
   *   The editable config settings.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    VocabularyStorageInterface $vocabularyStorage,
    TaxonomySchedulerFieldManager $fieldManager,
    ImmutableConfig $configImmutable,
    Config $configEditable
  ) {
    $this->vocabularyStorage = $vocabularyStorage;
    $this->fieldManager = $fieldManager;
    $this->configImmutable = $configImmutable;
    $this->configEditable = $configEditable;
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('taxonomy_scheduler.vocabulary_storage'),
      $container->get('taxonomy_scheduler.field_manager'),
      $container->get('taxonomy_scheduler.config'),
      $container->get('taxonomy_scheduler.config_editable')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'taxonomy_scheduler_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    return [
      'taxonomy_scheduler.settings',
    ];
  }

  /**
   * The admin form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configImmutable;
    $options = [];
    $vocabularies = $this->vocabularyStorage->loadMultiple();

    foreach ($vocabularies as $key => $vocabulary) {
      $options[$key] = $vocabulary->label();
    }

    $form['vocabularies'] = [
      '#title' => $this->t('Vocabularies'),
      '#description' => $this->t('Choose the vocabularies to apply the publishing field to.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('vocabularies') ?: [],
    ];

    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#description' => $this->t('Name of the field where the publishing date will be stored.'),
      '#default_value' => $config->get('field_name') ?: 'field_publishing_date',
      '#disabled' => (bool) $config->get('initialized'),
      '#required' => TRUE,
    ];

    $form['field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field label'),
      '#description' => $this->t('Label of the publishing date field.'),
      '#default_value' => $config->get('field_label') ?: $this->t('Publish on'),
      '#required' => TRUE,
    ];

    $form['field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Field is required'),
      '#default_value' => $config->get('field_required') ?: 0,
      '#description' => $this->t('Determine whether the publishing date field is required.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Admin form submit handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $vocabularies = \array_filter($form_state->getValue('vocabularies'));
    $fieldLabel = $form_state->getValue('field_label');
    $fieldName = $form_state->getValue('field_name');
    $fieldRequired = $form_state->getValue('field_required');

    $storedVocabs = $this->configImmutable->get('vocabularies');

    if (\is_array($storedVocabs)) {
      $disabled = \array_diff($storedVocabs, $vocabularies);

      if (!empty($disabled)) {
        $fieldStorageItem = new TaxonomyFieldStorageItem([
          'vocabularies' => $disabled,
          'fieldLabel' => $fieldLabel,
          'fieldName' => $fieldName,
          'fieldRequired' => $fieldRequired,
        ]);
        $this->fieldManager->disableField($fieldStorageItem);
      }
    }

    $this->configEditable->set('initialized', 1)
      ->set('vocabularies', $vocabularies)
      ->set('field_label', $fieldLabel)
      ->set('field_name', $fieldName)
      ->set('field_required', $fieldRequired)
      ->save();

    if (!empty($vocabularies)) {
      $fieldStorageItem = new TaxonomyFieldStorageItem([
        'vocabularies' => $vocabularies,
        'fieldLabel' => $fieldLabel,
        'fieldName' => $fieldName,
        'fieldRequired' => $fieldRequired,
      ]);

      $this->fieldManager->addField($fieldStorageItem);
      $this->fieldManager->enableField($fieldStorageItem);
    }

    parent::submitForm($form, $form_state);
  }

}
