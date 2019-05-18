<?php
/**
 * @file
 * Chatroll Live Chat platform extension
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
namespace Drupal\chatroll\Plugin\Block;
use Drupal\Component\Utility\Unicode;

class Chatroll {
  /**
   * Render Chatroll HTML from shortcode
   * e.g. [chatroll id="" name="" apikey=""]
   */
  public function renderChatrollHtmlFromShortcode($shortcode) {
    return preg_replace_callback('/' . self::$shortcodePattern . '/s', array($this, 'doShortcodeTag'), $shortcode);
  }

  /**
   * OVERRIDE this method with platform specific implementation!
   *  - Set user parameters for SSO integration
   */
  public function appendPlatformDefaultAttr($attr) {
    return $attr;
  }

  /**
   * Render Chatroll HTML from attributes array
   */
  public function renderChatrollHtml($attr) {
    $defaults = array(
      'domain'        => 'chatroll.com',
      // Reference platform.
      'platform'      => '',
      // Chatroll ID.
      'id'            => '',
      // Chatroll name.
      'name'          => '',
      // API key for SSO.
      'apikey'        => '',
      // Standard embed parameters.
      'width'         => '450',
      'height'        => '350',
      // Appearance values (optional; overrides server-side config).
      'bgcolor'       => '',
      'fgcolor'       => '',
      'textbgcolor'   => '',
      'textfgcolor'   => '',
      'border'        => '',
      'sound'         => '',
      // SSO parameters (optional; overrides generated values).
      'uid'           => '',
      'uname'         => '',
      'upic'          => '',
      'ulink'         => '',
      'ismod'         => '',
      'linkurl'       => '',
      'linktxt'       => '',
    );
    // Generate default parameters, based on platform specific user info.
    $defaults = $this->appendPlatformDefaultAttr($defaults);
    // Merge specified parameter values w/ defaults.
    $attr = $this->shortcodeAtts($defaults, $attr);
    $http_protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    // Generate Chatroll iframe.
    $output = '<iframe width="' . $attr['width'] . '" height="' . $attr['height'] . '"';
    $output .= ' frameborder="0" scrolling="no" marginheight="0" marginwidth="0" allowtransparency="true"';
    $output .= ' src="' . $http_protocol . '://' . $attr['domain'] . '/embed/chat/' . $attr['name'] . '?platform=' . $attr['platform'];
    // Add standard GET parameters.
    if (!empty($attr['id'])) {
      $output .= '&id=' . $attr['id'];
    }
    if (!empty($attr['fgcolor'])) {
      $output .= '&fgcolor=' . $attr['fgcolor'];
    }
    if (!empty($attr['bgcolor'])) {
      $output .= '&bgcolor=' . $attr['bgcolor'];
    }
    if (!empty($attr['textfgcolor'])) {
      $output .= '&textfgcolor=' . $attr['textfgcolor'];
    }
    if (!empty($attr['textbgcolor'])) {
      $output .= '&textbgcolor=' . $attr['textbgcolor'];
    }
    if (!empty($attr['sound']) || $attr['sound'] == '0') {
      $output .= '&sound=' . $attr['sound'];
    }
    if (!empty($attr['border']) || $attr['border'] == '0') {
        $output .= '&border=' . $attr['border'];
    }

    // Add SSO parameters.
    // Always append uid to indicate SSO request;
    // Empty uid or uid=0 indicates sign out.
    $output .= '&uid=' . $attr['uid'];
    if (!empty($attr['uname'])) {
      // Convert usernames to Chatroll compatible format:.
      // 1) Trim whitespaces from begin/end.
      // 2) Limit length to 64 .
      $attr['uname'] = trim($attr['uname']);
      
      if (Unicode::strlen($attr['uname']) > 64) {
        $uname = Unicode::substr($attr['uname'], 0, 64);
      }
      $output .= '&uname=' . urlencode($attr['uname']);
    }

    if (!empty($attr['ismod']) || $attr['ismod'] == '0') {
      $output .= '&ismod=' . $attr['ismod'];
    }

    if (!empty($attr['upic'])) {
      $output .= '&upic=' . urlencode($attr['upic']);
    }
    if (!empty($attr['ulink'])) {
      $output .= '&ulink=' . urlencode($attr['ulink']);
    }
    $output .= '&sig=' . md5($attr['uid'] . $attr['uname'] . $attr['ismod'] . $attr['apikey']);

    $output .= '&w=$0';
    $output .= '"></iframe>';

    if (!empty($attr['linkurl']) && !empty($attr['linktxt'])) {
      $output .= '<small style="display:block;width:' . $attr['width'] . 'px;">';
      $output .= '<a style="margin-left:8px;color:#A0A0A0;" href="http://' . $attr['domain'] . $attr['linkurl'] . '" alt="' . $attr['linktxt'] . '">' . $attr['linktxt'] . '</a>';
      $output .= '</small>';
    }

    return $output;
  }


  /**
   * Shortcode functions
   */
  // Chatroll shortcode regex pattern.
  // e.g. [chatroll width="500" height="400" name="" id="" apikey=""].
  static $shortcodePattern = '\[chatroll\b(.*?)(?:(\/))?\]';
  static $shortcodeAttsPattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

  /**
   * Parase shortcode attributes.
   */
  public function shortcodeParseAtts($text) {
    $atts = array();
    $text = preg_replace('/[\x{00a0}\x{200b}]+/u', ' ', $text);
    if (preg_match_all(self::$shortcodeAttsPattern, $text, $match, PREG_SET_ORDER)) {
      foreach ($match as $m) {
        if (!empty($m[1])) {
          $atts[strtolower($m[1])] = stripcslashes($m[2]);
        }
        elseif (!empty($m[3])) {
          $atts[strtolower($m[3])] = stripcslashes($m[4]);
        }
        elseif (!empty($m[5])) {
          $atts[strtolower($m[5])] = stripcslashes($m[6]);
        }
        elseif (isset($m[7]) and drupal_strlen($m[7])) {
          $atts[] = stripcslashes($m[7]);
        }
        elseif (isset($m[8])) {
          $atts[] = stripcslashes($m[8]);
        }
      }
    }
    else {
      $atts = ltrim($text);
    }
    return $atts;
  }

  /**
   * Combine default values w/ extracted attributes array.
   */
  public function shortcodeAtts($pairs, $atts) {
    $atts = (array) $atts;
    $out = array();
    foreach ($pairs as $name => $default) {
      if (array_key_exists($name, $atts)) {
        $out[$name] = $atts[$name];
      }
      else {
        $out[$name] = $default;
      }
    }
    return $out;
  }
  /**
   * Return rendered html code.
   */
  public function doShortcodeTag($m) {
    $attr = $this->shortcodeParseAtts($m[1]);
    return $this->renderChatrollHtml($attr);
  }
}