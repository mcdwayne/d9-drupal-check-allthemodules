<?php

namespace Drupal\simple_lazyloader\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines a form for changing a Lazyloader's configuration.
 */
class LazyloaderConfigForm extends ConfigFormBase {

  protected $entityTypeManager;
  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, EntityManager $entityManager) {
    parent::__construct($config_factory);
    $this->entityManager = $entityManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'simple_lazyloader';

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('simple_lazyloader.settings');

    $form['is_activated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate lazy loading of images'),
      '#default_value' => $config->get('lazyloader_settings')['is_activated'],
    ];

    $form['activated_for_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate for all images'),
      '#default_value' => $config->get('lazyloader_settings')['activated_for_all'],
    ];

    $form['container'] = [
      '#type' => 'container',
      '#states' => [
        "visible" => [
          "input[name='activated_for_all']" => [
            "checked" => FALSE,
          ],
        ],
      ],
    ];

    $form['container']['deactivate_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Activate Lazyloader for specific entities.'),
      '#attached' => [
        'library' => [
          'block/drupal.block',
        ],
      ],
    ];

    // List of all the available Entities.
    $entities = [
      'node_type',
      'block_content_type',
      'taxonomy_vocabulary',
      'comment_type',
      'page',
      'webform',
      'view',
    ];

    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    /* @var $definition EntityTypeInterface */
    foreach ($entity_type_definitions as $definition) {
      $entity_id = $definition->get('id');

      /**
       * Check if the current entity is one of
       * the available entities for the module.
      **/
      if (in_array($entity_id, $entities)) {
        $bundles = $this->entityManager->getStorage($entity_id)->loadMultiple();
        $form[$entity_id] = [
          '#title' => $definition->get('label'),
          '#group' => 'deactivate_tabs',
          '#type' => 'details',
        ];

        /**
         * Get all bundles for the specific Entity
         * and create a form elements for them.
         **/
        foreach ($bundles as $bundle) {

          switch ($entity_id) {
            case 'node_type':
            case 'taxonomy_vocabulary':
              $title = $bundle->get('name');
              $id = $bundle->get('originalId');
              break;

            case 'system_main_block':
              $title = $bundle->get('settings')['label'];
              $id = $bundle->get('originalId');
              break;

            case 'webform':
              $title = $bundle->get('title');
              $id = $bundle->get('id');
              break;

            default:
              $title = $bundle->get('label');
              $id = $bundle->get('id');
          }

          // Check if there is a default value from the existing configuration.
          $default_value = !empty($config->get('lazyloader_settings')[$id]) ? $config->get('lazyloader_settings')[$id] : 0;

          $form[$entity_id][$id] = [
            '#title' => $title,
            '#type' => 'checkbox',
            '#default_value' => $default_value,
          ];
        }
      }
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('simple_lazyloader.settings');
    $config->set('lazyloader_settings', $form_state->getValues());

    $config->save();

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'simple_lazyloader.settings',
    ];

  }

}
