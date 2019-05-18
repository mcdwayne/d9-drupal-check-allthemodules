<?php

namespace Drupal\config_selector\Controller;

use Drupal\config_selector\ConfigSelector;
use Drupal\config_selector\ConfigSelectorSortTrait;
use Drupal\config_selector\Entity\FeatureInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Allows the UI to switch between different configuration entities.
 */
class Switcher extends ControllerBase {
  use ConfigSelectorSortTrait;

  /**
   * Selects the supplied configuration entity.
   *
   * @param \Drupal\config_selector\Entity\FeatureInterface $config_selector_feature
   *   The Configuration selector feature.
   * @param string $config_entity_type
   *   The entity type of the configuration entity we are switching to.
   * @param string $config_entity_id
   *   The ID of the configuration entity we are switching to.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   This always redirects to the feature's manage route.
   */
  public function select(FeatureInterface $config_selector_feature, $config_entity_type, $config_entity_id) {
    $redirect = $this->redirect('entity.config_selector_feature.manage', ['config_selector_feature' => $config_selector_feature->id()]);
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $config_entity */
    $config_entity = $this->getConfigEntity($config_selector_feature, $config_entity_type, $config_entity_id);
    if (!$config_entity) {
      return $redirect;
    }

    // Enable the entity and disable the others.
    $config_entity->setStatus(TRUE);
    $config_entity->save();

    $entities = $config_selector_feature->getConfigurationByType($config_entity_type);
    unset($entities[$config_entity->id()]);
    $args = [
      ':enabled_link' => ConfigSelector::getConfigEntityLink($config_entity),
      '@enabled_label' => $config_entity->label(),
    ];
    foreach ($entities as $entity) {
      if ($entity->status()) {
        $entity->setStatus(FALSE);
        $entity->save();

        $args[':disabled_link'] = ConfigSelector::getConfigEntityLink($entity);
        $args['@disabled_label'] = $entity->label();
        $this->getLogger('config_selector')->info('Configuration entity <a href=":disabled_link">@disabled_label</a> has been disabled in favor of <a href=":enabled_link">@enabled_label</a>.', $args);
      }
    }

    $this->messenger()->addStatus($this->t('Configuration entity <a href=":enabled_link">@enabled_label</a> has been selected.', $args));
    return $redirect;
  }

  /**
   * Gets a valid configuration entity to work with.
   *
   * @param \Drupal\config_selector\Entity\FeatureInterface $config_selector_feature
   *   The Configuration selector feature.
   * @param string $config_entity_type
   *   The entity type of the configuration entity we are switching to.
   * @param string $config_entity_id
   *   The ID of the configuration entity we are switching to.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|false
   *   The configuration entity we are switching to, or FALSE if invalid.
   */
  protected function getConfigEntity(FeatureInterface $config_selector_feature, $config_entity_type, $config_entity_id) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $config_entity */
    $config_entity = $this->entityTypeManager()->getStorage($config_entity_type)->load($config_entity_id);
    if (!$config_entity) {
      $this->messenger()->addWarning($this->t('Configuration entity of type %type and ID $id does not exist.', ['%type' => $config_entity_type, '%id' => $config_entity_id]));
      return FALSE;
    }
    if ($config_entity->getThirdPartySetting('config_selector', 'feature') !== $config_selector_feature->id()) {
      $this->messenger()->addWarning($this->t('Configuration entity %config_label is not part of the %feature_label feature.', ['%config_label' => $config_entity->label(), '%feature_label' => $config_selector_feature->label()]));
      return FALSE;
    }
    return $config_entity;
  }

}
