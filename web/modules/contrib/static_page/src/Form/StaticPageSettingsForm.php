<?php
/**
 * @file
 * Contains \Drupal\static_page\Form\StaticPageSettingsForm.
 */

namespace Drupal\static_page\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure search settings for this site.
 */
class StaticPageSettingsForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the StaticPageSettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
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
    return 'static_page_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['static_page.fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('static_page.fields');
    $types = NodeType::loadMultiple();

    $fields_config = $config->get('fields');
    $form['help'] = [
      '#markup' => $this->t('Select which field, if any, to use for static page content.'),
    ];
    $form['fields'] = [
      '#tree' => TRUE,
    ];
    $valid_field_types = [
      'string_long',
      'text_long',
      'text_with_summary',
    ];
    foreach ($types as $key => $node_type) {
      $field_options = ['' => $this->t('-- None --')];
      $fields = $this->entityManager->getFieldDefinitions('node', $key);
      foreach ($fields as $machine_name => $field) {
        if ($field->getName() != 'revision_log'
            && in_array($field->getType(), $valid_field_types)
        ) {
          $field_options[$machine_name] = $field->getLabel() . ' (' . $field->getName() . ')';
        }
      }
      $form['fields'][$key] = [
        '#title' => $node_type->label(),
        '#type' => 'select',
        '#options' => $field_options,
        '#default_value' => !empty($fields_config[$key]) ? $fields_config[$key] : '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('static_page.fields')
      ->set('fields', array_filter($form_state->getValue('fields')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
