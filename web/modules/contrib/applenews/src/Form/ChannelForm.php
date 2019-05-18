<?php

namespace Drupal\applenews\Form;

use Drupal\applenews\PublisherInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Apple News channel forms.
 *
 * @internal
 */
class ChannelForm extends ContentEntityForm {

  /**
   * The channel being used by this form.
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $entity;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date formatter service.
   *
   * @var \Drupal\applenews\PublisherInterface
   */
  protected $publisher;

  /**
   * Constructs a MessageForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\applenews\PublisherInterface $publisher
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, DateFormatterInterface $date_formatter, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, PublisherInterface $publisher = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->languageManager = $language_manager;
    $this->dateFormatter = $date_formatter;
    $this->publisher = $publisher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('date.formatter'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('applenews.publisher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attributes']['class'][] = 'applenews-channel-form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t('Add new channel');
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $channel_id = $form_state->getValue('id')[0]['value'];
    try {
      $response = $this->publisher->getChannel($channel_id);
    }
    catch (\Exception $e) {
      // Throw validation error.
      $form_state->setError($form['id'], $this->t('Invalid channel id'));
    }
    if ($response) {
      $this->entity->updateFromResponse($response);
    }
    else {
      $form_state->setErrorByName('id', $this->t('Error while trying to reach applenews.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $channel_id = $form_state->getValue('id')[0]['value'];
    $channel = $this->entity;

    // Fetch sections.
    $response = $this->publisher->GetSections($channel_id);
    if ($response) {
      $channel->updateSections($response);
    }
    $status = $channel->save();

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label channel.', ['%label' => $channel->label()]));
    }
    else {
      $this->messenger()->addError($this->t('The %label channel was not saved.', ['%label' => $channel->label()]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
