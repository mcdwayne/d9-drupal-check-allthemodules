<?php

namespace Drupal\machine\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class AdminForm.
 *
 * @package Drupal\machine\Form
 */
class AdminForm extends FormBase {

  /**
   * Module config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AdminForm object.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->config = $this->configFactory()->getEditable('machine.settings');
    $this->entityTypeManager = $entity_type_manager;
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options     = [];
    $definitions = $this->entityTypeManager->getDefinitions();

    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($definitions as $definition) {
      if ($definition->getGroup() != 'content') {
        continue;
      }

      $options[$definition->get('id')] = $definition->getLabel();
    }

    $form['intro'] = [
      '#markup' => $this->t('<strong>Please mind</strong>: when you submit this form entity field cache will be invalidated.'),
    ];

    $types = $this->config->get('types') ?: [];

    $form['types'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Config entity types'),
      '#options'       => $options,
      '#default_value' => $types,
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   *  Saves user selection into configuration.
   *  Invalidates 'entity_field_info' cache tag.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_types = array_filter($form_state->getValue('types'));
    $original_types = $this->config->get('types') ?: [];

    try {
      $this->config->set('types', $selected_types)->save();
      Cache::invalidateTags(['entity_field_info']);
      \Drupal::entityDefinitionUpdateManager()->applyUpdates();
      drupal_set_message($this->t('Success'));
    }
    catch (EntityStorageException $e) {
      // Rollback all changes on exception.
      $this->config->set('types', $original_types)->save();
      Cache::invalidateTags(['entity_field_info']);

      drupal_set_message($this->t('Errors during Entity Updates:<br/> %message.', ['%message' => $e->getMessage()]), 'error');
      watchdog_exception('machine', $e);
    }
  }

}
