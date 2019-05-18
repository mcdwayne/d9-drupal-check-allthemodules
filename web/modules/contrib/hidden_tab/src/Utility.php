<?php

namespace Drupal\hidden_tab;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Entity\HiddenTabCreditInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * TODO make this an injectable service.
 *
 * @package Drupal\hidden_tab
 */
final class Utility {

  public const ADMIN_PERMISSION = 'administer hidden tab';

  /**
   * Let's name this properly.
   *
   * Layouts is a VERY dangerous page. It gives access to EVERYTHING. The user
   * may select whatever they wish, panels, blocks, view anything to any page
   * they have access to and maybe bypassing access check of that particular
   * komponent.
   *
   * There are a log of dangerous permissions, but this one deserves a bit of
   * more attention I believe.
   */
  public const THE_MIGHTY_DANGEROUS_LAYOUT_PERMISSION = 'administer site configuration';

  public const ERROR_VIEW_PERMISSION = self::ADMIN_PERMISSION;

  /**
   * Do not allow instantiation of utility class.
   */
  private function __construct() {
  }

  // ========================================================== DISPLAY GOODIES

  public const TICK = '✔'; // '&#x2714;';

  public const CROSS = '✘'; // '&#10008;';

  public const WARNING = '⚠️'; // '&#9888;';

  public const QUERY_NAME = 'hash';

  /**
   *  Unicode HTML element, tick or cross based on boolean evaluation of $eval.
   *
   * @param $eval
   *   Parameter to evaluate as boolean.
   *
   * @return string
   *   Unicode HTML element, tick or cross based on boolean evaluation of $eval.
   */
  public static function mark($eval): string {
    return !!$eval ? static::TICK : static::CROSS;
  }

  // ================================================================ DATA UTIL

  /**
   * Calculate hash by key and data. Implementation is arbitrary.
   *
   * @param mixed $data
   *   The data.
   * @param mixed $key
   *   The hash key.
   *
   * @return string
   *   Calculated hash.
   */
  public static function hash($data, $key): string {
    return Crypt::hmacBase64(strval($data), strval($key));
  }

  public static function matches(HiddenTabCreditInterface $hash_entity, string $hash) {
    return static::hash($hash_entity->id(), $hash_entity->secretKey()) === $hash;
  }


  // ==================================================================== STUFF

  /**
   * All permission in the drupal installation, id to label array.
   *
   * @param array $permissions
   *   Array of permissions.
   * @param bool $none_option
   *   Should the list include a none options or not.
   *
   * @return array
   *   All permission in the drupal installation, id to label array.
   */
  public static function permissionOptions(array $permissions = NULL,
                                           $none_option = TRUE): array {
    if ($permissions === NULL) {
      $permissions = \Drupal::service('user.permissions')->getPermissions();
    }
    $none = $none_option ? [
      '' => \Drupal::translation()
        ->translate('None'),
    ] : [];
    $options = [];
    foreach ($permissions as $id => $info) {
      $t = str_replace('</em>', '', str_replace('<em class="placeholder">', '', $info['title']));
      $options[$id] = $t . ' (' . $info['provider'] . ')';
    }
    asort($options);
    return $none + $options;
  }

  // ============================================================== REDIRECTION

  /**
   * Current path good for lredirect value, to redirect back here later.
   *
   * Value lredirect is a normal path but slashes replaced with stars.
   *
   * @return string
   *   Current path good for lredirect value, to redirect back here later.
   *
   * @see \Drupal\hidden_tab\Utility::redirectThere()
   */
  public static function redirectHere(): string {
    $cr = \Drupal::service('request_stack')->getCurrentRequest();
    /** @noinspection PhpUndefinedMethodInspection */
    $rep = $cr->getSchemeAndHttpHost() . $cr->getRequestUri();
    return str_replace('/', '*', $rep);
  }

