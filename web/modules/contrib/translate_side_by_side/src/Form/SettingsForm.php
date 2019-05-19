<?php

namespace Drupal\translate_side_by_side\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Renderer;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The variable containing the language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The variable containing the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The variable containing the entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The variable containing the menu tree link.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuLinkTree;

  /**
   * The variable containing the renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Source language.
   *
   * @var string
   */
  protected $sourceLanguage;

  /**
   * Target language.
   *
   * @var string
   */
  protected $targetLanguage;

  /**
   * Dependency injection through the constructor.
   *
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The language service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field service.
   * @param \Drupal\Core\Menu\MenuLinkTree $menuLinkTree
   *   The menu link tree  service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(LanguageManager $languageManager,
  EntityTypeManager $entityTypeManager,
  EntityFieldManager $entityFieldManager,
  MenuLinkTree $menuLinkTree,
  Renderer $renderer) {
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->menuLinkTree = $menuLinkTree;
    $this->renderer = $renderer;
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('language_manager'),
    $container->get('entity_type.manager'),
    $container->get('entity_field.manager'),
    $container->get('menu.link_tree'),
    $container->get('renderer'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'translate_side_by_side.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config.translate_side_by_side'];
  }

  /**
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['selection'] = [
      '#type' => 'fieldset',
    ];
    $form['selection']['source'] = [
      '#title' => $this->t('Source language'),
      '#type' => 'language_select',
      '#languages' => Language::STATE_CONFIGURABLE,
    ];
    $form['selection']['target'] = [
      '#title' => $this->t('Target language'),
      '#type' => 'language_select',
      '#languages' => Language::STATE_CONFIGURABLE,
    ];
    $form['selection']['filluntranslated'] = [
      '#title' => $this->t('Fill untranslated with source'),
      '#type' => 'checkbox',
    ];
    $form['selection']['load'] = [
      '#type' => 'button',
      '#value' => $this->t('Load'),
    ];

    $this->sourceLanguage = ($form_state->getValue('source') != '') ? ($form_state
      ->getValue('source')) : ($this->languageManager->getDefaultLanguage()
      ->getId());
    $this->targetLanguage = ($form_state->getValue('target') != '') ? ($form_state
      ->getValue('target')) : ($this->languageManager->getDefaultLanguage()
      ->getId());

    $form['menus'] = [
      '#value' => $this->t('Menus'),
      '#type' => 'html_tag',
      '#tag' => 'h3',
    ];
    $form = $this->buildFormMenu($form, $form_state);

    $form['nodes'] = [
      '#value' => $this->t('Nodes'),
      '#type' => 'html_tag',
      '#tag' => 'h3',
    ];
    $form = $this->buildFormEntity($form, $form_state, 'node', 'title', 'nid');

    $form['blocks'] = [
      '#value' => $this->t('Blocks'),
      '#type' => 'html_tag',
      '#tag' => 'h3',
    ];
    $form = $this->buildFormEntity($form, $form_state, 'block_content', 'info', 'id');

    $form['taxonomy'] = [
      '#value' => $this->t('Taxonomy'),
      '#type' => 'html_tag',
      '#tag' => 'h3',
    ];
    $form = $this->buildFormTaxonomy($form, $form_state);

    $form = parent::buildForm($form, $form_state);

    // Remove submit button.
    unset($form['actions']['submit']);
    return $form;
  }

  /**
   * Build entity form.
   */
  private function buildFormEntity(
  array $form,
  FormStateInterface $form_state,
  $entityType,
  $sortBy,
  $idKey) {
    $nids = $this->entityTypeManager->getStorage($entityType)->getQuery()
      ->condition('langcode', 'zzz', '<>')
      ->condition('langcode', 'zxx', '<>')
      ->sort($sortBy, 'ASC', 'en')
      ->execute();
    $entities = $this->entityTypeManager->getStorage($entityType)->loadMultiple($nids);
    foreach ($entities as $oneEntity) {
      if ($oneEntity->hasTranslation($this->sourceLanguage)) {
        $original = $oneEntity->getTranslation($this->sourceLanguage);
      }
      else {
        $original = $oneEntity;
      }
      if ($oneEntity->hasTranslation($this->targetLanguage)) {
        $translation = $oneEntity->getTranslation($this->targetLanguage);
      }
      else {
        $translation = NULL;
      }

      $fieldlist = [];
      $fields_in_display = $this->entityTypeManager
        ->getStorage('entity_view_display')
        ->load($entityType . '.' . $oneEntity->bundle() . '.default')
        ->getComponents();
      foreach ($fields_in_display as $field_name => $field_definition) {
        if (isset($field_definition['type'])) {
          $fieldlist = $this->retrieveField($fieldlist, $field_definition['type'], $original, $translation, $field_name, $oneEntity, $field_definition['weight'], $form_state);
        }
      }
      ksort($fieldlist);
      // Get fields that are not in default display.
      $allfields = $oneEntity->getFieldDefinitions();
      foreach ($allfields as $field_name => $field_definition) {
        if (!in_array($field_name, array_keys($fields_in_display))) {
          $fieldlist = $this->retrieveField($fieldlist, $field_definition->getType(), $original, $translation, $field_name, $oneEntity, max(array_keys($fieldlist)) + 1, $form_state);
        }
      }
      $form['tablet' . $entityType][] = $this->buildFormTable($form, $form_state,
      $oneEntity->get($sortBy)->value . ' ' . str_replace('/' .
      $this->languageManager->getDefaultLanguage()->getId(), '',
      $oneEntity->toUrl()->toString()), $entityType . ' ID: ' . $oneEntity->get($idKey)->value,
      $fieldlist, $entityType . $oneEntity->get($idKey)->value);

    }
    return $form;
  }

  /**
   * Build field.
   */
  private function retrieveField($fieldlist, $field_type, $original, $translation, $field_name, $oneEntity, $field_display_weight, $form_state) {
    if (!($field_name == 'title' || $field_name == 'body' || $field_name == 'info' ||
      substr($field_name, 0, 6) === 'field_')) {
      return $fieldlist;
    }
    if ($original->get($field_name)->count() == 0) {
      // Field_group exception.
      $entlist = [$original->get($field_name)];
    }
    else {
      $entlist = $original->get($field_name);
    }

    foreach ($entlist as $onekey => $onevalue) {

      $txtattr = [];
      if ($field_type == 'image') {
        $txtattr[] = 'alt';
        $txtattr[] = 'title';
      }
      elseif ($field_type == 'link') {
        $txtattr[] = 'title';
      }
      elseif (strpos($field_type, 'string') !== FALSE
      || strpos($field_type, 'text') !== FALSE) {
        $txtattr[] = 'value';
      }
      elseif ($field_type === 'entity_reference_revisions_entity_view' || $field_type === 'entity_reference_entity_view' || $field_type === 'paragraph_summary'
      || $field_type === 'field_collection_list' || $field_type === 'field_collection_items') {
        $par_fieldlist = $this->retrieveParagraphs([], $field_type, $original, $translation, $field_name, $oneEntity, $field_display_weight, $form_state);
        $fieldlist[$field_display_weight * 1000 + $onekey] = [
          'f' => $field_name,
          'a' => $par_fieldlist,
        ];
        return $fieldlist;
      }

      foreach ($txtattr as $onetxtattrkey => $onetxtattr) {
        if ($translation !== NULL && $translation->get($field_name)
          ->getValue()[$onekey][$onetxtattr] !== NULL) {
          $field_val = $translation->get($field_name)
            ->getValue()[$onekey][$onetxtattr];
        }
        elseif ($form_state->getValue('filluntranslated') === 0) {
          $field_val = '';
        }
        else {
          $field_val = $original->get($field_name)
            ->getValue()[$onekey][$onetxtattr];
        }

        $fieldlist[$field_display_weight * 10000 + $onekey * 100 + $onetxtattrkey] = [
          'f' => $field_name,
          'n' => (($onetxtattr !== 'value') ? (' (' . $onetxtattr . ')') : ('')),
          's' => (isset($original->get($field_name)->getValue()[$onekey])) ? ($original->get($field_name)->getValue()[$onekey][$onetxtattr]) : (''),
          't' => $field_val,
        ];
      }
    }
    return $fieldlist;
  }

  /**
   * Build paragraphs field.
   */
  private function retrieveParagraphs($par_fieldlist, $field_type, $original, $translation, $field_name, $oneEntity, $field_display_weight, $form_state) {
    $par_fieldlist = [];
    foreach ($original->get($field_name)->referencedEntities() as $oneparkey => $oneparvalue) {
      if ($oneparvalue->hasTranslation($this->sourceLanguage)) {
        $par_original = $oneparvalue->getTranslation($this->sourceLanguage);
      }
      else {
        $par_original = NULL;
      }
      if ($oneparvalue->hasTranslation($this->targetLanguage)) {
        $par_translation = $oneparvalue->getTranslation($this->targetLanguage);
      }
      else {
        $par_translation = NULL;
      }
      $par_fields = $oneparvalue->getFieldDefinitions();
      foreach ($par_fields as $par_field_name => $par_field_definition) {
        if (substr($par_field_name, 0, 6) === 'field_') {
          $par_field_display = $this->entityTypeManager
            ->getStorage('entity_view_display')
            ->load($original->get($field_name)->getFieldDefinition()->getSetting('target_type') . '.' . $oneparvalue->bundle() . '.default')
            ->getComponent($par_field_name);
          $par_field_display_weight = ($oneparkey + 1) * 10 + (($par_field_display !== NULL) ? ($par_field_display['weight'] + 1) : (0));
          $par_fieldlist = $this->retrieveField($par_fieldlist, $par_field_definition->getType(), $par_original, $par_translation, $par_field_name, $oneparvalue, $par_field_display_weight, $form_state);
        }
      }
    }
    return $par_fieldlist;
  }

  /**
   * Build menu form.
   */
  private function buildFormMenu(array $form, FormStateInterface $form_state) {
    $mids = $this->entityTypeManager->getStorage('menu')->getQuery()
      ->condition('langcode', 'zzz', '<>')
      ->condition('langcode', 'zxx', '<>')
      ->condition('id', 'account', '<>')
      ->condition('id', 'admin', '<>')
      ->condition('id', 'devel', '<>')
      ->condition('id', 'tools', '<>')
      ->sort('label', 'ASC', 'en')
      ->execute();
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple($mids);

    foreach ($menus as $onemenu) {

      $fieldlist = [];

      $parameters = new MenuTreeParameters();
      $parameters->setMinDepth(0)->setMaxDepth(4)->onlyEnabledLinks();
      $tree = $this->menuLinkTree->load($onemenu->get('id'), $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ['callable' => 'menu.default_tree_manipulators:flatten'],
      ];
      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      foreach ($tree as $element) {
        $linkDef = $element->link->getPluginDefinition();

        /* empty entity_id */
        if (empty($linkDef['metadata']['entity_id'])) {
          continue;
        }

        $entity_id = $linkDef['metadata']['entity_id'];

        $storage = $this->entityTypeManager
          ->getStorage('menu_link_content');

        $entity = $storage->load($entity_id);

        if ($entity->isTranslatable() && $entity->hasTranslation($this->sourceLanguage)) {
          $original = $entity->getTranslation($this->sourceLanguage);
        }
        else {
          $original = $entity;
        }

        if ($entity->isTranslatable() && $entity->hasTranslation($this->targetLanguage)) {
          $translation = $entity->getTranslation($this->targetLanguage);
        }
        else {
          $translation = NULL;
        }
        if ($translation !== NULL) {
          $menu_val = $entity->getTranslation($this->targetLanguage)->getTitle() . '<br>' . $entity->getTranslation($this->targetLanguage)->getDescription();
        }
        elseif ($form_state->getValue('filluntranslated') === 0) {
          $menu_val = '';
        }
        else {
          $menu_val = $original->getTitle() . '<br>' . $original->getDescription();
        }

        $fieldlist[] = [
          'f' => $entity_id,
          's' => $original->getTitle(),
          't' => $menu_val,
        ];
      }

      $form['tabletmenu'][] = $this->buildFormTable($form, $form_state,
      $onemenu->get('label'), 'machine name: ' . $onemenu->get('id'),
      $fieldlist, 'menu' . $onemenu->get('id'));
    }
    return $form;
  }

  /**
   * Build taxonomy form.
   */
  private function buildFormTaxonomy(array $form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();

    foreach ($vocabularies as $onevocabulary) {
      $tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->condition('vid', $onevocabulary->get('vid'))
        ->execute();
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($tids);

      foreach ($terms as $oneterm) {
        $fieldlist = [];
        if ($oneterm->isTranslatable() && $oneterm->hasTranslation($this->sourceLanguage)) {
          $original = $oneterm->getTranslation($this->sourceLanguage);
        }
        else {
          $original = $oneterm;
        }
        if ($oneterm->isTranslatable() && $oneterm->hasTranslation($this->targetLanguage)) {
          $translation = $oneterm->getTranslation($this->targetLanguage);
        }
        else {
          $translation = NULL;
        }
        if ($translation !== NULL) {
          $name_val = $oneterm->getTranslation($this->targetLanguage)->getName();
          $desc_val = $oneterm->getTranslation($this->targetLanguage)->getDescription();
        }
        elseif ($form_state->getValue('filluntranslated') === 0) {
          $name_val = '';
          $desc_val = '';
        }
        else {
          $name_val = $original->getName();
          $desc_val = $original->getDescription();
        }

        $fieldlist[] = [
          'f' => 'name',
          's' => $original->getName(),
          't' => $name_val,
        ];
        $fieldlist[] = [
          'f' => 'description',
          's' => $original->getDescription(),
          't' => $desc_val,
        ];

        $form['tablettax'][] = $this->buildFormTable($form, $form_state,
        $onevocabulary->get('name'), 'machine name: ' . $onevocabulary->get('vid') . ', ID: ' . $oneterm->id(),
        $fieldlist, 'taxonomy' . $onevocabulary->get('vid') . $oneterm->id());

      }

    }
    return $form;
  }

  /**
   * Build form table for all types above.
   */
  private function buildFormTable(array $form,
  FormStateInterface $form_state,
  $name1,
  $name2,
  array $fieldlist,
  $entityType) {
    $table = [
      '#type' => 'table',
      '#caption' => [
        '#markup' => '<strong>' . $name1 . ':</strong>  <small>(' . $name2 . ')</small>',
        '#attributes' => ['align' => 'left'],
      ],
      '#header' => [
        ['data' => 'ID', 'width' => '20%'],
        ['data' => $this->languageManager->getLanguage($this->sourceLanguage)->getName(), 'width' => '40%'],
        [
          'data' => $this->languageManager->getLanguage($this->targetLanguage)->getName(),
          'width' => '40%',
        ],
      ],
      '#attributes' => [
        'border' => '1',
        'width' => '100%',
        'class' => ['tsbstable', 'tsbstable_' . $entityType],
      ],
    ];
    foreach ($fieldlist as $k => $onefieldlist) {
      $table = $this->buildFormRow($table, $k, $onefieldlist, $entityType);
    }
    $formtable['table'] = $table;
    $formtable['br'] = [
      '#type' => 'html_tag',
      '#tag' => 'br',
    ];
    return $formtable;
  }

  /**
   * Build form table row.
   */
  private function buildFormRow($table, $k, $onefieldlist, $entityType) {
    if (isset($onefieldlist['a'])) {
      ksort($onefieldlist['a']);
      foreach ($onefieldlist['a'] as $ks => $onefieldlists) {
        $table = $this->buildFormRow($table, $ks, $onefieldlists, $entityType);
      }
      return $table;
    }
    $row = [];
    $row['valign'] = 'top';
    $row['class'][] = 'tsbstable_' . $entityType . '_' . $onefieldlist['f'];

    $r1html = [];
    $r1html['#markup'] = '<small>' . $onefieldlist['f'] . ((isset($onefieldlist['n'])) ? ($onefieldlist['n']) : ('')) . '</small>';
    $row['data'][] = ['data' => $this->renderer->render($r1html)];

    $r2html = [];
    $r2html['#markup'] = $onefieldlist['s'];
    $row['data'][] = [
      'data' => $this->renderer->render($r2html),
      'lang' => $this->sourceLanguage,
    ];

    $r3html = [];
    $r3html['#markup'] = $onefieldlist['t'];
    $row['data'][] = [
      'data' => $this->renderer->render($r3html),
      'lang' => $this->targetLanguage,
    ];

    $table['#rows'][] = $row;
    return $table;
  }

}
