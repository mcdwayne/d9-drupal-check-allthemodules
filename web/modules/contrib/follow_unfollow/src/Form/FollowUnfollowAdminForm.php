<?php

namespace Drupal\follow_unfollow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Configure follow unfollow settings form for this site.
 */
class FollowUnfollowAdminForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs the BlockVisibilityAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'follow_unfollow_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['follow_unfollow.admin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('follow_unfollow.admin.settings');

    // Get list of node type.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $contentTypeList = [];
    foreach ($node_types as $machine_name => $val) {
      $contentTypeList[$machine_name] = $val->label();
    }

    // Select content type need follow and unfollow button.
    $form['content_type'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select Content Types'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['content_type']['follow_unfollow_content_type'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => $this->t('Content Types'),
      '#options' => $contentTypeList,
      '#default_value' => $config->get('follow_unfollow.content_type')?:[],
      '#description' => $this->t('Select content type that is used to collect statistics of follow and unfollow content'),
    ];

    // Get list of vocabulary.
    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    $vocabularyType = [];
    foreach ($vocabularies as $machine_name => $val) {
      $vocabularyType[$machine_name] = $val->label();
    }
    // Select vocabulary type.
    $form['vocabulary_type'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select Vocabulary Types'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['vocabulary_type']['follow_unfollow_vocabulary_type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabulary Types'),
      '#options' => $vocabularyType,
      '#default_value' => $config->get('follow_unfollow.vocabulary_type')?:[],
      '#description' => $this->t('Select Vocabulary type that is used to collect statistics of follow and unfollow content'),
    ];

    // Select user type.
    $form['follow_unfollow_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select User Type'),
      '#description' => $this->t('Select User type that is used to collect statistics of follow and unfollow content.'),
      '#default_value' => $config->get('follow_unfollow.user'),
    ];

    // Configuration of email template for follow button.
    $form['follow_unfollow_email_template_of_follow'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Follow Email Template'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // Subject of follow button.
    $form['follow_unfollow_email_template_of_follow']['follow_unfollow_subject_of_follow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Follow Subject'),
      '#default_value' => $config->get('follow_unfollow_email.follow.subject'),
      '#description' => $this->t('Used @title,@username and @url for follow subject email templating.'),
    ];

    // Body of follow button.
    $form['follow_unfollow_email_template_of_follow']['follow_unfollow_body_of_follow'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Follow Body'),
      '#default_value' => $config->get('follow_unfollow_email.follow.body'),
      '#description' => $this->t('Used @title,@username and @url for follow body email templating.'),
      '#wysiwyg' => TRUE,
    ];

    // Configuration of email template for unfollow button.
    $form['follow_unfollow_email_template_of_unfollow'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Unfollow Email Template'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // Subject of unfollow button.
    $form['follow_unfollow_email_template_of_unfollow']['follow_unfollow_subject_of_unfollow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unfollow Subject'),
      '#default_value' => $config->get('follow_unfollow_email.unfollow.subject'),
      '#description' => $this->t('Used @title,@username and @url for unfollow subject email templating.'),
    ];

    // Body of unfollow button.
    $form['follow_unfollow_email_template_of_unfollow']['follow_unfollow_body_of_unfollow'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Unfollow Body'),
      '#default_value' => $config->get('follow_unfollow_email.unfollow.body'),
      '#description' => $this->t('Used @title,@username and @url for unfollow body email templating.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('follow_unfollow.admin.settings');

    $config->set('follow_unfollow.content_type', $form_state->getValue('follow_unfollow_content_type'));
    $config->set('follow_unfollow.vocabulary_type', $form_state->getValue('follow_unfollow_vocabulary_type'));
    $config->set('follow_unfollow.user', $form_state->getValue('follow_unfollow_user'));

    $config->set('follow_unfollow_email.follow.subject', $form_state->getValue('follow_unfollow_subject_of_follow'));
    $config->set('follow_unfollow_email.follow.body', $form_state->getValue('follow_unfollow_body_of_follow'));

    $config->set('follow_unfollow_email.unfollow.subject', $form_state->getValue('follow_unfollow_subject_of_unfollow'));
    $config->set('follow_unfollow_email.unfollow.body', $form_state->getValue('follow_unfollow_body_of_unfollow'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
