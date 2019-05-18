<?php

namespace Drupal\httpbl;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;

/**
 * HttpblResponse builds the final response to request.
 *
 * After a request has been fully evaluated, a new response is built and
 * retrieved in HttpblMiddleware.  It only uses the new response if the
 * status is not OK (status code = 200).
 *
 *  Other possible status:
 *  FORBIDDEN (403) - if the request is or will be blacklisted.
 *  PRECONDITION REQUIRED (428) - if the request is greylisted, pending a
 *  white-list challange.
 *  PRECONDITION FAILED  (412) - if the challenge is failed.
 *  NOT EXTENDED (510) - if a critical error occurs.
 */
class HttpblResponse implements HttpblResponseInterface {

 /**
   * A logger arbitration instance.
   *
   * @var \Drupal\httpbl\Logger\HttpblLogTrapperInterface
   */
  protected $logTrapper;

  /**
   * Construct HttpblResponse.
   *
   * @param \Drupal\httpbl\Logger\HttpblLogTrapperInterface $logTrapper
   *   The logger arbitration.
   */
  public function __construct(HttpblLogTrapperInterface $logTrapper) {
    $this->logTrapper = $logTrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHttpblResponse($ip, SymfonyRequest $request, $defaultResponse, $evaluated = NULL) {

    // If evaluation indicates a safe visitor...
    if (isset($evaluated) && $evaluated[0] == 'evaluated' && $evaluated[1] == (int)HTTPBL_LIST_SAFE){
      // Then nothing to do, so return the default response.

      return $defaultResponse;
    }

    // If evaluation indicates a blacklisted visitor...
    if (isset($evaluated) && $evaluated[0] == 'evaluated' && $evaluated[1] == HTTPBL_LIST_BLACK){
      // Build a response that includes a link the visitor's profile on Project
      // Honey Pot.  If they're human they'll see why they were blacklisted (if
      // they don't already know).
      $ipurl = self::honeypot_ipdata($ip, FALSE);
      // Also place a honeypot on the response, in case visitor is not human.
      $honeypot = t(self::buildHoneypot());
      // Retreive the pre-formatted blacklist message from the config settings.
      $message = \Drupal::state()->get('httpbl.message_black');
      // Build the new response and return it.
      $httpblResponse = new SymfonyResponse(new FormattableMarkup($message, ['@ip' => $ip, '@request' => $request->getRequestUri(), '@ipurl' => $ipurl, '@honeypot' => $honeypot]), 403);

      return $httpblResponse;
    }

    // If evaluation indicates a grey-listed visitor...
    if (isset($evaluated) && $evaluated[0] == 'evaluated' && $evaluated[1] == HTTPBL_LIST_GREY){
      // Build a response that includes a link the visitor's profile on Project
      // Honey Pot.  If they're human they'll see why they were grey-listed.
      $ipurl = self::honeypot_ipdata($ip, FALSE);

      // Build a link to the white-list challenge form.
      // Note:  We are doing this before all services are available, so we'll do
      // it the quick, old fashioned way.
      // Note 2: Once this link is set up for the visitor, it's the only valid
      // request the evaluator will accept from a grey-listed visitor.  In other
      // words, to get a 200 Response, they have to click the challenge link.
      // From that point on, the White-list challenge form will decide the next
      // step; they will either be white-listed on a session basis if they pass
      // the challenge, or blacklisted (and possibly banned) if they fail.
      $whitelistLink = t('/httpbl/whitelist');
      // Also place a honeypot on the response, in case visitor is not human.
      $honeypot = t(self::buildHoneypot());
      // Retreive the pre-formatted greylist message from the config settings.
      $message = \Drupal::state()->get('httpbl.message_grey');
      // Build the new response and return it.
      $httpblResponse = new SymfonyResponse(new FormattableMarkup($message, ['@ip' => $ip, '@request' => $request->getRequestUri(), '@ipurl' => $ipurl, '@whitelistUrl' => $whitelistLink, '@honeypot' => $honeypot]), 428);

      return $httpblResponse;
    }

    // Below is to handle any possible failure resulting in a non-evaluated
    // session getting passed through this function.
    $honeypot = t(self::buildHoneypot());
    $httpblResponse = new SymfonyResponse(new FormattableMarkup('<h1>Not Extended (510)</h1>Default fail for @ip. HttpblResponse did not receive an evaluated request!@honeypot', ['@ip' => $ip, '@request' => $request->getRequestUri(), '@honeypot' => $honeypot]), 510);

    // Log this failure as critical!
    // Somehow we got here without any valid evaluation results.  That means
    // something is broken.
    //
    // Note that despite the evaluation failure, a positive hit on honeypot
    // still results in the host being stored in our table.  In other words, 
    // the failure only affects the response that the visitor would see.
    $this->logTrapper->trapCritical('HttpBL Server Error 510 (Not Extended):Default fail for @ip. HttpblResponse received an invalid evaluation result for request  @request! Requested evaluation result was ("@r1" - @r2).', ['@ip' => $ip, '@request' => $request->getRequestUri(), '@r1' => $evaluated[0], '@r2' => $evaluated[1]]);

    return $httpblResponse;
  }

  /**
   * {@inheritdoc}
   */
  public function challengeFailureBlacklisted($ip, $return_date) {
    $honeypot = t(self::buildHoneypot());
    $message = new FormattableMarkup('<h1 class=httpbl>412 HTTP_PRECONDITION_FAILED</h1>Failed white-list request challenge; @ip has been blacklisted on this site for @return.@honeypot', ['@ip' => $ip, '@return' => $return_date, '@honeypot' => $honeypot]);

    $failureResponse = new SymfonyResponse($message, 412);
    return $failureResponse;
  }

  /**
   * {@inheritdoc}
   */
  public function challengeFailurePurgatory() {
    $honeypot = t(self::buildHoneypot());
    $message = new FormattableMarkup('<h1 class=httpbl>412 HTTP_PRECONDITION_FAILED</h1>Failed white-list request challenge.  Good-bye!@honeypot', ['@honeypot' => $honeypot]);

    $failureResponse = new SymfonyResponse($message, 412);
    return $failureResponse;
  }

  /**
   * {@inheritdoc}
   */
  private static function honeypot_ipdata($ip, $anchor = TRUE) {
    if ($anchor) {
      return '<a href="http://www.projecthoneypot.org/search_ip.php?ip=' . $ip . '">IP data</a>';
    }
    else {
      return 'http://www.projecthoneypot.org/search_ip.php?ip=' . $ip;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function buildHoneypot() {

    if (\Drupal::state()->get('httpbl.footer') ?: FALSE) {
      $link = \Drupal::state()->get('httpbl.link') ?: NULL;
      $word = \Drupal::state()->get('httpbl.word') ?: 'randomness';
      $honeypot = self::httpbl_honeylink($link, $word);

      if (!empty($honeypot)) {
        return $honeypot;
      }
    } return "";
  }

  /**
   * {@inheritdoc}
   */
  public static function httpbl_honeylink($link, $word) {
    if (!$link) {
      return empty($honeypot);
    }

    // Randomize the switch to determine which style will be used.
    switch (mt_rand(0, 5)) {

      case 0:
        // Formats a nofollow link with text as an html comment.
        // WCAG compliant using aria-hidden="true".
        // Example: <div><a aria-hidden="true" rel="nofollow" href="http://example.com/sites/all/scripts/disorder.php"><!-- alexander shoes --></a></div>
        // Test note: D8 Chrome Invisible, Firefox Invisible, Safari Invisible.
        return '<div><a aria-hidden="true" rel="nofollow" href="' . $link . '"><!-- ' . $word . '--></a></div>';

      case 1:
        // Formats a nofollow link and text in a style hidden anchor tag.
        // WCAG compliant using aria-hidden="true".
        // Example: <div><a aria-hidden="true" hidden rel="nofollow" href="http://example.com/sites/all/scripts/disorder.php" >alexander shoes</a></div>
        // Test note: D8 Chrome Invisible, Firefox Invisible, Safari Invisible.
        // Legacy for D5, D6 and D7: '<div><a rel="nofollow" style="display: none;" href="' . $link . '" >' . $word . '</a></div>';
        return '<div><a aria-hidden="true" hidden rel="nofollow" href="' . $link . '">' . $word . '</a></div>';

      case 2:
        // Format a nofollow link and text inside a style hidden div tag.
        // WCAG compliant using aria-hidden="true".
        // Example: <div hidden><a aria-hidden="true" rel="nofollow" href="http://example.com/sites/all/scripts/disorder.php">alexander shoes</a></div>
        // Test note: D8 Chrome Invisible, Firefox Invisible, Safari Invisible.
        // Legacy for D5, D6 and D7: '<div style="display: none;"><a rel="nofollow" href="' . $link . '">' . $word . '</a></div>';
        return '<div hidden><a aria-hidden="true" rel="nofollow" href="' . $link . '">' . $word . '</a></div>';

      case 3:
        // Formats a nofollow link with no text.
        // WCAG compliant using aria-hidden="true".
        // Example: <div><a aria-hidden="true" rel="nofollow" href="http://example.com/sites/all/scripts/disorder.php"></a></div>
        // Test Notes: D8 Chrome Invisible, Firefox Invisible, Safari Invisible.
        return '<div><a aria-hidden="true" rel="nofollow" href="' . $link . '"></a></div>';

      case 4:
        // Formats entire link and text as an HTML comment.
        // WCAG compliant using aria-hidden="true".
        // Example: <!-- <a aria-hidden="true" href="http://example.com/sites/all/scripts/disorder.php">alexander shoes</a> -->
        // Test Notes: D8 Chrome Invisible, Firefox Invisible, Safari Invisible.
        return '<!-- <a aria-hidden="true" href="' . $link . '">' . $word . '</a> -->';

      case 5:
        // Formats a nofollow link with text in style hidden span tag.
        // WCAG compliant using aria-hidden="true".
        // Example: <div><a aria-hidden="true" rel="nofollow" href="http://example.com/sites/all/scripts/disorder.php"><span hidden>alexander shoes</span></a></div>
        // Test Notes: D8 Chrome Invisible, Firefox Invisible, Safari Invisible.
        // Legacy for D5, D6 and D7: '<div><a rel="nofollow" href="' . $link . '"><span style="display: none;">' . $word . '</span></a></div>';
        return '<div><a aria-hidden="true" rel="nofollow" href="' . $link . '"><span hidden>' . $word . '</span></a></div>';

    }
    return empty($honeypot);
  }

}

