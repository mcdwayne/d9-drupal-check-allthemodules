<?php

namespace Drupal\content_entity_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\content_entity_builder\BaseFieldConfigManager;
use Drupal\content_entity_builder\ContentTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an add form for BaseFieldConfig.
 */
class BaseFieldConfigAddForm extends BaseFieldConfigFormBase {

  /**
   * The BaseFieldConfig manager.
   *
   * @var \Drupal\content_entity_builder\BaseFieldConfigManager
   */
  protected $baseFieldConfigManager;

  /**
   * Constructs a new BaseFieldConfigAddForm.
   *
   * @param \Drupal\content_entity_builder\BaseFieldConfigManager $base_field_config_manager
   *   The base_field_config manager.
   */
  public function __construct(BaseFieldConfigManager $base_field_config_manager) {
    $this->baseFieldConfigManager = $base_field_config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.content_entity_builder.base_field_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentTypeInterface $content_type = NULL, $base_field = NULL) {
    $form = parent::buildForm($form, $form_state, $content_type, $base_field);
    $form['actions']['submit']['#value'] = $this->t('Add base field');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBaseField($base_field) {
    return $this->contentType->getBaseField($base_field);
  }

}
