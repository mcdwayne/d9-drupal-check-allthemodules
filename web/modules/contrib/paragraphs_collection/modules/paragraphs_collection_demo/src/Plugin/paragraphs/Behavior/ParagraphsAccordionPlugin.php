<?php

namespace Drupal\paragraphs_collection_demo\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Provides a way to define accordion effect.
 *
 * @ParagraphsBehavior(
 *   id = "accordion",
 *   label = @Translation("Accordion"),
 *   description = @Translation("Accordion effect for paragraphs."),
 *   weight = 0
 * )
 */
class ParagraphsAccordionPlugin extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    foreach (Element::children($build) as $field) {
      if ($field == $this->getConfiguration()['paragraph_accordion_field']) {
        $build[$field]['#attributes']['class'][] = 'accordion';
        break;
      }
    }
    $build['#attached']['library'] = ['paragraphs_collection_demo/accordion'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $paragraphs_type = $form_state->getFormObject()->getEntity();
    if ($paragraphs_type->isNew()) {
      return [];
    }

    $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $paragraphs_type->id());
    $fields = array_filter($field_definitions, function ($definition) {
      return $definition instanceof FieldConfigInterface && $definition->getFieldStorageDefinition()->getCardinality() !== 1;
    });
    $options = array_map(function ($definition) {
      return $definition->getLabel();
    }, $fields);

    if (!empty($options)) {
      $form['paragraph_accordion_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Accordion field'),
        '#description' => $this->t('Choose a field to be used as the accordion container.'),
        '#options' => $options,
        '#default_value' => $this->configuration['paragraph_accordion_field'],
      ];
    }
    else {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('There are no fields available with the cardinality greater than one. Please add at least one in the <a href=":link">Manage fields</a> page.', [
          ':link' => Url::fromRoute("entity.{$paragraphs_type->getEntityType()->getBundleOf()}.field_ui_fields", [$paragraphs_type->getEntityTypeId() => $paragraphs_type->id()])->toString(),
        ]),
        '#attributes' => [
          'class' => ['messages messages--error'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('paragraph_accordion_field')) {
      $form_state->setErrorByName('message', $this->t('The Accordion plugin cannot be enabled if the accordion field is missing.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['paragraph_accordion_field'] = $form_state->getValue('paragraph_accordion_field');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'paragraph_accordion_field' => '',
    ];
  }

}
