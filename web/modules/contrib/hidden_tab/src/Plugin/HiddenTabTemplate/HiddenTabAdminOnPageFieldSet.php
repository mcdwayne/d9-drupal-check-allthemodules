<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabTemplate;

use Drupal\hidden_tab\Plugable\Annotation\HiddenTabTemplateAnon;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;

/**
 * Provides a field set on entity view page, so admin content can be bunched.
 *
 * @HiddenTabTemplateAnon(
 *   id = "hidden_tab_admin_on_page_fieldset"
 * )
 */
class HiddenTabAdminOnPageFieldSet extends HiddenTabTemplatePluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  public $PID = 'hidden_tab_admin_on_page_fieldset';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'On Page Admin Fieldset';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'Provides a field set on entity view page, so admin content can be bunched.';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 0;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = ['internal', 'admin'];

  /**
   * See regions().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase::regions()
   */
  protected $regions = [
    'reg_0' => 'Region 0',
  ];

  /**
   * See templateFile().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase::templateFile()
   */
  protected $templateFile = 'admin-on-page-fieldset';

  /**
   * Shortcut.
   *
   * @return string|null
   *   Template, if plugin was found.
   */
  public static function getAdminTemplateFromPlugin(): ?string {
    $admin_template_missing = !HiddenTabTemplatePluginManager::instance()
      ->exists(HiddenTabAdminOnPageFieldSet::$PID);
    return !$admin_template_missing
      ? !HiddenTabTemplatePluginManager::instance()
        ->plugin(HiddenTabAdminOnPageFieldSet::$PID)
      : NULL;
  }

}
