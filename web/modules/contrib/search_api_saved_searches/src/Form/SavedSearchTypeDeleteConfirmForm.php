<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityDeleteFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting saved search types.
 */
class SavedSearchTypeDeleteConfirmForm extends EntityConfirmFormBase {

  use EntityDeleteFormTrait;

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManager|null
   */
  protected $configManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);

    $form->setConfigManager($container->get('config.manager'));

    return $form;
  }

  /**
   * Retrieves the config manager.
   *
   * @return \Drupal\Core\Config\ConfigManager
   *   The config manager.
   */
  public function getConfigManager() {
    return $this->configManager ?: \Drupal::service('config.manager');
  }

  /**
   * Sets the config manager.
   *
   * @param \Drupal\Core\Config\ConfigManager $config_manager
   *   The new config manager.
   *
   * @return $this
   */
  public function setConfigManager(ConfigManager $config_manager) {
    $this->configManager = $config_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_searches = $this->entityTypeManager
      ->getStorage('search_api_saved_search')
      ->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->accessCheck(FALSE)
      ->execute();
    if ($num_searches) {
      $caption = '<p>' . $this->formatPlural($num_searches, '%type is used by 1 saved search on your site. You cannot remove this saved search type until you have removed all of the %type saved searches.', '%type is used by @count saved searches on your site. You cannot remove this saved search type until you have removed all of the %type saved searches.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    $form = parent::buildForm($form, $form_state);

    // Add information about the changes to dependent entities.
    // @see \Drupal\Core\Entity\EntityDeleteForm::buildForm()
    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $entity */
    $entity = $this->getEntity();
    $this->addDependencyListsToForm($form, $entity->getConfigDependencyKey(), $this->getConfigNamesToDelete($entity), $this->getConfigManager(), $this->entityManager);

    return $form;
  }

  /**
   * Returns config names to delete for the deletion confirmation form.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity being deleted.
   *
   * @return string[]
   *   A list of configuration names that will be deleted by this form.
   */
  protected function getConfigNamesToDelete(ConfigEntityInterface $entity) {
    return [$entity->getConfigDependencyName()];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to delete this saved search type?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->messenger()
      ->addStatus($this->t('The saved search type was successfully deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
