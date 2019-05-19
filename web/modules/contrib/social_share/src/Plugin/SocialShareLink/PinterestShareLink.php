<?php

namespace Drupal\social_share\Plugin\SocialShareLink;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Template\Attribute;
use Drupal\social_share\SocialShareLinkInterface;

/**
 * A social share link for pinterest.
 *
 * @SocialShareLink(
 *   id = "social_share_pinterest",
 *   label = @Translation("Pinterest"),
 *   category = @Translation("Default"),
 *   context = {
 *     "pinterest_link_text" = @ContextDefinition(
 *       data_type = "string",
 *       label = @Translation("Pinterest link text"),
 *       description = @Translation("The text of the sharing link."),
 *       default_value = "Share on pinterest",
 *     ),
 *     "title" = @ContextDefinition("string",
 *       label = @Translation("Title"),
 *       description = @Translation("The title of the shared item.")
 *     ),
 *     "image_url" = @ContextDefinition("string",
 *       label = @Translation("Image-Url"),
 *       description = @Translation("The URL of the image to share.")
 *     ),
 *     "url" = @ContextDefinition("uri",
 *       label = @Translation("Shared URL"),
 *       description = @Translation("The URL to share. Defaults to the current page."),
 *       required = false
 *     ),
 *     "hashtags" = @ContextDefinition("string",
 *       label = @Translation("Hashtags"),
 *       description = @Translation("Some comma-separated hash-tags."),
 *       required = false
 *     ),
 *   }
 * )
 */
class PinterestShareLink extends ContextAwarePluginBase implements SocialShareLinkInterface {

  /**
   * The machine name of the template used.
   *
   * @var string
   */
  protected $templateName = 'social_share_link_pinterest';

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
