<?php

namespace Drupal\taxonomy_breadcrumb;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyForm as VocabularyFormBuilderBase;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VocabularyListBuilder.
 *
 * @package Drupal\taxonomy_breadcrumb.
 */
class VocabularyForm extends VocabularyFormBuilderBase {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface.
   */
  protected $vocabularyStorage;

  /**
   * Constructs a new vocabulary form.
   *
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(VocabularyStorageInterface $vocabulary_storage) {
    $this->vocabularyStorage = $vocabulary_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('taxonomy_vocabulary')
    );
  }

  /**
   * Override Drupal\Core\Config\Entity\ConfigEntityListBuilder::load().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $vocabulary = $this->entity;

    $form['third_party_settings']['taxonomy_breadcrumb_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Breadcrumb path (taxonomy_breadcrumb)'),
      '#default_value' => $vocabulary->getThirdPartySetting('taxonomy_breadcrumb', 'taxonomy_breadcrumb_path'),
      '#maxlength' => 128,
      '#description' => t("Specify the path this vocabulary links to as a breadcrumb. If blank, the breadcrumb will not appear. Use a relative path and don't add a trailing slash. For example: node/42 or my/path/alias."),
    );

    $form = parent::form($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $vocabulary = $this->entity;
    $vocabulary->setThirdPartySetting('taxonomy_breadcrumb', 'taxonomy_breadcrumb_path', $form_state->getValue('taxonomy_breadcrumb_path'));
    parent::save($form, $form_state);
  }

}
