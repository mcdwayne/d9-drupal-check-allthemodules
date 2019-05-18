<?php

namespace Drupal\micro_taxonomy\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\taxonomy\VocabularyForm;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for vocabulary edit forms.
 *
 * @internal
 */
class SiteVocabularyForm extends VocabularyForm {

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a new vocabulary form.
   *
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The site negotiator.
   */
  public function __construct(VocabularyStorageInterface $vocabulary_storage, SiteNegotiatorInterface $negotiator) {
    parent::__construct($vocabulary_storage);
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('micro_site.negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    if ($this->negotiator->getActiveSite()) {
      $form_state->setRedirectUrl(Url::fromRoute('<current>'));
    }
  }


}
