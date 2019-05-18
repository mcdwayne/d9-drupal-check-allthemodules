<?php

namespace Drupal\micro_path\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the micro_path entity edit forms.
 *
 * @ingroup micro_path
 */
class MicroPathForm extends ContentEntityForm {

  /**
   * A language manager for looking up the current language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A domain path loader for loading domain path entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\domain_path\Entity\DomainPath */
    $form = parent::buildForm($form, $form_state);
    $options = [];
    $entity = $form_state->getFormObject()->getEntity();

    foreach ($this->languageManager->getLanguages() as $language) {
      $options[$language->getId()] = $language->getName();
    }

    $form['language'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#options' => $options,
      '#default_value' => $entity->getLanguageCode(),
      '#languages' => Language::STATE_ALL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
    parent::validateForm($form, $form_state);
    $sites = $this->entityTypeManager->getStorage('site')->loadMultiple();
    $site_id_value = $form_state->getValue('site_id');
    $site_id = isset($site_id_value[0]['target_id']) ? $site_id_value[0]['target_id'] : NULL;

    $source_value = $form_state->getValue('source');
    $source = isset($source_value[0]['value']) ? $source_value[0]['value'] : NULL;

    $source_check = rtrim(trim($source), " \\/");
    if ($source_check && $source_check[0] !== '/') {
      $form_state->setErrorByName('source', $this->t('Micro path "%source" needs to start with a slash.', ['%source' => $source_check]));
    }

    $alias_value = $form_state->getValue('alias');
    $alias = isset($alias_value[0]['value']) ? $alias_value[0]['value'] : NULL;

    $alias_check = rtrim(trim($alias), " \\/");
    if ($alias_check && $alias_check[0] !== '/') {
      $form_state->setErrorByName('alias', $this->t('Micro path "%alias" needs to start with a slash.', ['%alias' => $alias_check]));
    }

    if ($micro_path_entity_data = $this->entityTypeManager->getStorage('micro_path')->loadByProperties(['alias' => $alias])) {
      foreach ($micro_path_entity_data as $micro_path_entity) {
        $check_site_id = $micro_path_entity->get('site_id')->target_id;
        if ($check_site_id == $site_id) {
          $site_url = $sites[$site_id]->getSiteUrl();
          $form_state->setErrorByName('alias', $this->t('Micro path %path matches an existing micro path alias for %site_url.', ['%path' => $alias, '%domain_path' => $site_url]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The micro path %feed has been updated.', ['%feed' => $entity->toLink()->toString()]));
    }
    else {
      drupal_set_message($this->t('The micro path %feed has been added.', ['%feed' => $entity->toLink()->toString()]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $status;
  }

}
