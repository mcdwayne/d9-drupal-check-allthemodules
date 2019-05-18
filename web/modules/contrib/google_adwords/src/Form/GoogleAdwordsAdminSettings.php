<?php

/**
 * @file
 * Contains \Drupal\google_adwords\Form\GoogleAdwordsAdminSettings.
 */

namespace Drupal\google_adwords\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class GoogleAdwordsAdminSettings.
 *
 * @package Drupal\google_adwords\Form
 */
class GoogleAdwordsAdminSettings extends ConfigFormBase {

  /**
   * \Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * GoogleAdwordsAdminSettings constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager
  ) {
    parent::__construct($config_factory);
    $this->entity_type_manager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_adwords.adminsettings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_adwords_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_adwords.adminsettings');

    $form['conversion'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default Conversion settings'),
      '#collapsible' => FALSE,
    );

    $form['conversion']['conversion_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Conversion ID'),
      '#default_value' => $config->get('conversion_id'),
      '#size' => 15,
      '#maxlength' => 255,
      '#required' => FALSE,
      '#description' => '',
    );
    $form['conversion']['language'] = array(
      '#type' => 'textfield',
      '#title' => t('Conversion Language'),
      '#default_value' => $config->get('language'),
      '#size' => 15,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => '',
    );
    $form['conversion']['format'] = array(
      '#type' => 'textfield',
      '#title' => t('Conversion Format'),
      '#default_value' => $config->get('format'),
      '#size' => 15,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => '',
    );
    $form['conversion']['color'] = array(
      '#type' => 'textfield',
      '#title' => t('Conversion Color'),
      '#default_value' => $config->get('color'),
      '#size' => 15,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => '',
    );
    $form['conversion']['external_script'] = array(
      '#type' => 'textfield',
      '#title' => t('External JavaScript'),
      '#default_value' => $config->get('external_script'),
      '#size' => 80,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => '',
    );

    // Render the role overview.
    $form['conversion']['roles'] = array(
      '#type' => 'fieldset',
      '#title' => t('User Role Tracking'),
      '#collapsible' => TRUE,
      '#description' => t('Define what user roles should be tracked.'),
    );

    /**
     * @var \Drupal\user\RoleStorage $role_storage
     *   Storage object for role entities
     */
    $role_storage =  $this->entity_type_manager->getStorage('user_role');
    /**
     * @var int|array[/Drupal/Core/Entity] $roles
     */
    $roles = $role_storage
      ->getQuery()
      ->execute();

    if (is_array($roles)) {
      foreach ($role_storage->loadMultiple($roles) as $role) {
        /**
         * @var \Drupal\user\Entity\Role $role
         *   each role entity
         */

        /**
         * @var string $var_name
         *   admin settings config var name for the role
         *
         * @note that role ids are no longer numeric ids in a table
         *   so their ids should be safe to use in keys
         */
        $var_name = 'google_adwords_track_' . $role->id();

        $form['conversion']['roles'][$var_name] = array(
          '#type' => 'checkbox',
          '#title' => $role->label(), // this should already be translated
          '#default_value' => $config->get($var_name),
        );
      }
    }
    else {
      $form['conversion']['roles']['none'] = array(
        '#type' => 'markup',
        '#markup' => $this->t('No roles found in the system. None can be marked for conversion'),
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('google_adwords.adminsettings')
      ->save();
  }

}
