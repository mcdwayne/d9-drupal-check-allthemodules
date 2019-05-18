<?php

namespace Drupal\micro_sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micro_site\Entity\Site;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\sitemap\Form\SitemapSettingsForm;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SiteMapForm.
 */
class MicroSiteMapForm extends SitemapSettingsForm {

  /**
   * The micro site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * An array keyed by menu id with value 0.
   *
   * @var array
   */
  protected $menusDisabled;

  /**
   * An array keyed by vocabulary id with value 0.
   *
   * @var array
   */
  protected $vocabulariesDisabled;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_sitemap_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_sitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SiteInterface $site = NULL) {
    if (!$site instanceof SiteInterface) {
      $form = [
        '#type' => 'markup',
        '#markup' => $this->t('Sitemap settings is only available in a micro site context.'),
      ];
      return $form;
    }
    $form = parent::buildForm($form, $form_state);

    $form['site_id'] = [
      '#type' => 'value',
      '#value' => $site->id(),
    ];

    // Disable some options set at the master level.
    // @TODO make this configurable.
    $this->alterForm($form);

    $form['sitemap_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Check this option to enabled the sitemap. Uncheck to disable the sitemap page.'),
      '#default_value' => (bool) !empty($site->getData('micro_sitemap')),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $site_id = $form_state->getValue('site_id');
    $site = Site::load($site_id);
    if (!$site instanceof SiteInterface) {
      $form_state->setError($form, $this->t('An error occurs. Impossible to find the site entity.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_id = $form_state->getValue('site_id');
    $site = Site::load($site_id);
    if (!$site instanceof SiteInterface) {
      return;
    }
    $langcode = $this->getCurrentLanguageId();
    $data = [];

    if (empty($form_state->getValue('sitemap_enabled'))) {
      $site->setData('micro_sitemap', $data);
      $site->save();
    }
    else {
      $keys = $this->getOverridableKeys();
      $values = $form_state->getValues();
      // Save config.
      foreach ($keys as $key) {
        if (isset($values[$key])) {
          if ($key == 'order') {
            $order = $values[$key];
            asort($order);
            $data[$key] = $order;
          } else {
            $data[$key] = $values[$key];
          }
        }
      }
      $data['langcode'] = $langcode;
      // Ensure that main config will be override with empty value.
      $this->fillDataContent($data);
      // Allow others modules to alter the data saved.
      $context = ['site' => $site];
      $this->moduleHandler->alter('micro_site_map_data', $data, $context);
      $site->setData('micro_sitemap', $data);
      $site->save();
    }
  }

  protected function getCurrentLanguageId() {
    return \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * Gets the site negotiator.
   *
   * @return \Drupal\micro_site\SiteNegotiatorInterface
   *   The site negotiator.
   */
  protected function getNegotiator() {
    if (!$this->negotiator) {
      $this->negotiator = \Drupal::service('micro_site.negotiator');
    }
    return $this->negotiator;
  }

  /**
   * Sets the site negotiator for this handler.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface
   *   The site negotiator.
   *
   * @return $this
   */
  protected function setNegotiator(SiteNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
    return $this;
  }

  protected function fillDataContent(&$data) {
    $all_menus_disabled = $this->getAllMenusDisabled();
    $data['show_menus'] = $data['show_menus'] + $all_menus_disabled;
    $all_vocabularies_disabled = $this->getAllVocabulariesDisabled();
    $data['show_vocabularies'] = $data['show_vocabularies'] + $all_vocabularies_disabled;
  }

  /**
   * Helper to get all menus disabled.
   *
   * @return array
   */
  protected function getAllMenusDisabled() {
    if (is_null($this->menusDisabled)) {
      $result= [];
      $menus = Menu::loadMultiple();
      foreach ($menus as $id => $menu) {
        $result[$id] = 0;
      }
      $this->menusDisabled = $result;
    }

    return $this->menusDisabled;
  }

  /**
   * Helper to get all vocabularies disabled.
   *
   * @return array
   */
  protected function getAllVocabulariesDisabled() {
    if (is_null($this->vocabulariesDisabled)) {
      $result = [];
      $vocabularies = Vocabulary::loadMultiple();
      foreach ($vocabularies as $id => $vocabulary) {
        $result[$id] = 0;
      }
      $this->vocabulariesDisabled = $result;
    }

    return $this->vocabulariesDisabled;
  }

  /**
   * Helper function to get all menus related to a micro site.
   *
   * @return \Drupal\system\MenuInterface[]
   */
  protected function getMenus() {
    $active_site = $this->getNegotiator()->getActiveSite();
    $site_menus = [];
    if (!$active_site instanceof SiteInterface) {
      return $site_menus;
    }
    $menus = Menu::loadMultiple();
    /** @var \Drupal\system\MenuInterface $menu */
    foreach ($menus as $id => $menu) {
      $menu_site_id = $menu->getThirdPartySetting('micro_menu', 'site_id');
      if ($menu_site_id == $active_site->id()) {
        $site_menus[$id] = $menu;
      }
    }
    return $site_menus;
  }

  /**
   * Helper function to get all vocabularies related to a micro site.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary[]
   */
  protected function getVocabularies() {
    $active_site = $this->getNegotiator()->getActiveSite();
    $site_vocabularies = [];
    if (!$active_site instanceof SiteInterface) {
      return $site_vocabularies;
    }
    /** @var \Drupal\micro_site\Entity\SiteTypeInterface $site_type */
    $site_type = $active_site->type->entity;
    $site_type_vocabularies = $site_type->getVocabularies();

    $vocabularies = Vocabulary::loadMultiple();
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    foreach ($vocabularies as $id => $vocabulary) {
      $vocabulary_site_id = $vocabulary->getThirdPartySetting('micro_taxonomy', 'site_id');
      if ($vocabulary_site_id == $active_site->id()) {
        $site_vocabularies[$id] = $vocabulary;
      }
      elseif (in_array($id, $site_type_vocabularies)) {
        $site_vocabularies[$id] = $vocabulary;
      }
    }
    return $site_vocabularies;
  }

  protected function getKeys() {
    $keys = [
      'page_title',
      'message',
      'show_front',
      'show_titles',
      'show_menus',
      'show_menus_hidden',
      'show_vocabularies',
      'show_description',
      'show_count',
      'vocabulary_show_links',
      'vocabulary_depth',
      'term_threshold',
      'forum_threshold',
      'rss_front',
      'show_rss_links',
      'rss_taxonomy',
      'css',
      'order',
    ];
    if ($this->moduleHandler->moduleExists('book')) {
      $keys[] = 'show_books';
      $keys[] = 'books_expanded';
    }
    return $keys;
  }

  protected function getNotOverridableKeys() {
    $keys = [
      'page_title',
      'message',
      'show_count',
      'show_description',
      'term_threshold',
      'forum_threshold',
      'rss_front',
      'rss_taxonomy',
      'css',
    ];
    $show_rss_links = $this->configFactory->get('sitemap.settings')->getOriginal('show_rss_links', FALSE);
    if (empty($show_rss_links)) {
      $keys[] = 'show_rss_links';
    }
    return $keys;
  }

  protected function getOverridableKeys() {
    return array_diff($this->getKeys(), $this->getNotOverridableKeys());
  }

  /**
   * Helper function to hide some elements not configurable per site.
   * @TODO Make this configurable.
   *
   * @param $form
   */
  protected function alterForm(&$form) {
    $show_rss_links = $this->configFactory->get('sitemap.settings')->getOriginal('show_rss_links', FALSE);

    $form['page_title']['#access'] = FALSE;
    $form['message']['#access'] = FALSE;
    $form['sitemap_options']['sitemap_rss_options']['rss_front']['#access'] = FALSE;
    // Do not allow to set rss link if the master do not do it.
    if (empty($show_rss_links)) {
      $form['sitemap_options']['sitemap_rss_options']['#access'] = FALSE;
      $form['sitemap_options']['sitemap_rss_options']['show_rss_links']['#access'] = FALSE;
    }
    $form['sitemap_options']['sitemap_rss_options']['rss_taxonomy']['#access'] = FALSE;
    $form['sitemap_options']['sitemap_css_options']['#access'] = FALSE;
    $form['sitemap_options']['sitemap_css_options']['css']['#access'] = FALSE;

    if ($this->moduleHandler->moduleExists('forum')) {
      $form['sitemap_forum_options']['#access'] = FALSE;
      $form['sitemap_forum_options']['forum_threshold']['#access'] = FALSE;
    }
    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $form['sitemap_taxonomy_options']['show_description']['#access'] = FALSE;
      $form['sitemap_taxonomy_options']['show_count']['#access'] = FALSE;
      $form['sitemap_taxonomy_options']['term_threshold']['#access'] = FALSE;
    }
  }

}
