<?php

namespace Drupal\social_share\Plugin\SocialShareLink;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Template\Attribute;
use Drupal\social_share\SocialShareLinkInterface;

/**
 * A social share link for linkedin.
 *
 * @SocialShareLink(
 *   id = "social_share_linkedin",
 *   label = @Translation("Linked.in"),
 *   category = @Translation("Default"),
 *   context = {
 *     "linkedin_link_text" = @ContextDefinition(
 *       data_type = "string",
 *       label = @Translation("Linked.in link text"),
 *       description = @Translation("The text of the sharing link."),
 *       default_value = "Share with Linked.in"
 *     ),
 *     "linkedin_url" = @ContextDefinition("uri",
 *       label = @Translation("Linked.in share URL"),
 *       description = @Translation("URL of the page that you wish to share. When set to '&lt;current&gt;', the current page's URL is used. Maximum length is 1024 characters."),
 *     ),
 *     "linkedin_title" = @ContextDefinition("string",
 *       label = @Translation("Linked.in shared title"),
 *       description = @Translation("A short text to use for sharing. Maximum length is 200 characters."),
 *     ),
 *     "linkedin_summary" = @ContextDefinition("string",
 *       label = @Translation("Linked.in summary"),
 *       description = @Translation("A short description that you wish you use. Maximum length is 256 characters."),
 *       required = false
 *     ),
 *     "linkedin_source" = @ContextDefinition("string",
 *       label = @Translation("Linked.in source"),
 *       description = @Translation("A source of the content (e.g. your website or application name). Maximum length is 200 characters."),
 *       required = false
 *     ),
 *   }
 * )
 */
class LinkedInShareLink extends ContextAwarePluginBase implements SocialShareLinkInterface {

  /**
   * The machine name of the template used.
   *
   * @var string
   */
  protected $templateName = 'social_share_link_linkedin';

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
