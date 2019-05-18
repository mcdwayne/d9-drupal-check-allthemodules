<?php

namespace Drupal\obfuscate\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;

/**
 * Provides a filter to obfuscate email addresses.
 *
 * The regex patterns are taken from the FilterSpamspan class.
 * See https://www.drupal.org/project/spamspan.
 *
 * @Filter(
 *   id = "obfuscate_mail",
 *   title = @Translation("Email address obfuscation filter"),
 *   description = @Translation("Attempt to hide email addresses from spam-bots."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class ObfuscateMail extends FilterBase {

  // These will help us deal with inline images, which if very large
  // break the preg_match and preg_replace.
  const PATTERN_IMG_INLINE = '/data\:(?:.+?)base64(?:.+?)["|\']/';
  const PATTERN_IMG_PLACEHOLDER = '__obfuscate_img_placeholder__';

  /**
   * Safeguard pattern to operate replacements.
   */
  const SAFEGUARD = '!%%$$';

  /**
   * Stores the original element to restore.
   *
   * @var array
   */
  private $elementsQueue = [];

  /**
   * Returns main pattern.
   *
   * Set up a regex constant to split an email address into name and domain
   * parts. The following pattern is not perfect (who is?), but is intended to
   * intercept things which look like email addresses.  It is not intended to
   * determine if an address is valid.  It will not intercept addresses with
   * quoted local parts.
   *
   * @return string
   *   Main pattern.
   */
  private function getPatternMain() {
    return "([-\.\~\'\!\#\$\%\&\+\/\*\=\?\^\_\`\{\|\}\w\+^@]+)"
    // @
    . '@'
    // Group 2.
    . '((?:'
    // One or more letters or dashes followed by a dot.
    . '[-\w]+\.'
    // The whole thing one or more times.
    . ')+'
    // With between 2 and 63 letters at the end (NB new TLDs)
    . '[A-Z]{2,63})';
  }

  /**
   * Returns pattern email bare.
   *
   * Top and tail the email regexp it so that it is case insensitive and
   * ignores whitespace.
   *
   * @return string
   *   Bare email pattern.
   */
  private function getPatternEmailBare() {
    return '!' . $this->getPatternMain() . '!ix';
  }

  /**
   * Returns pattern email with options.
   *
   * Options such as subject or body
   * e.g. <a href="mailto:email@example.com?subject=Hi there!&body=Dear Sir">
   *
   * @return string
   *   Email with options pattern.
   */
  private function getPatternEmailWithOptions() {
    return '!' . $this->getPatternMain() . '\[(.*?)\]!ix';
  }

  /**
   * Returns patterns mailto.
   *
   * Next set up a regex for mailto: URLs.
   * - see http://www.faqs.org/rfcs/rfc2368.html
   * This captures the whole mailto: URL into the second group,
   * the name into the third group and the domain into
   * the fourth. The tag contents go into the fifth.
   *
   * @return string
   *   Mailto pattern.
   */
  private function getPatternMailto() {
    // Opening <a and spaces.
    return '!<a\s+'
    // Any attributes.
    . "((?:\w+\s*=\s*)(?:\w+|\"[^\"]*\"|'[^']*'))*?"
    // whitespace.
    . '\s*'
    // The href attribute.
    . "href\s*=\s*(['\"])(mailto:"
    // The email address.
    . $this->getPatternMain()
    // An optional ? followed.
    . "(?:\?[A-Za-z0-9_= %\.\-\~\_\&;\!\*\(\)\\'#&]*)?)"
    // By a query string. NB
    // we allow spaces here,
    // even though strictly
    // they should be URL
    // encoded
    // the relevant quote.
    . '\\2'
    // Character
    // any more attributes.
    . "((?:\s+\w+\s*=\s*)(?:\w+|\"[^\"]*\"|'[^']*'))*?"
    // End of the first tag.
    . '>'
    // Tag contents.  NB this.
    . '(.*?)'
    // Will not work properly
    // if there is a nested
    // <a>, but this is not
    // valid xhtml anyway.
    // closing tag.
    . '</a>!ix';
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Each email address will be obfuscated with the system wide configuration.');
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // HTML image tags need to be handled separately, as they may contain base64
    // encoded images slowing down the email regex function.
    // Therefore, remove all image contents and add them back later.
    // See https://drupal.org/node/1243042 for details.
    $images = [[]];
    preg_match_all(self::PATTERN_IMG_INLINE, $text, $images);
    $text = preg_replace(self::PATTERN_IMG_INLINE, self::PATTERN_IMG_PLACEHOLDER, $text);

    // Now we can convert all mailto URLs.
    $text = preg_replace_callback($this->getPatternMailto(), [$this, 'callbackMailto'], $text);

    // All bare email addresses with optional formatting information.
    // @todo implement
    // $text = preg_replace_callback($this->getPatternEmailWithOptions(),
    // [$this, 'callbackEmailAddressesWithOptions'], $text);
    // And finally, all bare email addresses.
    // @todo use polymorphism to handle this exception/remove coupling
    // that should be only in the scope of ROT 13
    // A match could already have been applied with the result
    // of rot13 for the mailto callback, so in this case we are double
    // obfuscating a rot13 email (which cannot happen in
    // the case of html_entity method).
    // This could probably be simplified by a
    // negative lookahead / lookbehind regex.
    $dom = $this->rot13Safeguard($text);
    $text = Html::serialize($dom);

    // Apply then the bare email obfuscation.
    $text = preg_replace_callback($this->getPatternEmailBare(), [$this, 'callbackBareEmailAddresses'], $text);

    // Set then back the safeguarded obfuscated emails.
    $newDom = $this->restoreRot13Safeguard($text);
    $text = Html::serialize($newDom);

    // Revert back to the original image contents.
    foreach ($images[0] as $image) {
      $text = preg_replace('/' . self::PATTERN_IMG_PLACEHOLDER . '/', $image, $text, 1);
    }

    $result = new FilterProcessResult($text);

    // Libraries are not attached via the template in this case.
    $result->setAttachments([
      'library' => [
        'obfuscate/rot13',
      ],
    ]);
    return $result;
  }

  /**
   * Safeguards ROT 13 obfuscated emails.
   *
   * Applies a safeguard based on an index to preserve already
   * obfuscated emails from further alteration.
   *
   * @param string $text
   *   The text that may contain ROT 13 obfuscated emails.
   *
   * @return \DOMDocument
   *   The ROT 13 safeguarded DOM.
   */
  private function rot13Safeguard($text) {
    $dom = Html::load($text);
    $xPath = new \DOMXPath($dom);
    $index = 0;

    /** @var \DOMElement $domElement */
    foreach ($xPath->query('//span[contains(@class,"boshfpngr-e13")]') as $domElement) {
      $this->elementsQueue[] = $domElement;
      $safeguardElement = $dom->createElement('span', 'tmp');
      $safeguardElement->setAttribute('id', $this->getUniqueSafeguard($index));
      $domElement->parentNode->replaceChild($safeguardElement, $domElement);
      $index++;
    }

    $dom->saveHTML($dom->documentElement);
    return $dom;
  }

  /**
   * Restores the ROT 13 safeguarded values.
   *
   * @param string $text
   *   The ROT 13 safeguarded text.
   *
   * @return \DOMDocument
   *   The restored DOM that may contain ROT 13 obfuscated emails.
   */
  private function restoreRot13Safeguard($text) {
    $dom = HTML::load($text);
    $xPath = new \DOMXPath($dom);
    /** @var \DOMElement $domElement */
    foreach ($this->elementsQueue as $index => $domElement) {
      $safeguardElements = $xPath->query("//span[@id='" . $this->getUniqueSafeguard($index) . "']");
      /** @var \DOMElement $safeguardElement */
      $safeguardElement = $safeguardElements[0];
      if ($safeguardElement instanceof \DOMElement && $domElement instanceof \DOMElement) {
        $node = $dom->importNode($domElement, TRUE);
        $dom->documentElement->appendChild($node);
        $safeguardElement->parentNode->replaceChild($node, $safeguardElement);
      }
    }
    $dom->saveHTML($dom->documentElement);
    return $dom;
  }

  /**
   * Returns an unique safeguard to identify the element to replace back.
   *
   * @param int $index
   *   Index of the element to replace.
   *
   * @return string
   *   Safeguard with index.
   */
  private function getUniqueSafeguard($index) {
    return self::SAFEGUARD . $index . strrev(self::SAFEGUARD);
  }

  /**
   * Callback function for preg_replace_callback on getPatternEmailBare.
   *
   * @param array $matches
   *   An array containing parts of an email address.
   *
   * @return string
   *   The span with which to replace the email address.
   */
  public function callbackBareEmailAddresses(array $matches) {
    return $this->output($matches[1], $matches[2]);
  }

  /**
   * Callback function for preg_replace_callback on getPatternMailto.
   *
   * Replace an email addresses which has been found with the appropriate
   * <span> tags.
   *
   * @param array $matches
   *   An array containing parts of an email address or mailto: URL.
   *
   * @return string
   *   The span with which to replace the email address.
   */
  public function callbackMailto(array $matches) {
    // Take the mailto: URL in $matches[3] and split the query string
    // into its component parts, putting them in $headers as
    // [0]=>"header=contents" etc.  We cannot use parse_str because
    // the query string might contain dots.
    // Single quote can be encoded as &#039; which breaks parse_url
    // Replace it back to a single quote which is perfectly valid.
    $matches[3] = str_replace("&#039;", '\'', $matches[3]);
    $query = parse_url($matches[3], PHP_URL_QUERY);
    $query = str_replace('&amp;', '&', $query);
    $headers = preg_split('/[&;]/', $query);
    // If no matches, $headers[0] will be set to '' so $headers must be reset.
    if ($headers[0] == '') {
      $headers = [];
    }

    // Take all <a> attributes except the href and put them into custom $vars.
    $vars = $attributes = [];
    // Before href.
    if (!empty($matches[1])) {
      $matches[1] = trim($matches[1]);
      $attributes[] = $matches[1];
    }
    // After href.
    if (!empty($matches[6])) {
      $matches[6] = trim($matches[6]);
      $attributes[] = $matches[6];
    }
    if (count($attributes)) {
      $vars['extra_attributes'] = implode(' ', $attributes);
    }
    return $this->output($matches[4], $matches[5], $matches[7], $headers, $vars);
  }

  /**
   * Callback function for preg_replace_callback on getPatternEmailWithOptions.
   *
   * @param array $matches
   *   An array containing parts of an email address.
   *
   * @return string
   *   The span with which to replace the email address.
   */
  public function callbackEmailAddressesWithOptions(array $matches) {
    $vars = [];
    if (!empty($matches[3])) {
      $options = explode('|', $matches[3]);
      if (!empty($options[0])) {
        $custom_form_url = trim($options[0]);
        if (!empty($custom_form_url)) {
          $vars['custom_form_url'] = $custom_form_url;
        }
      }
      if (!empty($options[1])) {
        $custom_displaytext = trim($options[1]);
        if (!empty($custom_displaytext)) {
          $vars['custom_displaytext'] = $custom_displaytext;
        }
      }
    }
    return $this->output($matches[1], $matches[2], '', '', $vars);
  }

  /**
   * A helper function for the callbacks.
   *
   * Obfuscates the email address with the method chosen from the
   * system wide configuration.
   *
   * @param string $name
   *   The user name.
   * @param string $domain
   *   The email domain.
   * @param string $contents
   *   The contents of any <a> tag.
   * @param array $headers
   *   The email headers extracted from a mailto: URL @todo implement.
   * @param array $vars
   *   Optional parameters @todo implement.
   *
   * @return string
   *   The obfuscated email address as a link.
   */
  private function output($name, $domain, $contents = '', array $headers = [], array $vars = []) {
    /** @var \Drupal\obfuscate\ObfuscateMail $obfuscateMail */
    $obfuscateMail = \Drupal::service('obfuscate_mail');
    $renderer = \Drupal::service('renderer');
    $output = $obfuscateMail->getObfuscatedLink($name . '@' . $domain);
    // @todo implement spamspan coverage of contents and headers.
    return $renderer->renderRoot($output);
  }

}