  /**
   * Get the lredirect value from the query or empty (that is, a single start).
   *
   * @return string
   *   Get the lredirect value from the query or empty (that is, a single
   *   start).
   *
   * @see \Drupal\hidden_tab\Utility::redirectHere()
   */
  public static function lRedirect(): string {
    return \Drupal::request()->query->get('lredirect') ?: '*';
  }

  /**
   * Find a suitable lredirect value created by redirectHere().
   *
   * @return \Drupal\Core\Url|null
   *   The found Url to redirect to.
   *
   * @see \Drupal\hidden_tab\Utility::redirectHere()
   */
  public static function checkRedirect(): ?Url {
    $q = static::lRedirect();
    $p = !$q || $q === '*' ? NULL : str_replace('*', '/', $q);
    if (!$p) {
      return NULL;
    }
    return Url::fromUri($p, [
      'lredirect' => $p,
    ]);
  }

  /**
   * Same path just like redirectHere() but for the given page.
   *
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page to redirect to.
   * @param \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $credit
   *   The current hash being visited.
   *
   * @return string
   *   Same path just like redirectHere() but for the given page.
   *
   * @see \Drupal\hidden_tab\Utility::redirectHere()
   */
  public static function redirectThere(HiddenTabPageInterface $page,
                                       HiddenTabCreditInterface $credit): string {


    // TODO limited support of entity type.
    try {
      try {
        $target = $credit->targetEntity();
        if (!$target
          && $credit->targetEntityType()
          && \Drupal::routeMatch()->getParameter($credit->targetEntityType())) {
          $target = \Drupal::routeMatch()
            ->getParameter($credit->targetEntityType());
        }
        if (!$target) {
          $target = \Drupal::routeMatch()->getParameter('node');
        }
        if (!$target) {
          \Drupal::logger('hidden_tab')
            ->warning('could not find redirect target page={page} credit={credit}', [
              'page' => $page->id(),
              'credit' => $credit->id(),
            ]);
          return '*';
        }
        $uri = \Drupal::request()
            ->getSchemeAndHttpHost() . '/' . $target->getEntityTypeId()
          . '/' . $target->id() . '/' . $page->id();
        return str_replace('/', '*', $uri);
      }
      catch (\Throwable $error0) {
        \Drupal::logger('hidden_tab')
          ->warning('error when finding redirect page={page} credit={credit} msg={msg} trace={trace}', [
            'page' => $page->id(),
            'credit' => $credit->id(),
            'msg' => $error0->getMessage(),
            'trace' => $error0->getTraceAsString(),
          ]);
        return '*';
      }
    }
    catch (\Throwable $error1) {
      // Don't fail for dumb shit.
      \Drupal::logger('hidden_tab')
        ->error('error while erroring msg={msg}', [
          'msg' => $error1->getMessage(),
        ]);
      return '*';
    }
  }

  /**
   * Calculates current Url, doh!
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   To calculate current Url from.
   *
   * @return string
   *   Current Url, doh!
   */
  public static function currentUrl(RequestStack $request_stack): string {
    $cr = $request_stack->getCurrentRequest();
    return $cr->getSchemeAndHttpHost() . $cr->getRequestUri();
  }

  // ==================================================================== EMAIL

  /**
   * Send an email (the secret link).
   *
   * @param string $mail
   *   The email address.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param \Drupal\hidden_tab\Entity\HiddenTabMailerInterface $mailer
   *   The mail configuration for the given page.
   *
   * @return bool
   *   True if success.
   */
  public static function email(string $mail,
                               HiddenTabPageInterface $page,
                               EntityInterface $entity,
                               HiddenTabMailerInterface $mailer,
                               array $ctx): bool {
    $ok = \Drupal::service('plugin.manager.mail')->mail(
      'hidden_tab',
      'hidden_tab',
      $mail,
      'en',
      [
        'page' => $page,
        'target_entity' => $entity,
        'mailer' => $mailer,
        'email' => $mail,
        'langcode' => 'en',
      ] + $ctx,
      NULL,
      TRUE
    );
    return $ok['result'] ? TRUE : FALSE;
  }

