<?php

namespace Drupal\build_hooks\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  protected $entityTypeManager;

  protected $nodeTypes;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
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
  protected function getEditableConfigNames() {
    return [
      'build_hooks.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'build_hooks_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('build_hooks.settings');

    $form['logged_entity_types'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getContentEntityTypes(),
      '#default_value' => $config->get('logging.entity_types'),
      '#title' => $this->t('Loggable entities'),
      '#description' => $this->t('What entities should the system consider when logging "changes" for an environment?'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('build_hooks.settings');
    $config->set('logging.entity_types', $form_state->getValue('logged_entity_types'));
    $config->save();
  }

  /**
   * Gets a list of all the defined content entities in the system.
   *
   * @return array
   *   An array of content entities definitions.
   */
  private function getContentEntityTypes() {
    $content_entity_types = [];
    $allEntityTypes = $this->entityTypeManager->getDefinitions();

    foreach ($allEntityTypes as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $content_entity_types[$entity_type_id] = $entity_type->getLabel();
      }
    }
    return $content_entity_types;
  }

}
