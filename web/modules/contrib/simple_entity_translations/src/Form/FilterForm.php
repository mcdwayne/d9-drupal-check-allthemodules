<?php

namespace Drupal\simple_entity_translations\Form;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FilterForm.
 */
class FilterForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * EntityTranslateForm constructor.
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_entity_translations_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityTypeInterface $entityType = NULL) {
    $form['#tree'] = TRUE;
    $form['filters'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Filters'),
      '#attributes' => ['class' => ['form--inline']],
    ];

    $defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();

    if (isset($entityType)) {
      $form['filters']['entity_type'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Entity filters'),
        '#attributes' => ['style' => 'float: none;'],
      ];

      foreach ($entityType->getKeys() as $key => $name) {
        switch ($key) {
          case 'label':
            $form['filters']['entity_type'][$key] = [
              '#type' => 'textfield',
              '#title' => $this->t(ucfirst($name)),
              '#default_value' => $_SESSION['simple_entity_translation_filter']['entity_type'][$key] ?? '',
            ];
            break;

          case 'published':
            $form['filters']['entity_type'][$key] = [
              '#type' => 'select',
              '#title' => $this->t(ucfirst($name)),
              '#options' => [
                0 => $this->t('Unpublished'),
                1 => $this->t('Published'),
              ],
              '#empty_value' => -1,
              '#default_value' => $_SESSION['simple_entity_translation_filter']['entity_type'][$key] ?? -1,
            ];
            break;
        }
      }
    }

    $form['filters']['source'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Source language'),
      '#description' => $this->t('The language of entities in first column.'),
      '#default_value' => $_SESSION['simple_entity_translation_filter']['source'] ?? $defaultLangcode,
    ];

    $form['filters']['target'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Target language'),
      '#description' => $this->t('The language of entities in second column.'),
      '#default_value' => $_SESSION['simple_entity_translation_filter']['target'] ?? $defaultLangcode,
    ];

    $form['filters']['actions'] = [
      '#type' => 'actions',
    ];

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    if (empty($_SESSION['simple_entity_translation_filter'])) {
      $this->messenger()->addWarning($this->t('Please select source and target languages.'));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $sourceLangcode = $form_state->getValue(['filters', 'source']);
    $targetLangcode = $form_state->getValue(['filters', 'target']);
    if ($sourceLangcode == $targetLangcode) {
      $form_state->setError($form['filters']['source'], $this->t('Source and target languages must be different!'));
      $form_state->setError($form['filters']['target'], $this->t('Source and target languages must be different!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('filters');
    unset($values['actions']);
    $_SESSION['simple_entity_translation_filter'] = $values;
    $form_state->setRebuild();
  }

}
