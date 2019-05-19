<?php

namespace Drupal\social_share\Plugin\SocialShareLink;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Template\Attribute;
use Drupal\social_share\SocialShareLinkInterface;

/**
 * A social share link for twitter.
 *
 * @SocialShareLink(
 *   id = "social_share_twitter",
 *   label = @Translation("Twitter"),
 *   category = @Translation("Default"),
 *   context = {
 *     "twitter_link_text" = @ContextDefinition(
 *       data_type = "string",
 *       label = @Translation("Twitter link text"),
 *       description = @Translation("The text of the sharing link."),
 *       default_value = "Share with Twitter"
 *     ),
 *     "shared_text" = @ContextDefinition("string",
 *       label = @Translation("Shared text (short)"),
 *       description = @Translation("A short text to use for sharing. Maximum length is 140 characters."),
 *     ),
 *     "hashtags" = @ContextDefinition("string",
 *       label = @Translation("Hashtags"),
 *       description = @Translation("Some comma-separated hash-tags."),
 *       required = false
 *     ),
 *     "twitter_url" = @ContextDefinition("uri",
 *       label = @Translation("Twitter share URL"),
 *       description = @Translation("The URL shared and shortened by twitter. When set to '&lt;current&gt;', the current page's URL is used."),
 *       required = false
 *     ),
 *     "twitter_via" = @ContextDefinition("string",
 *       label = @Translation("Twitter via"),
 *       description = @Translation(" A Twitter username to associate with the Tweet."),
 *       required = false
 *     ),
 *     "twitter_related" = @ContextDefinition("string",
 *       label = @Translation("Twitter related users"),
 *       description = @Translation("Suggest additional Twitter usernames related to the Tweet as comma-separated values."),
 *       required = false
 *     ),
 *     "twitter_reply_to" = @ContextDefinition("integer",
 *       label = @Translation("Twitter reply to"),
 *       description = @Translation("The Tweet ID of a parent Tweet in a conversation, such as the initial Tweet from your site or author account."),
 *       required = false
 *     ),
 *   }
 * )
 */
class TwitterShareLink extends ContextAwarePluginBase implements SocialShareLinkInterface {

  /**
   * The machine name of the template used.
   *
   * @var string
   */
  protected $templateName = 'social_share_link_twitter';

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
