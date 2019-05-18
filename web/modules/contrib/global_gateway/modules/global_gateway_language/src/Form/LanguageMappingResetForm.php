<?php

namespace Drupal\global_gateway_language\Form;

use Drupal\Core\Url;
use Drupal\global_gateway\Mapper\MapperPluginManager;
use Drupal\global_gateway_ui\Form\MappingResetFormBase;

/**
 * Class ConfigEntityFormBase.
 *
 * Typically, we need to build the same form for both adding a new entity,
 * and editing an existing entity.
 */
class LanguageMappingResetForm extends MappingResetFormBase {

  protected $languages;

  /**
   * {@inheritdoc}
   */
  public function __construct(MapperPluginManager $mapperManager) {
    parent::__construct('region_languages', $mapperManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset languages for %label region ?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_gateway_language_mapping_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('global_gateway_ui.region', ['region_code' => $this->entity->id()]);
  }

}
