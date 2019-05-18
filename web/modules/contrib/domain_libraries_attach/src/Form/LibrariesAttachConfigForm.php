<?php

namespace Drupal\domain_libraries_attach\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain_libraries_attach\DomainLibrariesManager;

/**
 * Class LibrariesAttachConfigForm.
 *
 * @package Drupal\domain_libraries_attach\Form
 */
class LibrariesAttachConfigForm extends ConfigFormBase {

  /**
   * Domain Storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * Libraries Manager.
   *
   * @var \Drupal\domain_libraries_attach\DomainLibrariesManager
   */
  protected $librariesManager;

  /**
   * Associative array of domains $id => $name.
   *
   * @var array
   */
  protected $domainOptionsList;

  /**
   * LibrariesAttachConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\domain_libraries_attach\DomainLibrariesManager $libraries_manager
   *   Libraries Manager.
   * @param \Drupal\domain\DomainStorageInterface $domain_storage
   *   Domain Storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainLibrariesManager $libraries_manager, DomainStorageInterface $domain_storage) {
    parent::__construct($config_factory);
    $this->domainStorage = $domain_storage;
    $this->librariesManager = $libraries_manager;
    $this->domainOptionsList = $this->domainStorage->loadOptionsList();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('domain_libraries_attach.manager'),
      $container->get('entity_type.manager')->getStorage('domain')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'domain_libraries_attach.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_libraries_attach_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_libraries_attach.settings');
    $defaultTheme = $this->librariesManager->defaultThemeName;
    $librariesOptionsList = $this->librariesManager->getOptionsList();

    foreach ($this->domainOptionsList as $id => $name) {

      $form[$id] = [
        '#type' => 'fieldset',
        '#title' => $name,
        $id . '_libraries' => [
          '#type' => 'select',
          '#title' => $this->t('Libraries from default "@default_theme" theme:', ['@default_theme' => $defaultTheme]),
          '#options' => $librariesOptionsList,
          '#multiple' => TRUE,
          '#default_value' => $config->get($id),
        ],
      ];
    }

    if (empty($this->domainOptionsList)) {
      $form['no_domains_message'] = [
        '#markup' => $this->t("Can't find any domain record"),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('domain_libraries_attach.settings');

    foreach ($this->domainOptionsList as $id => $name) {
      $selectedValue = $form_state->getValue($id . '_libraries');
      $config->set($id, $selectedValue);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
