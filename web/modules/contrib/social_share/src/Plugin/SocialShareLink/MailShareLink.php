<?php

namespace Drupal\social_share\Plugin\SocialShareLink;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Template\Attribute;
use Drupal\social_share\SocialShareLinkInterface;

/**
 * A social share link for e-mail.
 *
 * @SocialShareLink(
 *   id = "social_share_mail",
 *   label = @Translation("e-Mail"),
 *   category = @Translation("Default"),
 *   context = {
 *     "mail_link_text" = @ContextDefinition(
 *       data_type = "string",
 *       label = @Translation("Mail link text"),
 *       description = @Translation("The text of the sharing link."),
 *       default_value = "Share via e-Mail"
 *     ),
 *     "mail_subject" = @ContextDefinition(
 *       data_type = "string",
 *       label = @Translation("Mail default subject"),
 *       description = @Translation("The default subject of the mail."),
 *       default_value = "Share this article"
 *     ),
 *     "mail_body" = @ContextDefinition(
 *       data_type = "string",
 *       label = @Translation("Mail default body"),
 *       description = @Translation("The default text of the mail."),
 *     )
 *   }
 * )
 */
class MailShareLink extends ContextAwarePluginBase implements SocialShareLinkInterface {

  /**
   * The machine name of the template used.
   *
   * @var string
   */
  protected $templateName = 'social_share_link_mail';

  /**
   * {@inheritdoc}
   */
  public function build($template_suffix = '', $render_context = []) {
    $render =  [
      '#theme' => $this->templateName . $template_suffix,
      '#attributes' => new Attribute([]),
      '#render_context' => $render_context,
    ];
    foreach ($this->getContexts() as $name => $context) {
      $render["#$name"] = $context->getContextValue();
    }
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateInfo() {
    $info = [
      'variables' => [
        'render_context' => [],
      ],
    ];
    foreach ($this->getContextDefinitions() as $name => $definition) {
      $info['variables'][$name] = $definition->getDefaultValue();
    }
    return [
      $this->templateName => $info,
    ];
  }

}
