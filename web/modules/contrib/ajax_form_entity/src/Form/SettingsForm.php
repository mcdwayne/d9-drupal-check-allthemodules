<?php
/**
 * @file
 * Contains \Drupal\ajax_form_entity\Form\ExampleConfigForm.
 */

namespace Drupal\ajax_form_entity\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\ajax_form_entity\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, EntityTypeBundleInfo $entity_bundle_info) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'id_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->entityTypeManager->getDefinitions();
    $all_bundle = $this->entityBundleInfo->getAllBundleInfo();

    // @todo : option to define form modes and view modes.
    $form_modes = $definitions['entity_form_mode'];
    $view_modes = $definitions['entity_view_mode'];

    // Get all display types for the entity.
    //$bundles=$entity_config->getBundleEntityType();
    //$bundles=$entity_config->bundle();

    // $tab_entity_labels = $entity_manager->getEntityTypeLabels();
    $config = $this->config('ajax_form_entity.settings')->get();

    // Content entities to be excluded.
    $excluded_entity_types = [
      1 => 'shortcut',
      2 => 'menu_link_content',
      3 => 'file',
    ];

    foreach ($all_bundle as $entity_name => $bundle) {

      // Exclude content entities which are not supported.
      if (!isset($definitions[$entity_name]) || array_search($entity_name, $excluded_entity_types)) {
        continue;
      }
      /* @var  $config_entity \Drupal\Core\Config\Entity\ConfigEntityType */
      $config_entity = $definitions[$entity_name];
      $group = $config_entity->get('group');

      // Fix missing content group for ECK module. @todo : fix in ECK.
      if ($config_entity->getProvider() == 'eck' && !strpos($entity_name, '_type')) {
        $group = 'content';
      }

      // Do not work with configuration entities for now.
      // @todo : see what can be done to improve the backoffice.
      if ($group == 'configuration' || $group == NULL) {
        continue;
      }

      if (!isset($form[$group])) {
        $form[$group] = [
          '#type' => 'container',
          '#tree' => TRUE,
          '#title' => $group,
        ];
      }

      $form[$group][$entity_name] = [
        '#type' => 'details',
        '#title' => $config_entity->getLabel(),
      ];

      // Define all configuration per bundle.
      if (is_array($bundle)) {
        foreach ($bundle as $bundle_name => $label) {
          if (isset($label['label'])) {
            $form[$group][$entity_name][$bundle_name] = [
              '#type' => 'details',
              '#group' => $entity_name,
              '#open' => TRUE,
              '#title' => $label['label'],
            ];
            if (isset($config[$group][$entity_name][$bundle_name])) {
              $default_values = $config[$group][$entity_name][$bundle_name];
            }

            $form[$group][$entity_name][$bundle_name]['activate'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Activate Ajax Entity form'),
              '#default_value' => isset($default_values['activate']) ? $default_values['activate'] : '',
            ];

            $form[$group][$entity_name][$bundle_name]['popin'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Popin mode'),
              '#default_value' => isset($default_values['popin']) ? $default_values['popin'] : '',
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            $form[$group][$entity_name][$bundle_name]['reload'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Reload the form on creation'),
              '#default_value' => isset($default_values['reload']) ? $default_values['reload'] : '',
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            $form[$group][$entity_name][$bundle_name]['send_content'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Show result on creation'),
              '#default_value' => isset($default_values['send_content']) ? $default_values['send_content'] : TRUE,
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            $selector_type_options = [
              'prepend' => $this->t('Before'),
              'append' => $this->t('After'),
            ];

            $form[$group][$entity_name][$bundle_name]['selector_type'] = [
              '#type' => 'select',
              '#options' => $selector_type_options,
              '#title' => $this->t('Creation view mode'),
              '#description' => $this->t('Area to send the content. If custom'),
              '#default_value' => isset($default_values['selector_type']) ? $default_values['selector_type'] : '#prepend',
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][send_content]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            $form[$group][$entity_name][$bundle_name]['selector_content'] = [
              '#type' => 'textfield',
              '#title' => $this->t('Class or ID where to send the content'),
              '#default_value' => isset($default_values['selector_content']) ? $default_values['selector_content'] : '',
              '#description' => $this->t('Let empty to send before / after the creation form.'),
              '#weight' => 1,
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][send_content]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            $form[$group][$entity_name][$bundle_name]['edit_link'] = [
              '#type' => 'textfield',
              '#title' => $this->t('Edit link label'),
              '#description' => $this->t('Provide an AJAX edit link in any display mode. Let blank for no link.'),
              '#default_value' => isset($default_values['edit_link']) ? $default_values['edit_link'] : $this->t('Edit'),
              '#weight' => 1,
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            $form[$group][$entity_name][$bundle_name]['form'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Add a field with edit form'),
              '#description' => $this->t('EXPERIMENTAL - Provide a field in view mode with ajax edit form.'),
              '#default_value' => isset($default_values['form']) ? $default_values['form'] : 0,
              '#weight' => 1,
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            $form[$group][$entity_name][$bundle_name]['show_message'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Show the message'),
              '#default_value' => isset($default_values['show_message']) ? $default_values['show_message'] : 1,
              '#weight' => 1,
              '#states' => [
                'visible' => [
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name . '][activate]"]' => ['checked' => TRUE],
                ],
              ],
            ];

            /*
             * // @todo : activate delete link.
            $form[$group][$entity_name] [$bundle_name]['delete_link'] = array(
              '#type' => 'textfield',
              '#title' => $this->t('Delete link label'),
              '#description' => $this->t('Provide an AJAX delete link in any display mode. Let blank for no link.'),
              '#default_value' => isset($default_values['delete_link']) ? $default_values['delete_link'] : $this->t('Delete'),
              '#weight' => 1,
              '#states' => array(
                'visible' => array(
                  'input[name="' . $group . '[' . $entity_name . '][' . $bundle_name. '][activate]"]' => array(
                    'checked' => TRUE,
                  ),
                ),
              ),
            );

             */


            /*
             * @todo : view mode and form mode to be selected.
            $form[$group][$entity_name] [$bundle_name]['view_mode'] = array(
              '#type' => 'select',
              '#options' => array(),
              '#title' => $this->t('Creation view mode'),
              '#description' => $this->('The view mode used after creation'),
              '#default_value' => isset($default_values['view_mode']) ? $default_values['view_mode'] : '',
            );
             $form[$group][$entity_name] [$bundle_name]['form_mode'] = array(
              '#type' => 'select',
              '#options' => array(),
              '#title' => $this->t('Default form mode'),
              '#description' => $this->('The form mode used for AJAX edition'),
              '#default_value' => isset($default_values['form_mode']) ? $default_values['form_mode'] : '',
            );


            */
          }

        }
      }
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ajax_form_entity.settings');
    $config->set('content', $form_state->getValue('content'));
    $config->save();
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['ajax_form_entity.settings'];
  }

}
