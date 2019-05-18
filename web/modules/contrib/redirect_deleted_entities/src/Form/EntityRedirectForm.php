<?php

/**
 * @file
 * Contains \Drupal\redirect_removed_entities\Form\RedirectDeletedEntities.
 */

namespace Drupal\redirect_deleted_entities\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redirect_deleted_entities\RedirectTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class EntityRedirectForm extends ConfigFormBase {

  /**
   * The redirect type manager.
   *
   * @var \Drupal\redirect_deleted_entities\RedirectTypeManager
   */
  protected $redirectTypeManager;

  /**
   * Constructs a RedirectDeletedEntities object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RedirectTypeManager $redirect_type_manager) {
    parent::__construct($config_factory);
    $this->redirectTypeManager = $redirect_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.redirect_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_deleted_entities_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['redirect_deleted_entities.redirects'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $definitions = $this->redirectTypeManager->getDefinitions();

    $config = $this->config('redirect_deleted_entities.redirects');

    foreach ($definitions as $id => $definition) {
      /** @var \Drupal\pathauto\AliasTypeInterface $alias_type */
      $alias_type = $this->redirectTypeManager->createInstance($id, $config->get('redirects.' . $id) ?: []);

      $form[$id] = $alias_type->buildConfigurationForm([], $form_state);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('redirect_deleted_entities.redirects');

    $definitions = $this->redirectTypeManager->getDefinitions();

    foreach ($definitions as $id => $definition) {
      $config->set('redirects.' . $id, $form_state->getValue($id));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
