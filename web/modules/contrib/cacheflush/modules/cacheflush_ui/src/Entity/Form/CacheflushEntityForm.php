<?php

namespace Drupal\cacheflush_ui\Entity\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cacheflush\Controller\CacheflushApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for Cacheflush entity edit forms.
 *
 * @ingroup cacheflush
 */
class CacheflushEntityForm extends ContentEntityForm {

  /**
   * Cacheflush API.
   *
   * @var \Drupal\cacheflush\Controller\CacheflushApi
   */
  protected $cacheflush;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, CacheflushApi $cacheflush) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->cacheflush = $cacheflush;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('cacheflush.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* @var $entity \Drupal\cacheflush_entity\Entity\CacheflushEntity */
    $form = parent::buildForm($form, $form_state);
    $form['title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->getTitle(),
      '#required' => TRUE,
    ];

    $this->presetForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Mark the entity as requiring validation.
    $entity->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);
    // Call validation function for tabs.
    foreach ($form_state->getStorage()['cacheflush_tabs'] as $tab => $value) {
      $value['validation']($tab, $form, $form_state);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->setData($form_state->getStorage()['presets']);
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Cacheflush entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Cacheflush entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.cacheflush.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function presetForm(&$form, &$form_state) {

    $storage = $form_state->getStorage();
    // Form element, vertical tab parent.
    $form['cacheflush_vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 50,
    ];

    // Add vertical tabs.
    $storage['cacheflush_tabs'] = $this->moduleHandler->invokeAll('cacheflush_ui_tabs');
    $original_tabs = cacheflush_ui_cacheflush_ui_tabs();
    foreach ($storage['cacheflush_tabs'] as $key => $value) {
      $form[$key] = [
        '#type' => 'details',
        '#title' => Html::escape($value['name']),
        '#group' => 'cacheflush_vertical_tabs',
        '#weight' => isset($value['weight']) ? $value['weight'] : NULL,
        '#attributes' => isset($original_tabs[$key]) ? ['class' => ['original_tabs']] : [],
        '#tree' => TRUE,
      ];
    }

    // Adding table elemnts to tabs.
    $storage['preset_options'] = $this->cacheflush->getOptionList();
    $data = $this->entity->getData();
    foreach ($storage['preset_options'] as $key => $value) {
      // Special tab element added only if there module are instaled.
      if ($value['category'] == 'vertical_tabs_often' && !$this->moduleHandler
        ->moduleExists($key)
      ) {
        continue;
      }
      $form[$value['category']][$key] = [
        '#type' => 'checkbox',
        '#title' => Html::escape($key),
        '#default_value' => isset($data[$key]) ? 1 : 0,
        '#description' => Html::escape($value['description']),
      ];
    }

    $this->tabsDescription($form);
    $storage['presets'] = [];
    $storage['data'] = $data;
    $form_state->setStorage($storage);
  }

  /**
   * Update form tabs with Notes.
   */
  public function tabsDescription(&$form) {

    $form['cacheflush_form_mani_note'] = [
      '#type' => 'item',
      '#title' => $this->t('Cache sources'),
      '#weight' => 40,
      '#description' => $this->t('Select below the different cache sources you wish to clear when your preset is executed. Don`t be afraid to select them, all these are flushed when you normally clear all the caches. Select only those you need for better performance.'),
    ];

    $form['vertical_tabs_core']['note'] = [
      '#type' => 'item',
      '#title' => $this->t('Note'),
      '#description' => $this->t('Select any of the cache database tables below, to be truncated when this preset is executed.'),
      '#weight' => -10,
    ];

    $form['vertical_tabs_functions']['note'] = [
      '#type' => 'item',
      '#title' => $this->t('Note'),
      '#description' => $this->t('Select any of the below functions to be run when this preset is executed.'),
      '#weight' => -10,
    ];

    $form['vertical_tabs_custom']['note'] = [
      '#type' => 'item',
      '#title' => $this->t('Note'),
      '#description' => $this->t('Select any of the tables defined by contributed modules to be flushed when this preset is executed.'),
      '#weight' => -10,
    ];

    $form['vertical_tabs_often']['note'] = [
      '#type' => 'item',
      '#title' => $this->t('Note'),
      '#description' => $this->t('Some contrib modules have unique ways to store their cache, or to flush them.<br />These require custom configuration, so if you can`t find some of your contrib modules here, please submit us an issue on <a href="@url">http://drupal.org/project/cacheflush/issues/</a><br />
Select any from the list below to clear when this preset is executed.', ['@url' => 'http://drupal.org/project/issues/cacheflush/']),
      '#weight' => -10,
    ];
  }

}
