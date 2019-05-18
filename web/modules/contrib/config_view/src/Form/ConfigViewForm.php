<?php

namespace Drupal\config_view\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\ViewsData;

/**
 * Defines a base form for editing Config Entity Types.
 */
class ConfigViewForm extends ConfigFormBase {

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Gets the typed config manager.
   *
   * @return \Drupal\Core\Config\TypedConfigManagerInterface
   *   Returns the all Config Entities.
   *
   * @see \Drupal::service('config.typed')
   */
  protected function getTypedConfig() {
    $types = $this->entityTypeManager->getDefinitions();
    $options = [];
    foreach ($types as $type) {
      if ($type->getGroup() === 'configuration') {
        $options[$type->id()] = (string) $type->getLabel();
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config_view.settings')->get('data');
    $form = [];

    $form['description'] = [
      '#markup' => '<p>' . t('Choose the configuration entities for which data needs to be exposed to the Views module.') . '</p>',
    ];

    $form['data'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getTypedConfig(),
      '#default_value' => empty($config) ? $this->getTypedConfig() : $config,
      '#title' => $this->t('Configuration Entities:'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This is the default entity object builder function. It is called before any
   * other submit handler to build the new entity object to be used by the
   * following submit handlers. At this point of the form workflow the entity is
   * validated and the form state can be updated, this way the subsequently
   * invoked handlers can retrieve a regular entity object to act on. Generally
   * this method should not be overridden unless the entity requires the same
   * preparation for two actions, see \Drupal\comment\CommentForm for an example
   * with the save and preview actions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('config_view.settings');
    $config->set('data', $form_state->getValues()['data'])->save();
    parent::submitForm($form, $form_state);
    // Clear cache for views - only.
    $this->viewsData->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['config_view.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('views.views_data'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a CommunityCoreSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data used to clear the views cache.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ViewsData $views_data, EntityTypeManagerInterface $entity_type_manager) {
    $this->viewsData = $views_data;
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($config_factory);
  }

}
