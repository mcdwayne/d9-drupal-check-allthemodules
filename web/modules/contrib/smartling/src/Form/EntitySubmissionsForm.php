<?php

/**
 * @file
 * Contains \Drupal\smartling\Form\EntitySubmissionsForm.
 */

namespace Drupal\smartling\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\smartling\Entity\SmartlingSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smartling\ApiWrapper\SmartlingApiWrapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manages entity submissions for entity.
 */
class EntitySubmissionsForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The submission entity storage.
   *
   * @var \Drupal\smartling\SubmissionStorageInterface
   */
  protected $storage;

  /**
   * Smartling API Wrapper.
   *
   * @var \Drupal\smartling\ApiWrapper\SmartlingApiWrapper
   */
  protected $apiWrapper;

  /**
   * Constructs a AccountInfoSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The submission entity storage.
   * @param \Drupal\smartling\ApiWrapper\SmartlingApiWrapper
   *   Smartling API Wrapper.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, EntityStorageInterface $storage, SmartlingApiWrapper $api_wrapper) {
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->storage = $storage;
    $this->apiWrapper = $api_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('entity.manager')->getStorage('smartling_submission'),
      $container->get('smartling.api_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartling_entity_submissions_form';
  }

  /**
   * Loads existing submissions of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get submissions.
   *
   * @return \Drupal\smartling\SmartlingSubmissionInterface[]
   */
  protected function loadEntitySubmissions(EntityInterface $entity) {
    return $this->storage->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
  }

  /**
   * Returns entity from request.
   *
   * @todo Move to proper place.
   * @see \Drupal\smartling\Controller\SmartlingController::getEntityFromRequest()
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity to translate.
   */
  protected function getEntityFromRequest(Request $request) {
    $entity_type_id = $request->get('entity_type_id');
    return $request->get($entity_type_id);
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntityFromRequest($request);
    // Entity always in negotiated language, Smartling needs source language.
    $entity = $entity->getUntranslated();
    // Store entity for submission.
    $form_state->setTemporaryValue('entity', $entity);

    $url = Url::fromRoute('entity.configurable_language.collection');

    $languages = $this->languageManager->getLanguages();
    // Exclude entity source language.
    unset($languages[$entity->language()->getId()]);

    $options = [];
    $existing = $this->loadEntitySubmissions($entity);
    foreach ($existing as $id => $submission) {
      $target_lang = $submission->get('target_language')->value;
      $options[$id] = [
        'translation' => (isset($languages[$target_lang])) ? $languages[$target_lang]->getName() : '',
        // @todo Display cool progress element.
        'progress' => $submission->get('progress')->value . ' %',
        'status' => ['data' => $submission->get('status')->view(['label' => 'visually_hidden'])],
      ];
      // Exclude from languages for translation.
      unset($languages[$submission->get('target_language')->value]);
    }

    // Display the rest of language options.
    foreach ($languages as $id => $language) {
      $options['new--' . $id] = [
        'translation' => $language->getName(),
        'progress' => 0 . ' %',
        // @todo Rename this status.
        'status' => $this->t('To be created'),
      ];
    }

    // @todo Check that SDK language mappings configured.
    $config = $this->config('smartling.settings');
    $enabled_languages = $config->get('account_info.enabled_languages');

    $form['submissions'] = [
      '#type' => 'tableselect',
      '#header' => [
        'translation' => $this->t('Translation'),
        'progress' => $this->t('Progress'),
        'status' => $this->t('Status'),
      ],
      '#options' => $options,
      '#empty' => [
        '#type' => 'link',
        '#title' => $this->t('At least two languages must be enabled. Please change language settings.'),
        '#url' => $url,
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send to Smartling'),
      '#submit' => ['::send'],
    ];
    $form['actions']['download'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download translation'),
      '#submit' => ['::download'],
    ];
    // Make form rebuildable.
    $form['#submit'][] = '::submitForm';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Build array of entities to process.
    $submissions = $form_state->getValue('submissions');
    $submissions = array_filter($submissions);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getTemporaryValue('entity');
    $existing = $this->loadEntitySubmissions($entity);

    // Load or create submissions for the entity.
    $target = [];
    foreach ($submissions as $id) {
      if (strpos($id, 'new--') === 0) {
        // Remove prefix for language to create.
        $lang_code =  substr($id, 5);
        // @todo Convert to load multiple and add validation for content entity.
        $submission = SmartlingSubmission::getFromDrupalEntity($entity, $lang_code);
        $submission->save();
        $target[$submission->id()] = $submission;
      }
      elseif (isset($existing[$id])) {
        $target[$id] = $existing[$id];
      }
    }
    $form_state->setTemporaryValue('submissions', $target);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function send(array &$form, FormStateInterface $form_state) {
    $manager = \Drupal::entityManager();
    $entity = $form_state->getTemporaryValue('entity');
    $entity_type_id = $entity->getEntityTypeId();
    if ($manager->hasHandler($entity_type_id, 'smartling')) {
      /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
      $handler = $manager->getHandler($entity_type_id, 'smartling');
      /** @var \Drupal\smartling\SmartlingSubmissionInterface[] $submissions */
      $submissions = $form_state->getTemporaryValue('submissions');
      $locales = [];
      foreach ($submissions as $submission) {
        $locales[] = $submission->get('target_language')->value;
      }
      if ($handler->uploadTranslation($entity, $submission->getFileName(), $locales)) {
        drupal_set_message('@TODO report about upload');
      }
      else {
        drupal_set_message('@TODO report about failed upload');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function download(array &$form, FormStateInterface $form_state) {
    $manager = \Drupal::entityManager();
    $entity = $form_state->getTemporaryValue('entity');
    $entity_type_id = $entity->getEntityTypeId();
    if ($manager->hasHandler($entity_type_id, 'smartling')) {
      /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
      $handler = $manager->getHandler($entity_type_id, 'smartling');
      /** @var \Drupal\smartling\SmartlingSubmissionInterface[] $submissions */
      $submissions = $form_state->getTemporaryValue('submissions');

      $downloaded = $failed = [];
      foreach ($submissions as $submission) {
        if ($handler->downloadTranslation($submission)) {
          $downloaded[] = $submission->get('target_language')->value;
        }
        else {
          $failed[] = $submission->get('target_language')->value;
        }
      }
      // @todo Write better messages.
      if ($failed) {
        $message = $this->t('Failed to download for following languages: @locales', [
          '@locales' => implode(', ', $failed),
        ]);
        drupal_set_message($message, 'error');
      }

      if ($downloaded) {
        $message = $this->t('Translations downloaded for following languages: @locales', [
          '@locales' => implode(', ', $downloaded),
        ]);
        drupal_set_message($message);
      }
    }
  }

}
