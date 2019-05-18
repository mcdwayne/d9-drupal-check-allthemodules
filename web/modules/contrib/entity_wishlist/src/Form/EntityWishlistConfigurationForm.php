<?php

namespace Drupal\entity_wishlist\Form;

/**
 * @file
 * Contains Drupal\entity_wishlist\Form\EntityWishlistConfigurationForm.
 */


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class SettingsForm.
 */
class EntityWishlistConfigurationForm extends ConfigFormBase {

  protected $nodestorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $nodestorage, ConfigFactoryInterface $config_factory) {
    $this->nodestorage = $nodestorage;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entitywishlist.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_wishlist_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('entitywishlist.settings');
    $contentTypes = $this->nodestorage->getStorage('node_type')->loadMultiple();

    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    $form['entitywishlist_content_type'] = [
      '#type' => 'checkboxes',
      '#cache' => ['max-age' => 0],
      '#options' => $contentTypesList,
      '#default_value' => $config->get('entitywishlist_content_type') ? $config->get('entitywishlist_content_type') : [],
      '#title' => $this->t('Please choose any content type which you want for wishlist.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('entitywishlist.settings')
      ->set('entitywishlist_content_type', $form_state->getValue('entitywishlist_content_type'))
      ->save();
    drupal_flush_all_caches();
  }

}
