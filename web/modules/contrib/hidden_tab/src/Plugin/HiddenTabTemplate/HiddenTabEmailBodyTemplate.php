<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabTemplate;

use Drupal\hidden_tab\Plugable\Annotation\HiddenTabTemplateAnon;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase;

/**
 * Displays email's body.
 *
 * @HiddenTabTemplateAnon(
 *   id = "hidden_tab_email_body"
 * )
 */
class HiddenTabEmailBodyTemplate extends HiddenTabTemplatePluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::id()
   */
  protected $PID = 'hidden_tab_email_body';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::label()
   */
  protected $HTPLabel = 'Email Body';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::description()
   */
  protected $HTPDescription = 'Default template to render body of email';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::weight()
   */
  protected $HTPWeight = 0;

  /**
   * See display().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::display()
   */
  protected $HTPDisplay = FALSE;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = ['email', 'email_body'];

  /**
   * See regions().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase::regions()
   */
  protected $regions = [];

  /**
   * See templateFile().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase::templateFile()
   */
  protected $templateFile = 'internal-email-body';

}
