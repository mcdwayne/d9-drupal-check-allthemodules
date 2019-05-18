<?php

namespace Drupal\field_redirection\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'field_redirection_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_redirection_formatter",
 *   label = @Translation("Redirect"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class FieldRedirectionFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'code' => '301',
      '404_if_empty' => FALSE,
      'page_restrictions' => 0,
      'pages' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $codes = $this->httpCodes();

    // Load the current selection, default to "301".
    $code = 301;
    if (!empty($this->getSetting('code')) && isset($codes[$this->getSetting('code')])) {
      $code = $this->getSetting('code');
    }
    // Choose the redirector.
    $elements['code'] = [
      '#title' => 'HTTP status code',
      '#type' => 'select',
      '#options' => $codes,
      '#default_value' => $code,
    ];

    // 404 if the field value is empty.
    $elements['404_if_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('404 if URL empty'),
      '#default_value' => !empty($this->getSetting('404_if_empty')),
      '#description' => $this->t('Optionally display a 404 error page if the associated URL field is empty.'),
    ];

    $elements['note'] = [
      '#markup' => $this->t('Note: If the destination path is the same as the current path it will behave as if it is empty.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Provide targeted URL rules to trigger this action.
    $elements['page_restrictions'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect page restrictions'),
      '#default_value' => empty($this->getSetting('page_restrictions')) ? 0 : $this->getSetting('page_restrictions'),
      '#options' => $this->pageRestrictionOptions(),
    ];

    $elements['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#default_value' => empty($this->getSetting('pages')) ? '' : $this->getSetting('pages'),
      '#description' => $this->t("Enter one page per line as Drupal paths. The '@wildcard' character is a wildcard. Example paths are '@example_blog' for the blog page and '@example_all_personal_blogs' for every personal blog. '@frontpage' is the front page. You can also use tokens in this field, for example '@example_current_node' can be used to define the current node path.", [
        '@wildcard' => '*',
        '@example_blog' => 'blog',
        '@example_all_personal_blogs' => 'blog/*',
        '@frontpage' => '<front>',
        '@example_current_node' => 'node/[node:nid]',
      ]),
      '#states' => [
        'invisible' => [
          ':input[name*="[page_restrictions]"]' => ['value' => '0'],
        ],
      ],
    ];

    $elements['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => 'all',
      '#weight' => 100,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    // Display a "hair on fire" warning message for any view mode other than "full".
    if ($this->viewMode != 'full') {
      drupal_set_message($this->t('Danger! The Redirect formatter should not be used with any view mode other than "Full content".'), 'warning');
    }

    if (!empty($settings['code'])) {
      $summary[] = $this->t('HTTP status code: @code', ['@code' => $settings['code']]);
    }

    if ($settings['404_if_empty']) {
      $summary[] = $this->t('Will return 404 (page not found) if field is empty.');
    }

    if (!empty($settings['page_restrictions'])) {
      $page_restrictions = $this->pageRestrictionOptions();
      $summary[] = $this->t('Page restriction options: @pagerestriction', ['@pagerestriction' => $page_restrictions[$settings['page_restrictions']]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();
    $codes = $this->httpCodes();
    $current_url = Url::fromRoute('<current>');
    $current_path = $current_url->toString();

    // Optionally control the list of pages this works on.
    if (!empty($settings['page_restrictions']) && !empty($settings['pages'])) {
      // Remove '1' from this value so it can be XOR'd later on.
      $page_restrictions = $settings['page_restrictions'] - 1;

      // Do raw token replacements.
      $pages = \Drupal::token()->replace(
        $settings['pages'],
        [],
        ['clear' => TRUE]
      );

      // Normalise all paths to lower case.
      $pages = Unicode::strtolower($pages);
      $page_match = \Drupal::service('path.matcher')->matchPath($current_path, $pages);

      if ($current_path != \Drupal::request()->getRequestUri()) {
        $page_match = $page_match || \Drupal::service('path.matcher')->matchPath(\Drupal::request()->getRequestUri(), $pages);
      }

      // Stop processing if the page restrictions have matched.
      if (!($page_restrictions xor $page_match)) {
        return $elements;
      }
    }

    // Don't do anything if running via the CLI, e.g. Drush.
    if (constant('PHP_SAPI') == 'cli') {
      return $elements;
    }
    // Don't do anything if the current page is running the normal cron script; this
    // also supports Elysia Cron.
    elseif (Unicode::strpos($_SERVER['PHP_SELF'], 'cron.php') !== FALSE) {
      return $elements;
    }
    // Don't do anything if the cron script is being executed from the admin status page.
    elseif ($current_path == 'admin/reports/status/run-cron') {
      return $elements;
    }
    // Don't do anything if site is in maintenance mode.
    elseif (defined('MAINTENANCE_MODE') || \Drupal::state()->get('system.maintenance_mode')) {
      return $elements;
    }

    // Only redirect based on the first value of a field. Ignore other values.
    if (!empty($items[0])) {
      $item = $items[0];
    }
    // If no URL was provided, and the user does not have permission to bypass
    // the redirection, display the 404 error page.
    elseif (!\Drupal::currentUser()->hasPermission('bypass redirection') && $settings['404_if_empty']) {
      throw new NotFoundHttpException();
    }
    // If no values are present, pick up ball and go home.
    else {
      return $elements;
    }

    // Set response code.
    $response_code = 301;
    if (!empty($settings['code']) && isset($codes[$settings['code']])) {
      $response_code = $settings['code'];
    }

    // Work out the destination path to redirect to. Each field type is handled
    // slightly differently, so identify that here.
    $redirect_url = null;
    //$options = [];
    $field_definition = $item->getFieldDefinition();
    if (!empty($field_definition->getType())) {
      switch ($field_definition->getType()) {
        // Link field from the Link module.
        case 'link':
          if (!empty($item->uri)) {
            // Create a Url object from the uri.
            $redirect_url = Url::fromUri($item->uri);

            // The path is the URL field itself.
            // $uri = $item->uri;

            /*
            // Cover for cases when a query string was provided.
            if (!empty($item['query'])) {
              $options['query'] = $item['query'];
            }

            // Optional fragment.
            if (!empty($item['fragment'])) {
              $options['fragment'] = $item['fragment'];
            }
            */
          }
          break;
      }
    }

    if (!empty($redirect_url)) {
      /**
       * We need to check if the redirect URL is the same as:
       *
       * 1. The current (possibly an alias) path (relative).
       * 2. The current (possibly an alias) path (absolute).
       * 3. The current path's internal path (relative). Url->toString()
       *    always returns an alias, so this is covered by point 1 above.
       * 4. The current path's internal path (absolute).
       * 5. The current path, which is also the home page.
       *
       * If any of these cases are true, then do not redirect.
       */
      // Current path (relative) and current internal path (relative).
      if ($current_path == $redirect_url->toString()) {
        $redirect_url = null;
      }
      // Current path (absolute).
      $current_url->setAbsolute(TRUE);
      if (($redirect_url != null) && ($current_url->toString() == $redirect_url->toString())) {
        $redirect_url = null;
      }
      // Current internal path (absolute).
      /**
       * To do: this is not working! Need to figure out how to create an
       * absolute path to an internal path. Until this is fixed, a redirect url
       * that looks like http://drupal8.dev/node/3 will end up in a redirect
       * loop if node/3 has an alias. This is because I can't figure out how
       * to generate an absolute path to the internal path (not the alias).
       */
      /*
      $current_url->setAbsolute(TRUE);
      if (($redirect_url != null) && ($current_url->getInternalPath() == $redirect_url->toString())) {
        $redirect_url = null;
      }
      */
      // Current path is the home page.
      if ($redirect_url != null) {
        if (!$redirect_url->isExternal()) {
          if (($redirect_url->getRouteName() == '<front>') && (\Drupal::service('path.matcher')->isFrontPage())) {
            $redirect_url = null;
          }
        }
      }
    }

    // Only proceed if a url was identified.
    if (!empty($redirect_url)) {
      // Use default language to not prepend language prefix if path is absolute
      // without hostname.
      // To do: figure this stuff out.
      /*
      if ($path[0] == '/') {
        $path = substr($path, 1);
        $options['language'] = language_default();
      }
      */

      // If the user has permission to bypass the page redirection, return a
      // message explaining where they would have been redirected to.
      if (\Drupal::currentUser()->hasPermission('bypass redirection')) {
        // To do: Do we need to worry about $options here?
        $external_link = \Drupal::service('link_generator')->generate(t('another URL'), $redirect_url);

        // "Listen very carefully, I shall say this only once." - 'Allo, 'Allo.
        $message = $this->t('This page is set to redirect to @external_link, but you have permission to see this page and will not be automatically redirected.', ['@external_link' => $external_link]);
        // To do: what does all this $_SESSION stuff do? Do we need it in 8.x?
        if (empty($_SESSION['messages']['warning']) || !in_array($message, $_SESSION['messages']['warning'])) {
          drupal_set_message($message, 'warning');
        }
      }
      else {
        // If caching this page, add 'Cache-Control' header before redirecting.
        // To do: figure this stuff out.
        /*
        $caching_enabled = variable_get('cache', 0);
        $page_cacheable = drupal_page_is_cacheable();
        $no_session_cookie = !isset($_COOKIE[session_name()]);
        if ($caching_enabled && $page_cacheable && $no_session_cookie) {
          // @see drupal_serve_page_from_cache().
          // If the client sent a session cookie, a cached copy will only be
          // served to that one particular client due to Vary: Cookie. Thus, do
          // not set max-age > 0, allowing the page to be cached by external
          // proxies, when a session cookie is present unless the Vary header has
          // been replaced or unset in hook_boot().
          $max_age = !isset($_COOKIE[session_name()]) || isset($hook_boot_headers['vary']) ? variable_get('page_cache_maximum_age', 0) : 0;
          drupal_add_http_header('Cache-Control', 'public, max-age=' . $max_age);
        }
        */
        $response = new RedirectResponse($redirect_url->toString(), $response_code);
        $response->send();
        exit();
      }
    }

    return $elements;
  }

  /**
   * The standard HTTP redirection codes that are supported.
   *
   * @return array
   *   The supported HTTP codes.
   */
  protected function httpCodes() {
    return [
      '300' => $this->t('300: Multiple Choices (rarely used)'),
      '301' => $this->t('301: Moved Permanently (default)'),
      '302' => $this->t('302: Found (rarely used)'),
      '303' => $this->t('303: See Other (rarely used)'),
      '304' => $this->t('304: Not Modified (rarely used)'),
      '305' => $this->t('305: Use Proxy (rarely used)'),
      '307' => $this->t('307: Temporary Redirect (temporarily moved)'),
    ];
  }

  /**
   * The standard HTTP redirection codes that are supported.
   *
   * @return array
   *   The supported HTTP codes.
   */
  protected function pageRestrictionOptions() {
    return [
      '0' => $this->t('Redirect on all pages.'),
      '1' => $this->t('Redirect only on the following pages.'),
      '2' => $this->t('Redirect on all pages except the following pages.'),
    ];
  }

}
