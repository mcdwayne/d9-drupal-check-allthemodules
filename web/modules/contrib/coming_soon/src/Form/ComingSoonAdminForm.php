<?php

namespace Drupal\coming_soon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * Manages the Coming Soon admin form.
 */
class ComingSoonAdminForm extends ConfigFormBase {

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Current user from current session.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $entityTypeManager;

  /**
   * Current user from current session.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Construct a new Coming Soon Admin form.
   *
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   File usage backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   */
  public function __construct(FileUsageInterface $file_usage, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account) {
    $this->fileUsage = $file_usage;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.usage'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coming_soon_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['coming_soon.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('coming_soon.settings');

    $form['coming_soon_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
      '#default_value' => $config->get('coming_soon_heading'),
      '#description' => $this->t("Heading to display in the coming soon page, will default to the site name if omitted."),
    ];

    $form['coming_soon_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('coming_soon_body.value'),
      '#format' => $config->get('coming_soon_body.format'),
      '#description' => $this->t("The body text of the page."),
    ];

    $form['coming_soon_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable guests notification'),
      '#description' => $this->t("Enable users to sign up for notification on website launch."),
      '#default_value' => $config->get('coming_soon_notification'),
    ];

    $form['coming_soon_logo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display logo'),
      '#description' => $this->t("Display the site's default logo."),
      '#default_value' => $config->get('coming_soon_logo'),
    ];

    $form['coming_soon_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#description' => $this->t("When to stop displaying the coming soon page."),
      '#default_value' => $config->get('coming_soon_end_date'),
    ];

    $form['coming_soon_bg'] = [
      '#type' => 'managed_file',
      '#name' => 'coming_soon_bg',
      '#title' => $this->t('Background image'),
      '#default_value' => $config->get('coming_soon_bg'),
      '#description' => $this->t("Background image, if omitted, a default color will be applied."),
      '#upload_location' => 'public://coming_soon/',
    ];

    $form['coming_soon_copyrights'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyrights'),
      '#default_value' => $config->get('coming_soon_copyrights'),
      '#description' => $this->t("Copyrights text."),
    ];

    $form['coming_soon_social'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Social networks"),
    ];

    $form['coming_soon_social']['coming_soon_facebook'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook'),
      '#default_value' => $config->get('coming_soon_facebook'),
      '#description' => $this->t("Facebook link."),
    ];

    $form['coming_soon_social']['coming_soon_twitter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter'),
      '#default_value' => $config->get('coming_soon_twitter'),
      '#description' => $this->t("Twitter link."),
    ];

    $form['coming_soon_social']['coming_soon_googleplus'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google+'),
      '#default_value' => $config->get('coming_soon_googleplus'),
      '#description' => $this->t("Google+ link."),
    ];

    $form['coming_soon_social']['coming_soon_linkedin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Linkedin'),
      '#default_value' => $config->get('coming_soon_linkedin'),
      '#description' => $this->t("Linkedin link."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get submitted values.
    $values = $form_state->getValues();
    $bg_image_id = !empty($values['coming_soon_bg'][0]) ? $values['coming_soon_bg'][0] : NULL;
    $existing_bg_image_config = $this->config('coming_soon.settings')->get('coming_soon_bg');
    $existing_image_id = !empty($existing_bg_image_config[0]) ? $existing_bg_image_config[0] : NULL;

    // Make image file permanent if user uploaded new file.
    if ($bg_image_id && $bg_image_id != $existing_image_id && $bg_image = $this->entityTypeManager->getStorage('file')->load($bg_image_id)) {
      $bg_image->setPermanent();
      $bg_image->save();

      // Increment file usage count as well.
      $this->fileUsage->add($bg_image, 'coming_soon', 'coming_soon', $this->account->id());
    }
    // Decrement file usage count if existing image was replaced/removed.
    if ($existing_image_id && $existing_image_id != $bg_image_id && $existing_bg_image = $this->entityTypeManager->getStorage('file')->load($existing_image_id)) {
      $this->fileUsage->delete($existing_bg_image, 'coming_soon', 'coming_soon');
    }

    // Save the configuration.
    $this->config('coming_soon.settings')
      ->set('coming_soon_heading', $values['coming_soon_heading'])
      ->set('coming_soon_body', $values['coming_soon_body'])
      ->set('coming_soon_notification', $values['coming_soon_notification'])
      ->set('coming_soon_logo', $values['coming_soon_logo'])
      ->set('coming_soon_end_date', $values['coming_soon_end_date'])
      ->set('coming_soon_bg', $values['coming_soon_bg'])
      ->set('coming_soon_copyrights', $values['coming_soon_copyrights'])
      ->set('coming_soon_facebook', $values['coming_soon_facebook'])
      ->set('coming_soon_twitter', $values['coming_soon_twitter'])
      ->set('coming_soon_googleplus', $values['coming_soon_googleplus'])
      ->set('coming_soon_linkedin', $values['coming_soon_linkedin'])
      ->save();

    // Clear cache to allo for the new config to take effect.
    drupal_flush_all_caches();

    // Display success message.
    drupal_set_message($this->t('Coming Soon configuration submitted successfully.'), 'status', TRUE);
  }

}
