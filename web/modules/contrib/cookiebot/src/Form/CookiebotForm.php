<?php

namespace Drupal\cookiebot\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\AliasManager;

/**
 * Cookiebot settings form.
 */
class CookiebotForm extends ConfigFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Alias manager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * The cache tag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  private $cacheTagsInvalidator;

  /**
   * Constructs a object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\AliasManager $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tag invalidator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_manager, AliasManager $alias_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($config_factory);
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_manager;
    $this->aliasManager = $alias_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('path.alias_manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cookiebot.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cookiebot_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cookiebot.settings');

    if (empty($config->get('cookiebot_cbid'))) {
      $this->messenger()->addWarning($this->t('Cookiebot functionality is disabled until you enter a valid CBID.'));
    }

    $form['cookiebot_cbid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cookiebot Domain Group ID (CBID)'),
      '#description' => $this->t("This ID looks like 00000000-0000-0000-0000-000000000000. You can find it in the <a href='https://www.cookiebot.com/en/manage'>Cookiebot Manager</a> on the 'Your scripts' tab."),
      '#default_value' => $config->get('cookiebot_cbid'),
    ];

    $form['cookiebot_iab_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabling IAB framework'),
      '#description' => $this->t('IAB (Interactive Advertising Bureau) model puts scripts control in the hands of advertisers and vendors by only signaling consent to vendors. More information about <a href="https://support.cookiebot.com/hc/en-us/articles/360007652694-Cookiebot-and-the-IAB-Consent-Framework">Cookiebot and the IAB Consent Framework</a>.'),
      '#default_value' => $config->get('cookiebot_iab_enabled'),
    ];

    $form['cookiebot_declaration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cookie declaration'),
    ];

    $form['cookiebot_declaration']['cookiebot_show_declaration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the Cookiebot cookie declaration'),
      '#description' => $this->t('Automatically show the full Cookiebot cookie declaration on the given page.'),
      '#default_value' => $config->get('cookiebot_show_declaration'),
    ];

    $form['visibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cookiebot visibility'),
    ];

    $form['visibility']['exclude_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude paths'),
      '#default_value' => !empty($config->get('exclude_paths')) ? $config->get('exclude_paths') : '',
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
        '%blog' => '/blog',
        '%blog-wildcard' => '/blog/*',
        '%front' => '<front>',
      ]),
    ];

    $form['visibility']['exclude_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude admin pages'),
      '#default_value' => $config->get('exclude_admin_theme'),
    ];

    $form['visibility']['exclude_uid_1'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Don’t show the Cookiebot for UID 1.'),
      '#default_value' => !empty($config->get('exclude_uid_1')) ? $config->get('exclude_uid_1') : 0,
    ];

    $declaration_node = '';
    $alias = $this->aliasManager->getPathByAlias($config->get('cookiebot_show_declaration_node_path'));
    if (preg_match('/node\/(\d+)/', $alias, $matches)) {
      $declaration_node = $this->entityTypeManager->getStorage('node')->load($matches[1]);
    }

    $description = $this->t('Show the full cookie declaration on the node page with the given title.');
    $description .= '<br />';
    $description .= $this->t("Note that custom templates and modules like Panels and Display Suite can prevent the declaration from showing up.
    You can always place our block or manually place Cookiebot's declaration script found in their manager if your input filters allow it.");

    $form['cookiebot_declaration']['cookiebot_show_declaration_node_path'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Node page title'),
      '#description' => $description,
      '#default_value' => $declaration_node,
      '#states' => [
        'visible' => [
          ':input[name="cookiebot_show_declaration"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cbid_trimmed = trim($form_state->getValue('cookiebot_cbid'));
    $form_state->setValue('cookiebot_cbid', $cbid_trimmed);

    if (!empty($cbid_trimmed) && !preg_match('/^[0-9a-z]{8}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{12}$/', $cbid_trimmed)) {
      $form_state->setErrorByName('cookiebot_cbid', $this->t('The entered Domain Group ID is not formatted correctly.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->cacheTagsInvalidator->invalidateTags([
      'cookiebot:cbid',
      'cookiebot:show_declaration',
      'cookiebot:iab_enabled',
    ]);

    $this->config('cookiebot.settings')
      ->set('cookiebot_cbid', $form_state->getValue('cookiebot_cbid'))
      ->set('cookiebot_iab_enabled', $form_state->getValue('cookiebot_iab_enabled'))
      ->set('cookiebot_show_declaration', $form_state->getValue('cookiebot_show_declaration'))
      ->set('cookiebot_show_declaration_node_path', $this->aliasManager->getAliasByPath('/node/' . $form_state->getValue('cookiebot_show_declaration_node_path')))
      ->set('exclude_paths', $form_state->getValue('exclude_paths'))
      ->set('exclude_admin_theme', $form_state->getValue('exclude_admin_theme'))
      ->set('exclude_uid_1', $form_state->getValue('exclude_uid_1'))
      ->save();
  }

}
