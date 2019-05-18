<?php

namespace Drupal\set_front_page\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\set_front_page\SetFrontPageManager;

/**
 * The general settings form for set_front_page.
 */
class SetFrontPageConfigForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The set front page manager.
   *
   * @var \Drupal\set_front_page\SetFrontPageManager
   */
  protected $setFrontPageManager;

  /**
   * Constructs a new SetFrontPageLocalTasks.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\iset_front_page\SetFrontPageManager $setFrontPageManager
   *   The set_front_page manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, SetFrontPageManager $setFrontPageManager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_manager;
    $this->pathValidator = $path_validator;
    $this->aliasManager = $alias_manager;
    $this->setFrontPageManager = $setFrontPageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'set_front_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'set_front_page.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('set_front_page.manager')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->setFrontPageManager->getConfig();
    $frontpage = $this->setFrontPageManager->getFrontPage();
    $frontpage_alias = $frontpage ? $this->aliasManager->getAliasByPath($config['frontpage']) : $frontpage;

    $form['site_frontpage'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Site's current front page"),
      '#default_value' => $frontpage,
      '#field_prefix' => $this->t('Internal path:') . ' ',
      '#field_suffix' => '<br />' . $this->t('Page alias: @url', ['@url' => $frontpage_alias]) . '<br />',
      '#description' => $this->t('If not set, the built-in Drupal @path page will be used.', ['@path' => 'node']),
    ];

    $form['site_frontpage_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Default front page"),
      '#default_value' => $config['default'],
      '#field_prefix' => $this->t('Internal path:') . ' ',
      '#field_suffix' => '<br />' . $this->t('Page alias: @url', ['@url' => $config['default']]) . '<br />',
      '#description' => $this->t('If provided, each page will have an extra option allowing the front page to be reassigned to this default page instead.'),
    ];

    $form['_prefix'] = [
      '#markup' => t('Select which types of site content should be allowed to be used as the homepage.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $entities = $this->entityTypeManager->getDefinitions();

    foreach ($entities as $entity_type => $entity_info) {

      if ($this->setFrontPageManager->entityTypeIsAllowed($entity_type)) {
        $types = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();

        $form[$entity_type] = [
          '#type' => 'container',
          '#tree' => FALSE,
        ];

        $form[$entity_type]['_prefix'] = [
          '#markup' => $entity_type,
          '#prefix' => '<h4>',
          '#suffix' => '</h4>',
        ];
        foreach ($types as $bundle => $bundle_label) {

          $key = 'set_front_page_' . $entity_type . '__' . $bundle;
          $default = isset($config['types'][$key]) ? $config['types'][$key] : FALSE;
          $form[$entity_type][$key] = [
            '#type' => 'checkbox',
            '#title' => $bundle,
            '#default_value' => $default,
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $frontpage = $form_state->getValue('site_frontpage');
    $default = $form_state->getValue('site_frontpage_default');
    $entities = $this->entityTypeManager->getDefinitions();

    $entities_enabled = [];
    foreach ($entities as $entity_type => $entity_info) {
      if ($this->setFrontPageManager->entityTypeIsAllowed($entity_type)) {
        $types = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
        foreach ($types as $bundle => $bundle_label) {
          $variable_name = 'set_front_page_' . $entity_type . '__' . $bundle;
          $bundle_value = [];
          $bundle_value['entity_type'] = $entity_type;
          $bundle_value['bundle'] = $bundle;
          $bundle_value['status'] = $form_state->getValue($variable_name);
          $entities_enabled[] = $bundle_value;
        }
      }
    }

    $this->setFrontPageManager->saveConfig($frontpage, $default, $entities_enabled);
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for empty front page path.
    if (!$form_state->isValueEmpty('site_frontpage')) {
      // Get the normal path of the front page.
      $form_state->setValueForElement($form['site_frontpage'], $this->aliasManager->getPathByAlias($form_state->getValue('site_frontpage')));
    }
    // Validate front page path.
    if (($value = $form_state->getValue('site_frontpage')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_frontpage')]));

    }
    if (!$this->pathValidator->isValid($form_state->getValue('site_frontpage'))) {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_frontpage')]));
    }

    // Check for empty front page path.
    if (!$form_state->isValueEmpty('site_frontpage_default')) {
      // Get the normal path of the front page.
      $form_state->setValueForElement($form['site_frontpage_default'], $this->aliasManager->getPathByAlias($form_state->getValue('site_frontpage_default')));
    }
    // Validate front page path.
    if (($value = $form_state->getValue('site_frontpage_default')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_frontpage_default')]));

    }
    if (!$this->pathValidator->isValid($form_state->getValue('site_frontpage_default'))) {
      $form_state->setErrorByName('site_frontpage_default', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_frontpage_default')]));
    }

    // @todo: validate entity types

    parent::validateForm($form, $form_state);
  }

}
