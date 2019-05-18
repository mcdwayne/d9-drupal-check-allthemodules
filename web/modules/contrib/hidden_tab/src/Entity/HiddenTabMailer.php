<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\hidden_tab\Entity\Helper\DescribedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\MultiAspectPluginSupportingTrait;
use Drupal\hidden_tab\Entity\Helper\RefrencerEntityTrait;
use Drupal\hidden_tab\Entity\Helper\StatusedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\TimestampedEntityTrait;
use Drupal\hidden_tab\FUtility;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;

/**
 * Defines the hidden tab mailer entity class.
 *
 * @ContentEntityType(
 *   id = "hidden_tab_mailer",
 *   label = @Translation("Hidden Tab Mailer"),
 *   label_collection = @Translation("Hidden Tab Mailers"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\hidden_tab\Entity\HiddenTabMailerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" =
 *   "Drupal\hidden_tab\Entity\HiddenTabMailerAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\hidden_tab\Form\HiddenTabMailerForm",
 *       "edit" = "Drupal\hidden_tab\Form\HiddenTabMailerForm",
 *       "delete" =
 *   "Drupal\hidden_tab\Form\Base\PageBasedRedirectedDeleteFormBase",
 *       "default" = "Drupal\hidden_tab\Form\HiddenTabMailerForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "hidden_tab_mailer",
 *   data_table = "hidden_tab_mailer_field_data",
 *   admin_permission = "administer hidden tab",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/hidden-tab-mailer/add",
 *     "edit-form" =
 *   "/admin/content/hidden-tab-mailer/{hidden_tab_mailer}/edit",
 *     "delete-form" =
 *   "/admin/content/hidden-tab-mailer/{hidden_tab_mailer}/delete",
 *     "canonical" = "/hidden_tab_mailer/{hidden_tab_mailer}",
 *     "collection" = "/admin/content/hidden-tab-mailer"
 *   },
 *   field_ui_base_route = "entity.hidden_tab_mailer.settings"
 * )
 */
class HiddenTabMailer extends ContentEntityBase implements HiddenTabMailerInterface {

  use RefrencerEntityTrait;
  use StatusedEntityTrait;
  use DescribedEntityTrait;
  use TimestampedEntityTrait;
  use EntityChangedTrait;
  use MultiAspectPluginSupportingTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += FUtility::defaultField();
    $fields += FUtility::refrencerEntityFields('node', t('Node'), HiddenTabEntityHelper::nodeBundlesSelectList());

    $tpl_svc = HiddenTabTemplatePluginManager::instance();

    $fields['plugins'] = FUtility::jsonEncodedField('Plugins', []);

    $fields['email_schedule'] =
      FUtility::int('Email Schedule', 'Every...')
        ->setDefaultValue(HiddenTabMailerInterface::EMAIL_SCHEDULE_DEFAULT_PERIOD);
    $fields['email_schedule_granul'] =
      FUtility::list('Email Schedule Granularity', NULL, [
        'second' => 'Second',
        'minute' => 'Minute',
        'hour' => 'Hour',
        'day' => 'Day',
        'month' => 'Month',
        'year' => 'Year',
        'week' => 'Week',
      ])
        ->setDefaultValue(HiddenTabMailerInterface::EMAIL_SCHEDULE_DEFAULT_GRANULARITY);
    $fields['next_schedule'] =
      FUtility::timestamp('next_schedule', '');
    $fields['email_template'] =
      FUtility::list('Email Template', NULL,
        $tpl_svc->pluginsForSelectElement('mailer'))
        ->setRequired(FALSE);
    $fields['email_title_template'] =
      FUtility::list('Email Title Template', NULL,
        $tpl_svc->pluginsForSelectElement('mailer'))
        ->setRequired(FALSE);
    $fields['email_inline_template'] =
      FUtility::bigString('Email Inline Twig Template', NULL);
    $fields['email_inline_title_template'] =
      FUtility::bigString('Email Inline Title Twig Template', NULL);

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * When a new hidden tab mailer entity is created, set the uid entity
   * reference to the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * See emailSchedule().
   *
   * @var int|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface::emailSchedule()
   */
  protected $email_schedule;

  /**
   * See emailScheduleGranul().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface::emailScheduleGranul()
   */
  protected $email_schedule_granul;

  /**
   * See nextSchedule().
   *
   * @var int|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface::nextSchedule()
   */
  protected $next_schedule;

  /**
   * See emailTemplate().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface::emailTemplate()
   */
  protected $email_template;

  /**
   * See emailTitleTemplate().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface::emailTitleTemplate()
   */
  protected $email_title_template;

  /**
   * See emailInlineTemplate().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface::emailInlineTemplate().
   */
  protected $email_inline_template;

  /**
   * See emailTitleInlineTemplate()
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface::emailTitleInlineTemplate()
   */
  protected $email_inline_title_template;

  /**
   * {@inheritdoc}
   */
  public function emailSchedule(): ?int {
    return $this->email_schedule;
  }

  /**
   * {@inheritdoc}
   */
  public function emailScheduleGranul(): ?string {
    return $this->email_schedule_granul;
  }

  /**
   * {@inheritdoc}
   */
  public function nextSchedule(): ?int {
    return $this->next_schedule;
  }

  /**
   * {@inheritdoc}
   */
  public function emailTemplate(): ?string {
    return $this->email_template;
  }

  /**
   * {@inheritdoc}
   */
  public function emailTitleTemplate(): ?string {
    return $this->email_title_template;
  }

  /**
   * {@inheritdoc}
   */
  public function emailInlineTemplate(): ?string {
    return $this->email_inline_template;
  }

  /**
   * {@inheritdoc}
   */
  public function emailTitleInlineTemplate(): ?string {
    return $this->email_inline_title_template;
  }

}
