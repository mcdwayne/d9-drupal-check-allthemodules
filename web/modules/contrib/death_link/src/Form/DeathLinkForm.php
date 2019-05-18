<?php

namespace Drupal\death_link\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\death_link\Service\RedirectService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class DeathLinkForm.
 */
class DeathLinkForm extends EntityForm {

  use StringTranslationTrait;

  /**
   * The redirect service.
   *
   * @var \Drupal\death_link\Service\RedirectService
   */
  protected $redirectService;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\death_link\Service\RedirectService $redirectService
   *   The redirect service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation.
   */
  public function __construct(RedirectService $redirectService, EntityTypeManagerInterface $entityManager, ConfigFactoryInterface $configFactory, TranslationInterface $stringTranslation) {
    $this->redirectService = $redirectService;
    $this->entityManager = $entityManager;
    $this->configFactory = $configFactory;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('death_link.redirect'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $formState) {

    // Call parent form.
    $form = parent::form($form, $formState);

    // Get the redirect entity.
    /** @var \Drupal\death_link\Entity\DeathLinkInterface $deathLink */
    $deathLink = $this->getEntity();

    // Get the linkit profile.
    $linkitProfile = $this->getLinkitProfile();
    if (!$linkitProfile) {

      // Provide the label field.
      $form['no_linkit'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('No linkit profile provided'),
      ];

      // Provide the label field.
      $form['no_linkit']['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('Please provide a linkit profile before creating death links. Please configure @linkit.', [
          '@linkit' => Link::fromTextAndUrl($this->t('the linkit profile to use'), Url::fromRoute('entity.linkit_profile.collection'))->toString(),
        ]),
      ];

      return $form;
    }

    // Provide the label field.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $deathLink->label(),
      '#description' => $this->t("Label for the redirect."),
      '#required' => TRUE,
    ];

    // Provide the id field as a machine name.
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $deathLink->id(),
      '#machine_name' => [
        'exists' => '\Drupal\death_link\Entity\DeathLink::load',
      ],
      '#disabled' => !$deathLink->isNew(),
    ];

    // Provide the fromUri field.
    $form['from_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect From'),
      '#maxlength' => 255,
      '#default_value' => $deathLink->getFromUri(),
      '#description' => $this->t("The path to redirect. Requires the following format: /example"),
      '#required' => TRUE,
    ];

    $form['to_uri'] = [
      '#type' => 'linkit',
      '#title' => $this->t('Redirect To'),
      '#description' => t('Start typing to find content.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkitProfile,
      ],
      '#default_value' => $deathLink->getToUri(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $deathLink->status(),
    ];

    return $form;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $formState) {

    // Get the config.
    $config = $this->configFactory()->get('death_link.settings');
    $linkitProfile = $config->get('linkit_profile');

    // Make sure the linkit profile is set.
    if (!$linkitProfile) {
      return [];
    }

    return parent::actionsElement($form, $formState);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {
    $fromUri = $formState->getValue('from_uri');
    $toUri = $formState->getValue('to_uri');

    /** @var \Drupal\death_link\Entity\DeathLinkInterface $entity */
    $entity = $this->entity;
    $originalId = $entity->getOriginalId();

    /** @var \Drupal\death_link\Entity\DeathLinkInterface $originalRedirect */
    $originalRedirect = $originalId ? $this->entityManager->getStorage('death_link')->load($originalId) : FALSE;

    // Validate a unique from uri is passed.
    if (!$originalRedirect || $originalRedirect->getFromUri() !== $entity->getFromUri()) {
      if ($this->redirectService->getMatchingRedirect(trim($fromUri))) {
        $formState->setError($form['from_uri'], t('This redirect path already exists.'));
      }
    }

    // Validate a valid from uri is passed.
    if (preg_match('#^/#', $fromUri) !== 1) {
      $formState->setError($form['from_uri'], t('This redirect path is invalid. It should start with a slash ( / ).'));
    }

    // Validate a valid from uri is passed.
    if (preg_match('#^/#', $toUri) !== 1) {
      $formState->setError($form['to_uri'], t('The url to redirect to is invalid. It should start with a slash ( / ).'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {

    $fromUri = $formState->getValue('from_uri');
    $toUri = $formState->getValue('to_uri');

    /** @var \Drupal\death_link\Entity\DeathLinkInterface $deathLink */
    $deathLink = $this->getEntity();
    $this->entity->set('fromUri', $fromUri);
    $this->entity->set('toUri', $toUri);
    $status = $deathLink->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label redirect.', [
          '%label' => $deathLink->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label redirect.', [
          '%label' => $deathLink->label(),
        ]));
    }
    $formState->setRedirectUrl($deathLink->toUrl('collection'));
  }

  /**
   * Get the linkit profile to use.
   *
   * @return array|mixed|null
   *   The linkit profile id.
   */
  protected function getLinkitProfile() {
    // Get the config.
    $config = $this->configFactory()->get('death_link.settings');
    return $config->get('linkit_profile');
  }

}
