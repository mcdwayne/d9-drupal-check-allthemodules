<?php

/**
 * Defines the client interface for a Akismet Drupal client.
 */

namespace Drupal\akismet\Client;

interface DrupalClientInterface {

  /**
   * Loads a configuration value from client-side storage.
   *
   * @param string $name
   *   The configuration setting name to load, one of:
   *   - publicKey: The public API key for Akismet authentication.
   *   - privateKey: The private API key for Akismet authentication.
   *   - expectedLanguages: List of expected language codes for site content.
   *
   * @return mixed
   *   The stored configuration value or NULL if there is none.
   *
   * @see Akismet::saveConfiguration()
   * @see Akismet::deleteConfiguration()
   */
  function loadConfiguration($name);

  /**
   * Saves a configuration value to client-side storage.
   *
   * @param string $name
   *   The configuration setting name to save.
   * @param mixed $value
   *   The value to save.
   *
   * @see Akismet::loadConfiguration()
   * @see Akismet::deleteConfiguration()
   */
  function saveConfiguration($name, $value);

  /**
   * Deletes a configuration value from client-side storage.
   *
   * @param string $name
   *   The configuration setting name to delete.
   *
   * @see Akismet::loadConfiguration()
   * @see Akismet::saveConfiguration()
   */
  function deleteConfiguration($name);

  /**
   * Checks user-submitted content with Akismet.
   *
   * @param array $data
   *   An associative array containing any of the keys:
   *   - blog: The URL of this site.
   *   - user_ip: The IP address of the text submitter.
   *   - user_agent: The user-agent string of the web browser submitting the
   *     text.
   *   - referrer: The HTTP_REFERER value.
   *   - permalink: The permanent URL where the submitted text can be found.
   *   - comment_type: A description of the type of content being checked:
   *     https://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/
   *   - comment_author: The (real) name of the content author.
   *   - comment_author_email: The email address of the content author.
   *   - comment_author_url: The URL (if any) that the content author provided.
   *   - comment_content: The body of the content. If the content consists of
   *     multiple fields, concatenate them into one postBody string, separated
   *     by " \n" (space and line-feed).
   *   - comment_date_gmt: The date the content was submitted.
   *   - comment_post_modified_gmt: (For comments only) The creation date of the
   *     post being commented on.
   *   - blog_lang: The languages in use on this site, in ISO 639-1 format. Ex:
   *     "en, fr_ca".
   *   - blog_charset: The character encoding in use for the values being
   *     submitted.
   *   - user_role: The role of the user who submitted the comment. Optional.
   *     Should be 'administrator' for site administrators; submitting a value
   *     of 'administrator' will guarantee that Akismet sees the content as ham.
   *   - server: The contents of $_SERVER, to be added to the request.
   *
   * @return int|array
   *   On failure, the status code. On success, an associative array keyed as
   *   follows:
   *   - guid: The GUID returned by Akismet.
   *   - classification: the spam classification ('ham', 'spam', or 'unsure').
   */
  public function checkContent(array $data = array());

  /**
   * Sends feedback to Akismet.
   *
   * @param array $data
   *   An associative array of data suitable for checkContent().
   * @param string $feedback
   *   The kind of content being sent (either 'ham' or 'spam').
   *
   * @return bool
   *   TRUE if the feedback was sent successfully, FALSE otherwise.
   */
  public function sendFeedback(array $data, $feedback);
}
