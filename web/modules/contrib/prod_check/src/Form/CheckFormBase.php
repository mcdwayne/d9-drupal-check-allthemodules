<?php

namespace Drupal\prod_check\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form for prod check forms.
 */
abstract class CheckFormBase extends EntityForm {

  /**
   * The prod check plugin being configured.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckInterface
   */
  protected $plugin;

  /**
   * The check storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new prod check form.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The check storage.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('prod_check')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->plugin = $this->entity->getPlugin();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => '255',
      '#description' => $this->t('A unique label for this advanced check. This label will be displayed in the interface of modules that integrate with prod checks.'),
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this check. It must only contain lowercase letters, numbers and underscores.'),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
    );
    $form['plugin'] = array(
      '#type' => 'value',
      '#value' => $this->entity->get('plugin'),
    );

    if ($this->plugin instanceof PluginFormInterface) {
      $form += $this->plugin->buildConfigurationForm($form, $form_state);
    }

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the prod check already exists.
   *
   * @param string $id
   *   The check ID
   *
   * @return bool
   *   TRUE if the check exists, FALSE otherwise.
   */
  public function exists($id) {
    $check = $this->storage->load($id);
    return !empty($check);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($this->plugin instanceof PluginFormInterface) {
      $this->plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($this->plugin instanceof PluginFormInterface) {
      $this->plugin->submitConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The production check has been successfully saved.'));

    $form_state->setRedirect('entity.prod_check.collection');
  }

}
