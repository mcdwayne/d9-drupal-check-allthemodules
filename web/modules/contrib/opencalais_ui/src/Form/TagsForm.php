<?php

namespace Drupal\opencalais_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\opencalais_ui\CalaisService;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto tag content with the Open Calais service.
 */
class TagsForm extends FormBase {

  /**
   * The Open Calais service.
   *
   * @var \Drupal\opencalais_ui\CalaisService
   */
  protected $calaisService;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\opencalais_ui\CalaisService $calais_service
   *   The Open Calais service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CalaisService $calais_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $this->config('opencalais_ui.settings');
    $this->calaisService = $calais_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('opencalais_ui.calais_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opencalais_ui_tags';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    // If no API key has been set, show an error message otherwise build the
    // form.
    $type = $node->getType();
    $node_type = NodeType::load($type);
    if (!$this->calaisService->apiKeySet()) {
      drupal_set_message(t('No API key has been set. Click <a href=":key_page">here</a> to set it.', [
        ':key_page' => Url::fromRoute('opencalais_ui.general_settings')
          ->toString()
      ]), 'warning');
    }
    else if (!$node_type->getThirdPartySetting('opencalais_ui', 'field')) {
      drupal_set_message(t('No Open Calais field has been set. Click <a href=":key_page">here</a> to set it.', [
        ':key_page' => Url::fromRoute('entity.node_type.edit_form', ['node_type' => $type])
          ->toString()
      ]), 'warning');
    }
    else {
      // Get the view builder and build the node view in 'content'.
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $node_output = $view_builder->view($node);
      $form['content'] = $node_output;
      $form_state->set('entity', $node);

      // If 'analyse' is set, call the service and display the tags.
      if ($form_state->get('analyse')) {
        $form['open_calais'] = [
          '#type' => 'container',
          '#tree' => TRUE,
        ];
        $form['open_calais']['entities'] = [
          '#type' => 'details',
          '#title' => $this->t('Entities'),
          '#open' => TRUE,
        ];
        $form['open_calais']['aboutness_tags'] = [
          '#type' => 'details',
          '#title' => $this->t('Aboutness tags'),
          '#open' => TRUE,
        ];

        // Append all the text fields in an unique string.
        $text_types = [
          'text_with_summary',
          'text',
          'text_long',
          'list_string',
          'string',
        ];
        $text = '';
        $node = $form_state->get('entity');
        foreach ($node->getFieldDefinitions() as $field_name => $field_definition) {
          if (in_array($field_definition->getType(), $text_types)) {
            $text .= $node->get($field_name)->value;
          }
        };
        $result = $this->calaisService->analyze($text, $form_state->getValue('language'));

        // Build checkboxes for aboutness tags.
        $social_tags_wrapper = [
          '#type' => 'item',
          '#title' => $this->t('Social tags')
        ];
        $social_tags = $this->buildCheckBoxes($result['social_tags'], 'social_tags', 'importance');
        $form['open_calais']['aboutness_tags']['social_tags'] = array_merge($social_tags_wrapper, $social_tags);
        $topic_tags_wrapper = [
          '#type' => 'item',
          '#title' => $this->t('Topic tags')
        ];
        $topic_tags = $this->buildCheckBoxes($result['topic_tags'], 'topic_tags', 'score');
        $form['open_calais']['aboutness_tags']['topic_tags'] = array_merge($topic_tags_wrapper, $topic_tags);
        $industry_tags_wrapper = [
          '#type' => 'item',
          '#title' => $this->t('Industry tags')
        ];
        $industry_tags = $this->buildCheckBoxes($result['industry_tags'], 'industry_tags', 'relevance');
        $form['open_calais']['aboutness_tags']['industry_tags'] = array_merge($industry_tags_wrapper, $industry_tags);

        foreach ($result['entities'] as $key => $value) {
          $entities_wrapper = [
            '#type' => 'item',
            '#title' => $this->t($key)
          ];
          $entities = $this->buildCheckBoxes($value, 'entities', 'relevance');
          $form['open_calais']['entities'][$key] = array_merge($entities_wrapper, $entities);
        }
      }
      // Add a submit button. Give it a class for easy JavaScript targeting.
      $form['suggested_tags'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'opencalais-suggested-tags',
        ],
      ];
      $form['open_calais']['config'] = [
        '#type' => 'details',
        '#title' => $this->t('Configuration'),
        '#open' => FALSE,
      ];
      // If the language module is enabled then allow the user to select the
      // language that will be used to analyze the text.
      if (\Drupal::moduleHandler()->moduleExists('language')) {
        $languages = \Drupal::languageManager()->getLanguages();
        $language_options = [];
        foreach ($languages as $langcode => $language) {
          $language_options[$language->getName()] = $language->getName();
        }
        $form['open_calais']['config']['language'] = [
          '#type' => 'select',
          '#title' => $this->t('Language'),
          '#options' => $language_options,
          '#default_value' => \Drupal::languageManager()->getCurrentLanguage()->getName(),
          '#description' => $this->t('The language that will be used to analyze the node.'),
        ];
      }
      $form['actions'] = [
        '#type' => 'actions',
        '#weight' => 999,
      ];
      $form['actions']['save'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#button_type' => 'primary',
      ];
      $form['actions']['suggest_tags'] = [
        '#type' => 'submit',
        '#value' => t('Suggest Tags'),
        '#attributes' => ['class' => ['opencalais_submit']],
        '#submit' => [[get_class($this), 'addMoreSubmit']],
        '#ajax' => [
          'callback' => '::suggestTagsAjax',
          'wrapper' => 'opencalais-suggested-tags',
          'effect' => 'fade',
        ],
      ];
    }

