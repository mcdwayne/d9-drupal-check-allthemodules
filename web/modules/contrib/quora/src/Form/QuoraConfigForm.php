<?php

namespace Drupal\quora\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Admin form for setting Google Api and CX ID.
 */
class QuoraConfigForm extends ConfigFormBase {

  /**
   * EntityFieldManager services object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManager $entity_field_manager) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quora_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['quora.admin'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('quora.admin');

    $form['google_cse_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Custom Search API'),
      '#description' => $this->t('Provide Google CSE API to be used my module'),
      '#default_value' => $config->get('google_cse_api'),
    ];
    $form['google_cse_cx'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Custom Search Engine CX ID'),
      '#default_value' => $config->get('google_cse_cx'),
      '#description' => $this->t('The custom search engine corresponding to this cx-id must be able to search quora.com'),
    ];
    $form['quora'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select Tags field for content types.'),
      '#description' => $this->t('This field content will be used for fetching related quora questions.'),
    ];
    foreach (NodeType::loadMultiple() as $content_type) {
      // Get bundle's original Id.
      $bundle_id = $content_type->getOriginalId();
      $fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle_id);
      if ($fields) {
        $options = [];
        foreach ($fields as $field_machine_name => $field_definition) {
          // Removes base fields.
          if ($field_definition->getTargetBundle() == $bundle_id) {
            $options[$field_machine_name] = $field_definition->getlabel();
          }
        }
        $form['quora'][$bundle_id . '_fields'] = array(
          '#type' => 'select',
          '#title' => $content_type->label(),
          '#options' => array_merge(array('auto' => $this->t('Auto')), $options),
          '#default_value' => $config->get($bundle_id . '_fields'),
        );
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit function for Admin Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('quora.admin');
    $values = $form_state->getValues();
    $config->set('google_cse_api', $values['google_cse_api'])
      ->set('google_cse_cx', $values['google_cse_cx'])
      ->save();

    foreach (NodeType::loadMultiple() as $content_type) {
      $bundle_id = $content_type->getOriginalId();
      $config->set($bundle_id . '_fields', $values[$bundle_id . '_fields'])->save();
    }
    parent::submitForm($form, $form_state);
  }

}
