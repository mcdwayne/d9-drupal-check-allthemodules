<?php

namespace Drupal\courier_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\courier\CourierTokenElementTrait;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Form controller for courier_template_collection.
 */
class EditCollectionTemplates extends FormBase {

  use CourierTokenElementTrait;

  /**
   * The courier_template_collection entity.
   *
   * @var \Drupal\courier\TemplateCollectionInterface
   */
  protected $entity;

  /**
   * Courier identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $courierChannelManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Template entities and their form display controllers.
   *
   * @var array
   */
  protected $templateData;

  /**
   * The current request object.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Enabled channels.
   *
   * @var array
   */
  protected $enabledChannels;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $courierChannelManager
   *   Courier identity channel manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    IdentityChannelManagerInterface $courierChannelManager,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    RequestStack $requestStack,
    ConfigFactoryInterface $configFactory
  ) {
    $this->courierChannelManager = $courierChannelManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->enabledChannels = $configFactory->get('courier.settings')->get('channel_preferences');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.identity_channel'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit-collection-templates';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $courier_template_collection = NULL) {
    $this->collection = $this->entityTypeManager->getStorage('courier_template_collection')->load($courier_template_collection);
    $translations = FALSE;
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $languages = $this->languageManager->getLanguages();

    foreach (array_keys($this->courierChannelManager->getChannels()) as $entity_type_id) {
      if (!in_array($entity_type_id, $this->enabledChannels['user'])) {
        continue;
      }
      $template = $this->collection->getTemplate($entity_type_id);

      if (!$template) {
        $template = $this->entityTypeManager
          ->getStorage($entity_type_id)
          ->create();
      }

      if ($template instanceof TranslatableInterface && $template->isTranslatable()) {
        if (!$template->hasTranslation($current_language)) {
          $template->addTranslation($current_language, []);
        }
        $template = $template->getTranslation($current_language);
        if (count($languages) > 1) {
          $translations = TRUE;
        }
      }

      $formDisplay = $this->entityTypeManager->getStorage('entity_form_display')->create([
        'targetEntityType' => $template->getEntityTypeId(),
        'bundle' => $template->bundle(),
        'mode' => 'default',
        'status' => TRUE,
      ]);

      // Set the template and form display as global object properties.
      $this->templateData[$entity_type_id] = [
        'template' => $template,
        'form_display' => $formDisplay,
      ];

      $template_type = $template->getEntityType();
      $form[$entity_type_id] = [
        '#type' => 'details',
        '#tree' => TRUE,
        '#open' => TRUE,
        '#title' => $template_type->getLabel(),
        '#parents' => [$entity_type_id],
      ];

      $formDisplay->buildForm($template, $form[$entity_type_id], $form_state);
    }

    $collectionClone = clone $this->collection;

    $tokens = [];
    $bundles = $this->collection->referenceable_bundles->getValue();
    if (!empty($bundles)) {
      foreach ($bundles as $item) {
        $tokens[$item['entity_type']] = $item['entity_type'];
      }
    }
    $form['tokens'] = [
      '#type' => 'container',
    ];
    $form['tokens']['list'] = $this->courierTokenElement($tokens);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save templates'),
    ];

    // Language links for user convenience.
    if ($translations) {
      $form['language_selector'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Edit templates in other languages'),
        '#items' => [],
        '#weight' => -10,
      ];
      $request = clone $this->currentRequest;
      foreach ($languages as $language_id => $language) {
        $request->setLocale($language_id);
        $url = Url::createFromRequest($request);
        $url->setOption('language', $language);
        $link = Link::fromTextAndUrl($language->getName(), $url);
        $form['language_selector']['#items'][] = $link->toString();
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach (array_keys($this->courierChannelManager->getChannels()) as $entity_type_id) {
      $form['#parents'] = [$entity_type_id];

      $this->templateData[$entity_type_id]['form_display']->validateFormValues(
        $this->templateData[$entity_type_id]['template'],
        $form,
        $form_state
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (array_keys($this->courierChannelManager->getChannels()) as $entity_type_id) {
      $form['#parents'] = [$entity_type_id];

      $formDisplay = $this->templateData[$entity_type_id]['form_display'];
      $template = $this->templateData[$entity_type_id]['template'];
      $formDisplay->extractFormValues($template, $form, $form_state);
      $template->save();
      $this->collection->setTemplate($template);
    }
    $this->collection->save();

  }

}