    return $form;
  }

  /**
   * Ajax callback for the "Suggest tags" button.
   */
  public function suggestTagsAjax(array $form, FormStateInterface $form_state) {
    return $form['open_calais'];
  }

  /**
   * Submission handler for the "Suggest tags" button.
   */
  public function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $form_state->set('analyse', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Build checkboxes for aboutness tags.
   */
  public function buildCheckBoxes($result, $name, $relevance) {
    // If there are no options show a message of empty.
    if ($result === NULL) {
      $form['empty'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No tags returned.')
      ];
    }
    else {
      foreach ($result as $key => $value) {
        $form[$value['name']] = [
          '#type' => 'container'
        ];
        $form[$value['name']]['enable'] = [
          '#type' => 'checkbox',
          '#title' => $this->t($value['name']),
          '#title_display' => 'after',
        ];
        $form[$value['name']]['score'] = [
          '#type' => 'opencalais_ui_relevance_bar',
          '#tag_type' => $name,
          '#score' => $value[$relevance],
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_tags = $form_state->getValue('open_calais');
    $node = $form_state->get('entity');
    $type = NodeType::load($node->getType());
    $field = $type->getThirdPartySetting('opencalais_ui', 'field');
    $tids = [];
    foreach ($selected_tags['aboutness_tags'] as $tags_type_id => $tags) {
      foreach ($tags as $key => $value) {
        if ($value['enable'] == TRUE) {
          $values = [
            'name' => $key,
            'vid' => $tags_type_id,
          ];
          if (!$term = $this->entityTypeManager->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $key, 'vid' => $tags_type_id])) {
            $term = Term::create($values);
            $term->save();
          }
          else {
            $term = reset($term);
          }
          $tids[] = $term->id();
        }
      }
    };

    foreach ($selected_tags['entities'] as $key => $value) {
      foreach ($value as $entity_id => $entity_value) {
        if ($entity_value['enable'] == TRUE) {
          $key_id = $term = $this->entityTypeManager->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $key]);
          $values = [
            'name' => $entity_id,
            'vid' => 'markup_tags',
            'subclassof' => $key_id
          ];
          // If the term has been added already to the entity, skip it.
          if (!$term = $this->entityTypeManager->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $entity_id])) {
            $term = Term::create($values);
            $term->save();
          }
          else {
            $term = reset($term);
          }
          $tids[] = $term->id();
        }
      }
    };
    foreach ($node->get($field)->getValue() as $taxonomy_term) {
      if ($taxonomy_term['target_id']) {
        $tids[] = $taxonomy_term['target_id'];
      }
    }
    $node->$field = array_unique($tids);
    $node->save();
  }

}
