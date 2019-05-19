<?php

namespace Drupal\simpleads;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Utility ads utility class.
 */
class UtilityBase {

  protected $manager_name;

  public function getStatuses() {
    return [
      1 => $this->t('Active'),
      0 => $this->t('Inactive'),
    ];
  }

  public function getStatusName($status) {
    return !empty($this->getStatuses()[$status]) ? $this->getStatuses()[$status] : '';
  }

  /**
   * Get ad type name by ID.
   */
  public function getName($id) {
    $types = $this->getTypes();
    if (!empty($types[$id])) {
      return $types[$id];
    }
  }

  /**
   * Get Simpleads types.
   */
  public function getTypes() {
    $types = [];
    $manager = \Drupal::service($this->manager_name);
    $plugins = $manager->getDefinitions();
    foreach ($plugins as $id => $plugin) {
      $plugin = $manager->createInstance($plugin['id']);
      $types[$id] = $plugin->getName();
    }
    return $types;
  }

  public function getBuildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $manager = \Drupal::service($this->manager_name);
    $plugins = $manager->getDefinitions();
    $plugin = $manager->createInstance($plugins[$type]['id']);
    return $plugin->buildForm($form, $form_state, $type, $id);
  }

  public function getSubmitForm($op, $options, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $manager = \Drupal::service($this->manager_name);
    $plugins = $manager->getDefinitions();
    $plugin = $manager->createInstance($plugins[$type]['id']);
    if ($op == 'create') {
      return $plugin->createFormSubmit($options, $form_state, $type);
    }
    else if ($op == 'update') {
      return $plugin->updateFormSubmit($options, $form_state, $type, $id);
    }
    else {
      return $plugin->deleteFormSubmit($options, $form_state, $type, $id);
    }
  }

}
