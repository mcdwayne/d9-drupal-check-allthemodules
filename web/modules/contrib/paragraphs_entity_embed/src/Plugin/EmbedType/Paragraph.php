<?php

namespace Drupal\paragraphs_entity_embed\Plugin\EmbedType;

use Drupal\embed\EmbedType\EmbedTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Paragraph embed type.
 *
 * @EmbedType(
 *   id = "paragraphs_entity_embed",
 *   label = @Translation("Paragraph")
 * )
 */
class Paragraph extends EmbedTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enable_paragraph_type_filter' => FALSE,
      'paragraphs_type_filter' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return file_create_url(drupal_get_path('module', 'paragraphs_entity_embed') . '/js/plugins/drupalparagraph/paragraph.png');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['enable_paragraph_type_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter which Paragraph types to be embed'),
      '#default_value' => $this->getConfigurationValue('enable_paragraph_type_filter'),
    ];
    $form['paragraphs_type_filter'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Paragraph types'),
      '#default_value' => $this->getConfigurationValue('paragraphs_type_filter'),
      '#options' => $this->getAllParagraphTypes(),
      '#states' => [
        'visible' => [':input[name="type_settings[enable_paragraph_type_filter]"]' => ['checked' => TRUE]],
      ],
    ];

    return $form;
  }

  /**
   * Methods get all paragraph types as options list.
   */
  protected function getAllParagraphTypes() {
    $paragraph_types = [];
    $types = \Drupal::service('entity_type.bundle.info')->getBundleInfo('paragraph');
    foreach ($types as $machine_name => $type) {
      $paragraph_types[$machine_name] = $type['label'];
    }
    return $paragraph_types;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!$form_state->hasAnyErrors()) {
      $this->setConfigurationValue('enable_paragraph_type_filter', $form_state->getValue('enable_paragraph_type_filter'));
      // Set views options.
      $paragraphs_types = $form_state->getValue('enable_paragraph_type_filter') ? array_filter($form_state->getValue('paragraphs_type_filter')) : [];
      $this->setConfigurationValue('paragraphs_type_filter', $paragraphs_types);

    }
  }

}