  /**
   * Find all mail configurations for a page.
   *
   * @param string $page_id
   *   The page in question.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Mail configurations set for a pge
   */
  public static function mailConfigOfPage(string $page_id): array {
    return \Drupal::entityTypeManager()->getStorage('hidden_tab_mailer')
      ->loadByProperties([
        'target_hidden_tab_page' => $page_id,
      ]);
  }

  // ==================================================================== EMAIL

  /**
   * Log exceptions when working with an entity type on non-critical
   * situations.
   *
   * @param \Throwable $error
   *   The occurred exception.
   * @param string $type
   *   The entity type who we were building fields for, and the exception
   *   occurred then.
   * @param string $prop
   *   Property being rendered while error happened.
   * @param string|integer $entity_id
   *   Id of entity if any,
   * @param string|null $msg
   *   Additional message.
   * @param array $extra_ctx
   *   Additional context to pass to logger.
   * @param bool $say
   *   Whether if show the error on flash message area too or not.
   */
  public static function renderLog(?\Throwable $error,
                                   $type,
                                   $prop = NULL,
                                   $entity_id = NULL,
                                   ?string $msg = '',
                                   $extra_ctx = [],
                                   bool $say = TRUE) {
    if (!is_array($extra_ctx)) {
      \Drupal::logger('hidden_tab')
        ->error('bad extra value passed to logger, must be array, type={h_type}', [
          'h_type' => gettype($extra_ctx),
        ]);
    }
    if ($msg) {
      $msg = 'rendering error, ' . $msg . ', entity_type={h_entity_type} entity_property={h_entity_property} entity_id={h_entity_id}';
    }
    else {
      $msg = 'rendering error entity_type={h_entity_type} entity_property={h_entity_property} entity_id={h_entity_id}';
    }
    static::error($error, $msg, [
        'h_entity_type' => strval($type ?: '?'),
        'h_entity_property' => strval($prop ?: ''),
        'h_entity_id' => strval($entity_id ?: '?'),
      ] + (is_array($extra_ctx) ? $extra_ctx : []), $say);
  }

  /**
   * Log an error and display a flash message if user has permission.
   *
   * Note: There will be limited number of $msg strings throughout the codebase,
   * essentially making the final error message an static one.
   *
   * @param \Throwable|null $error
   *   Error to display.
   * @param string|null $msg
   *   Additional message.
   * @param array $extra_ctx
   *   Additional context to pass to logger.
   * @param mixed|null say
   *   Boolean evaluated, whether if pass to static::sayError to or not.
   */
  public static function error(?\Throwable $error, ?string $msg = '', $extra_ctx = [], $say = TRUE) {
    $errs = [];
    if (is_array($extra_ctx)) {
      foreach ($extra_ctx as $key => $crap) {
        $errs[$key] = strval($crap);
      }
    }
    else {
      \Drupal::logger('hidden_tab')
        ->error('bad extra value passed to logger, must be array, type={h_type}', [
          'h_type' => gettype($extra_ctx),
        ]);
    }

    if ($say && \Drupal::currentUser()
        ->hasPermission(self::ERROR_VIEW_PERMISSION)) {
      $say = $msg ?: '';
      foreach ($extra_ctx as $e_key => $e_val) {
        $say = str_replace('{' . $e_key . '}', strval($e_val) ?: '?', $say);
      }
      if ($say) {
        \Drupal::messenger()->addError(t('Error, @msg: @err', [
          '@msg' => strval($say ?: '?'),
          '@err' => $error ? $error->getMessage() : '?',
        ]));
      }
      else {
        \Drupal::messenger()->addError(t('Error: @err', [
          '@err' => $error ? $error->getMessage() : '?',
        ]));
      }
    }

    \Drupal::logger('hidden_tab')
      ->error('error extra=[' . (strval($msg)) . '] error_type={h_type} message={h_msg} trace={h_trace}', [
          'h_type' => get_class($error) ?: '?',
          'h_msg' => $error ? $error->getMessage() : '?',
          'h_trace' => $error ? $error->getTraceAsString() : '?',
        ] + $errs);
  }

}
