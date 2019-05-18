<?php

namespace Drupal\cision_notify_pull\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides CisionMappingTargetForm form.
 */
class CisionMappingTargetForm extends FormBase {

  /**
   * Admin config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cision_mapping_target_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param string $type
   *   Type {@inheritdoc}.
   *
   * @return array
   *   Array {@inheritdoc}.
   */
  protected function getTargets($type) {

    $field_names = [];
    $exclude_fields = $this->getExcludedFields();
    $targets = $this->entityFieldManager->getFieldDefinitions('node', $type);
    foreach ($targets as $target_machine_name => $target) {
      if (!in_array($target_machine_name, $exclude_fields)) {
        $field_names[$target_machine_name] = $target->getLabel();
      }
    }
    return $field_names;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   Array {@inheritdoc}.
   */
  protected function getExcludedFields() {
    $exclude_fields = [
      'nid',
      'vid',
      'uuid',
      'type',
      'uid',
      'revision_timestamp',
      'revision_uid',
      'revision_log',
      'revision_translation_affected',
      'path',
      'menu_link',
    ];
    return $exclude_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSources() {

    $source_targets = [
      'Id',
      'PublishDateUtc',
      'LastChangeDateUtc',
      'InformationType',
      'LanguageCode',
      'CountryCode',
      'CustomerReference',
      // 'SyndicatedUrl',.
      'SeOrganizationNumber',
      'Title',
      'Header',
      'Intro',
      'Body',
      'CompanyInformation',
      'Complete',
      'Contact',
      'HtmlTitle',
      'HtmlIntro',
      'HtmlHeader',
      'HtmlBody',
      'HtmlContact',
      'HtmlCompanyInformation',
      'LanguageVersions',
      // 'Categories',.
      'Keywords',
      'Images',
      // 'Videos',.
      'Files',
      'Quotes',
      'QuickFacts',
      'ExternalLinks',
      'SocialMediaPitch',
      'IsRegulatory',
      // 'EmbeddedItems'.
    ];
    return array_combine($source_targets, $source_targets);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('cision_notify_pull.settings');
    $selected_type = $config->get('allowed_type');
    $target_mapping = $config->get('target_mapping');

    $admin_link = Link::fromTextAndUrl($this->t('click here'), Url::fromRoute('cision_notify_pull.select_content_type'))->toString();
    if (empty($selected_type)) {
      drupal_set_message($this->t('You need to choose content type first %click-here', ['%click-here' => $admin_link]));
      return $form;
    }

    $form['targets'] = [
      '#type' => 'table',
      '#header' => [$this->t('Target Fields'), $this->t('Source')],
      '#sticky' => TRUE,
    ];

    $sources = $this->getSources();

    foreach ($this->getTargets($selected_type) as $field_machine_name => $field_label) {

      $form['targets'][$field_machine_name]['description'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="field-label"><b>{{ title }}({{ type }})</b></div>',
        '#context' => [
          'title' => $field_label,
          'type' => $field_machine_name,
        ],
      ];

      $form['targets'][$field_machine_name]['source'] = [
        '#type' => 'select',
        '#default_value' => isset($target_mapping[$field_machine_name]) ? $target_mapping[$field_machine_name] : '',
        '#options' => $sources,
        '#empty_option' => $this->t('Select'),
        '#empty_value' => '',
      ];

    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $target_mapping = [];
    $targets = $form_state->getValue('targets');
    foreach ($targets as $target_key => $source_key) {
      if (!empty($source_key['source'])) {
        $target_mapping[$target_key] = $source_key['source'];
      }
    }
    $config = $this->configFactory->getEditable('cision_notify_pull.settings');
    $config->set('target_mapping', $target_mapping)
      ->save();

    drupal_set_message($this->t('The changes have been saved.'));
  }

}
