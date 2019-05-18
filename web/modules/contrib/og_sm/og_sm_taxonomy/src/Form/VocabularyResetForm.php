<?php

namespace Drupal\og_sm_taxonomy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface;
use Drupal\taxonomy\Form\VocabularyResetForm as VocabularyResetFormBase;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for resetting a vocabulary to alphabetical order.
 */
class VocabularyResetForm extends VocabularyResetFormBase {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * The site taxonomy manager.
   *
   * @var \Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface
   */
  protected $siteTaxonomyManager;

  /**
   * Constructs a new VocabularyResetForm object.
   *
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface $site_taxonomy_manager
   *   The site taxonomy manager.
   */
  public function __construct(TermStorageInterface $term_storage, SiteManagerInterface $site_manager, SiteTaxonomyManagerInterface $site_taxonomy_manager) {
    parent::__construct($term_storage);
    $this->siteManager = $site_manager;
    $this->siteTaxonomyManager = $site_taxonomy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('taxonomy_term'),
      $container->get('og_sm.site_manager'),
      $container->get('og_sm_taxonomy.site_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $site = $this->siteManager->currentSite();
    $form_state->set('site', $site);

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   */
  public function getCancelUrl(NodeInterface $site = NULL) {
    if (!$site) {
      $site = $this->siteManager->currentSite();
    }

    if (!$site) {
      return parent::getCancelUrl();
    }
    return new Url('og_sm_taxonomy.vocabulary.term_overview', [
      'node' => $site->id(),
      'taxonomy_vocabulary' => $this->getEntity()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\node\NodeInterface $site */
    $site = $form_state->get('site');
    if (!$site) {
      parent::submitForm($form, $form_state);
      return;
    }

    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);

    $this->siteTaxonomyManager->resetTermWeights($site, $this->entity);

    drupal_set_message($this->t('Reset vocabulary %name to alphabetical order.', ['%name' => $this->entity->label()]));
    $this->logger('taxonomy')->notice('Reset vocabulary %name to alphabetical order.', ['%name' => $this->entity->label()]);
    $form_state->setRedirectUrl($this->getCancelUrl($site));
  }

}
