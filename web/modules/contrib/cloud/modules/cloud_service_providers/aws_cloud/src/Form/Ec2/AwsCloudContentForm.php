<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\cloud\Form\CloudContentForm;
use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\cloud\Service\EntityLinkRendererInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base form class for the AWS Cloud content form.
 */
class AwsCloudContentForm extends CloudContentForm {

  /**
   * The AWS EC2 SErvice.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  protected $awsEc2Service;

  /**
   * Entity link renderer object.
   *
   * @var \Drupal\cloud\Service\EntityLinkRendererInterface
   */
  protected $entityLinkRenderer;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A plugin cache clear instance.
   *
   * @var \Drupal\Core\Plugin\CachedDiscoveryClearerInterface
   */
  protected $pluginCacheClearer;

  /**
   * A cache backend interface instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * The cloud config plugin manager service.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * AwsCloudContentForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The Entity Manager.
   * @param \Drupal\aws_cloud\Service\AwsEc2ServiceInterface $aws_ec2_service
   *   The AWS EC2 Service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The Messenger Service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\cloud\Service\EntityLinkRendererInterface $entity_link_renderer
   *   The entity link render service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheRender
   *   A cache backend interface instance.
   * @param \Drupal\Core\Plugin\CachedDiscoveryClearerInterface $plugin_cache_clearer
   *   A plugin cache clear instance.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $manager,
                              AwsEc2ServiceInterface $aws_ec2_service,
                              Messenger $messenger,
                              EntityRepositoryInterface $entity_repository,
                              EntityLinkRendererInterface $entity_link_renderer,
                              EntityTypeManagerInterface $entity_type_manager,
                              CacheBackendInterface $cacheRender,
                              CachedDiscoveryClearerInterface $plugin_cache_clearer,
                              CloudConfigPluginManagerInterface $cloud_config_plugin_manager,
                              AccountInterface $current_user) {
    parent::__construct($manager, $entity_repository, $messenger);
    $this->awsEc2Service = $aws_ec2_service;
    $this->entityLinkRenderer = $entity_link_renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheRender = $cacheRender;
    $this->pluginCacheClearer = $plugin_cache_clearer;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('aws_cloud.ec2'),
      $container->get('messenger'),
      $container->get('entity.repository'),
      $container->get('entity.link_renderer'),
      $container->get('entity_type.manager'),
      $container->get('cache.render'),
      $container->get('plugin.cache_clearer'),
      $container->get('plugin.manager.cloud_config_plugin'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Copy values of the form elements whose type are item to entity.
    // If not, the properties corresponding to the form elements
    // will be saved as NULL.
    $this->copyFormItemValues($form);

    $this->trimTextfields($form, $form_state);

    parent::save($form, $form_state);
  }

  /**
   * Copy values from #type=item elements to its original element type.
   *
   * @param array $form
   *   The form array.
   */
  protected function copyFormItemValues(array $form) {
    $original_entity = $this->manager
      ->getStorage($this->entity->getEntityTypeId())
      ->load($this->entity->id());

    $item_field_names = [];
    foreach ($form as $name => $item) {
      if (!is_array($item)) {
        continue;
      }

      if (isset($item['#type'])
        && $item['#type'] == 'item'
        && (!isset($item['#not_field']) || $item['#not_field'] === FALSE)
      ) {
        $item_field_names[] = $name;
      }

      if (isset($item['#type']) && $item['#type'] == 'details') {
        foreach ($item as $sub_item_name => $sub_item) {
          if (is_array($sub_item)
            && isset($sub_item['#type'])
            && $sub_item['#type'] == 'item'
            && (!isset($sub_item['#not_field']) || $sub_item['#not_field'] === FALSE)
          ) {
            $item_field_names[] = $sub_item_name;
          }
        }
      }
    }

    foreach ($item_field_names as $item_field_name) {
      // Support multi-valued item fields.
      $values = $original_entity->get($item_field_name)->getValue();
      if ($values != NULL && count($values) > 1) {
        $item_field_values = [];
        foreach ($values as $value) {
          $item_field_values[] = $value['value'];
        }
        $this->entity->set($item_field_name, $item_field_values);
      }
      else {
        $item_field_value = $original_entity->get($item_field_name)->value;
        if ($item_field_value !== NULL) {
          $this->entity->set($item_field_name, $item_field_value);
        }
      }
    }
  }

  /**
   * Trim white spaces in the values of textfields.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function trimTextfields(array $form, FormStateInterface $form_state) {
    $field_names = [];
    foreach ($form as $name => $item) {
      if (!is_array($item)) {
        continue;
      }

      if (isset($item['#type'])
        && $item['#type'] == 'textfield'
      ) {
        $field_names[] = $name;
      }

      if (isset($item['#type']) && $item['#type'] == 'details') {
        foreach ($item as $sub_item_name => $sub_item) {
          if (is_array($sub_item)
            && isset($sub_item['#type'])
            && $sub_item['#type'] == 'textfield'
          ) {
            $field_names[] = $sub_item_name;
          }
        }
      }
    }

    foreach ($field_names as $field_name) {
      $value = $form_state->getValue($field_name);
      if ($value === NULL) {
        continue;
      }

      $value = trim($value);
      $form_state->setValue($field_name, $value);
      $this->entity->set($field_name, $value);
    }
  }

  /**
   * Helper method to clear cache values.
   */
  protected function clearCacheValues() {
    $this->pluginCacheClearer->clearCachedDefinitions();
    $this->cacheRender->invalidateAll();
  }

  /**
   * Add the build array of fieldset others.
   *
   * @param array &$form
   *   The form array.
   * @param int $weight
   *   The weight of the fieldset.
   * @param string $cloud_context
   *   The cloud context.
   */
  protected function addOthersFieldset(array &$form, $weight, $cloud_context = '') {
    $form['others'] = [
      '#type'          => 'details',
      '#title'         => $this->t('Others'),
      '#open'          => FALSE,
      '#weight'        => $weight,
    ];

    $form['others']['cloud_context'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Cloud ID')),
      '#markup' => !$this->entity->isNew()
      ? $this->entity->getCloudContext()
      : $cloud_context,
    ];

    $form['others']['langcode'] = [
      '#title'         => t('Language'),
      '#type'          => 'language_select',
      '#default_value' => $this->entity->getUntranslated()->language()->getId(),
      '#languages'     => Language::STATE_ALL,
      '#attributes'    => ['readonly' => 'readonly'],
      '#disabled'      => FALSE,
    ];

    $form['others']['uid'] = $form['uid'];
    unset($form['uid']);
  }

  /**
   * Helper function to get title translatable string of a item.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $t
   *   The translatable string.
   *
   * @return string
   *   The string of title.
   */
  protected function getItemTitle(TranslatableMarkup $t) {
    return $t->render() . ': ';
  }

  /**
   * Set the uid tag for different entities.
   *
   * @param string $resource_id
   *   The resource id.  For example, instance_id, volume_id.
   * @param string $key
   *   The key to store this value.
   * @param string $uid
   *   The drupal user id.
   */
  protected function setUidInAws($resource_id, $key, $uid) {
    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());
    // Update the volume_created_by_uid.
    $this->awsEc2Service->createTags([
      'Resources' => [$resource_id],
      'Tags' => [
        [
          'Key' => $key,
          'Value' => $uid,
        ],
      ],
    ]);
  }

}
