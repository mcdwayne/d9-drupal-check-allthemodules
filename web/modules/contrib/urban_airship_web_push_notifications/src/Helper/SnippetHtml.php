<?php

namespace Drupal\urban_airship_web_push_notifications\Helper;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Site\Settings;

/**
 * Build Snippet HTML based on SDK Bundle files.
 */
class SnippetHtml {

  /**
   * Generate JS snippet to include on pages.
   */
  public function getSnippet() {
    $config = \Drupal::config('urban_airship_web_push_notifications.configuration');
    // Remove <script> tags from the source file.
    // We're rebuilding <script> tag in .module file.
    $javascript = str_replace(['<script type="text/javascript">', '</script>'], '', $config->get('snippet.html'));
    if (!empty($config->get('secure-bridge.html'))) {
      $javascript = $this->addSecureIframeUrl($javascript);
    }
    return Markup::create($javascript);
  }

  /**
   * Adding `secureIframeUrl` parameter to the snippet file to support
   * secure bridge.
   * See https://docs.urbanairship.com/platform/web/#secure-domain-bridge-http-scenarios-only
   */
  protected function addSecureIframeUrl($javascript) {
    $snippet = [];
    // Secure bridge only works when HTTPS enabled.
    // Make sure https://examples.com/web-push-secure-bridge.html path is using HTTPS.
    $host = \Drupal::request()->getHost();
    $secure_bridge_url = Url::fromRoute('urban_airship_web_push_notifications.secure_bridge_html');
    $domain = Settings::get('urban_airship_web_push_notifications_sb_domain', 'https://' . $host);
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $javascript) as $line) {
      if (strstr($line, 'appKey:')) {
        $snippet[] = $line;
        $snippet[] = "\tsecureIframeUrl: '" . $domain .  $secure_bridge_url->toString() . "',";
      }
      else {
        $snippet[] = $line;
      }
    }
    return join("\n", $snippet);
  }

}
