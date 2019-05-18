<?php

namespace Drupal\entity_library\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for entity library add/edit forms.
 */
class EntityLibraryForm extends EntityForm {

  /**
   * The request path condition.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $requestPathCondition;

  /**
   * EntityLibraryForm constructor.
   *
   * @param \Drupal\Core\Condition\ConditionManager $condition_plugin_manager
   *   The condition plugin manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct(ConditionManager $condition_plugin_manager) {
    $this->requestPathCondition = $condition_plugin_manager->createInstance('request_path');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_library\Entity\EntityLibraryInterface $entity */
    $entity = $this->entity;

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the loading bar style.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#description' => $this->t('A unique name for this entity library instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$entity->isNew() ? $entity->id() : NULL,
      '#machine_name' => [
        'exists' => '\Drupal\entity_library\Entity\EntityLibrary::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => ['label'],
      ],
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
    ];

    // @TODO Add library files validation.
    $form['library_info'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Library info'),
      '#description' => $this->t('Same structure as <a href="@defining-libraries">*.libraries.yml</a> but without the library name, it is the ID of this entity. You must use absolute paths.', ['@defining-libraries' => 'https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module#define-library']),
      '#default_value' => $entity->getLibraryInfo(),
      '#attributes' => ['data-yaml-editor' => 'true'],
    ];
    if (!\Drupal::moduleHandler()->moduleExists('yaml_editor')) {
      $t_args = ['@yaml-editor' => 'https://www.drupal.org/project/yaml_editor'];
      $this->messenger()->addWarning($this->t('It is recommended to install the <a href="@yaml-editor">YAML Editor</a> module for easier editing.', $t_args));
      $form['library_info']['#rows'] = count(explode("\n", $form['library_info']['#default_value'])) + 3;
      $form['library_info']['#default_value'] = $entity->getLibraryInfo();
    }
    else {
      $form['library_info']['#default_value'] = Yaml::encode($entity->getLibraryInfo());
    }

    // @TODO Add support for access conditions.
    // Set the default condition configuration and build the condition form.
    $this->requestPathCondition->setConfiguration($entity->getConditions());
    $form += $this->requestPathCondition->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->requestPathCondition->submitConfigurationForm($form, $form_state);

    // Update the condition form values.
    $form_state->setValue('conditions', $this->requestPathCondition->getConfiguration());

    if (is_array($form_state->getValue('library_info'))) {
      $form_state->setValue('library_info', Yaml::encode($form_state->getValue('library_info')));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_library\Entity\EntityLibraryInterface $entity */
    $entity = $this->entity;

    $status = $entity->save();

    $t_args = ['%name' => $entity->label()];
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addMessage($this->t('The entity library %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The entity library %name has been added.', $t_args));

      $context = array_merge($t_args, [
        'link' => $entity->toLink($this->t('View'), 'collection')->toString(),
      ]);
      $this->logger('entity_library')->notice('Added entity library %name.', $context);
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = t('Save library definition');
    $actions['delete']['#value'] = t('Delete library definition');

    return $actions;
  }

}
