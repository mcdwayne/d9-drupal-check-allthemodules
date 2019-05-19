<?php
/**
 * @file
 * Include file for urllogin security functions.
 * This module is designed for easy drop-in replacement where an alternative
 * encryption model is required.
 */

/**
 * Converts integer to 4-character string containing hashed values.
 *
 * The characters contain 8-bit binary values rather than normal characters.
 * If integer is greater than a 32 bit value, only the lower 32 bits are used.
 *
 * @param $i
 *   Integer to generate hash string.
 *
 * @return
 *   Four byte string of characters, each capable of 256 possible values.
 */
function urllogin_inthash($i) {
  $s = hash('sha256', $i & 0x7FFFFFFF, TRUE);
  return (ord($s[0]) << 24) | (ord($s[1]) << 16) | (ord($s[2]) << 8) | ord($s[3]);
}

/**
 * Encrypts a pair of integers
 *
 * The encryption uses a "butterfly" technique similar to the DES's Feistel
 * scheme.
 * See @link http://en.wikipedia.org/wiki/Data_Encryption_Standard for more
 * details of the DES @endlink Instead of the Feistel function, sha256 is used.
 * (This is probably way overkill but it is easy to code.) Another encryption
 * algorithm can be plugged in here if desired.
 *
 * @param $i
 *   First Integer, passed by reference
 *
 * @param $j
 *   Second Integer, passed by reference
 *
 * @param $x
 *   Extra byte, passed by reference
 *
 * @param $passkey
 *   String containing encryption key phrase
 *
 * Nothin is returned.
 */
function urllogin_encrypt(&$i, &$j, &$x, $passkey) {
  $k = hash('sha256', $passkey, TRUE); // sha256 produces 32 bytes, so could do 16 interations
  for ($iter = 0; $iter < 8; $iter++) {
    $tmp = $i;
    $i = $j ^ urllogin_inthash($i) ^ ((ord($k[$iter * 2]) << 8) | ord($k[$iter * 2 + 1]));
    $j = $tmp;
  }
  $x = ($i ^ $j ^ ord($k[16])) & 0xFF; // extra byte for added security
}

/**
 * Decrypts a pair of integers
 *
 * Exact reverse of encryption:
 * The values of $i and $j are swapped.
 * The key is applied in the reverse order to encryption.
 *
 * @param $j
 *   First Integer, passed by reference
 *
 * @param $i
 *   Second Integer, passed by reference
 *
 * @param $x
 *   Extra byte, passed by reference
 *
 * @param $passkey
 *   String containing encryption key phrase
 *
 * @return
 *   TRUE if successful, FALSE if extra byte fails
 *   Note that a TRUE return does not mean security checks are past.
 *   This is just an added level of security to help with diagnostics.
 */
function urllogin_decrypt(&$j, &$i, &$x, $passkey) {
  $k = hash('sha256', $passkey, TRUE); // sha256 produces 32 bytes, so could do 16 interations
  if ($x != (($i ^ $j ^ ord($k[16])) & 0xFF)) {
    return FALSE;
  } // extra byte for added security
  for ($iter = 0; $iter < 8; $iter++) {
    $tmp = $i;
    $i = $j ^ urllogin_inthash($i) ^ ((ord($k[14 - $iter * 2]) << 8) | ord($k[14 - $iter * 2 + 1]));
    $j = $tmp;
  }
  return TRUE;
}

/**
 * Converts a pair of integers plus an extra byte into a base64url encoded
 * string
 *
 * If integer is greater than a 32 bit value, only the lower 32 bits are used.
 *
 * @param $i
 *   First Integer
 *
 * @param $j
 *   Second Integer
 *
 * @param $x
 *   Extra byte
 *
 * @return
 *   Return base64url encoded string which will be 11 characters long
 *   since the '=' is stripped off the end.
 */
function urllogin_base64enc($i, $j, $x) {
  return strtr(
    base64_encode(
      chr(($i >> 24) & 0xff) . chr(($i >> 16) & 0xff) . chr(($i >> 8) & 0xff) . chr($i & 0xff) .
      chr(($j >> 24) & 0xff) . chr(($j >> 16) & 0xff) . chr(($j >> 8) & 0xff) . chr($j & 0xff) .
      chr($x & 0xff)
    ),
    '+/=', '-_,'); // modify to use base64url encoding
}

/**
 * Converts a base64url encoded string into a pair of integers plus an extra
 * byte
 *
 * @param $i
 *   First Integer, passed by reference
 *
 * @param $j
 *   Second Integer, passed by reference
 *
 * @param $x
 *   Extra byte, passed by reference
 *
 * @param $urlstr
 *   base64 encoded string with the '=' stripped off the end
 *
 * @return
 *   Return TRUE if successful, FALSE if $urlstr was invalid base64url
 */
function urllogin_base64dec(&$i, &$j, &$x, $urlstr) {
  if (strlen($urlstr) != 12) {  // do not use drupal_strlen because this is a binary string, not UTF
    return FALSE; // URL wrong length
  }
  // modify to use base64url decoding and decode  if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
  $s = base64_decode(strtr($urlstr, '-_,', '+/='), TRUE);

  if ($s == FALSE) {
    return FALSE;
  }
  $i = (ord($s[0]) << 24) | (ord($s[1]) << 16) | (ord($s[2]) << 8) | ord($s[3]);
  $j = (ord($s[4]) << 24) | (ord($s[5]) << 16) | (ord($s[6]) << 8) | ord($s[7]);
  $x = ord($s[8]);
  return TRUE;
}

/**
 * Encodes a user ID into an encoded url string.
 *
 * @param $uid
 *   user ID to be encoded
 *
 * @param $codekey
 *   Integer containing current active code
 *
 * @param $passkey
 *   String containing encryption key phrase
 *
 * @return
 *    encoded url string
 */
function urllogin_encode($uid, $codekey, $passkey) {
  // first encrypt the two values
  urllogin_encrypt($uid, $codekey, $x, $passkey);
  // then encode them into a string suitable for embedding into a URL
  return urllogin_base64enc($uid, $codekey, $x);
}

/**
 * Decodes an encoded url string into a user ID and tests validity.
 * If the uid matches the current one supplied, then it is valid even if link
 * is expired. This is so that the user for whom the link is intended does not
 * get an error message if they are logged in and click on an old link, but
 * instead still get redirected.
 *
 * @param string $urlstr
 *   encoded url string
 *
 * @param int $codekey
 *   Integer containing current active code (maximum allowable value)
 *
 * @param int $codemin
 *   Integer containing minimum allowable value of code
 *
 * @param string $passkey
 *   String containing encryption key phrase
 *
 * @param string $errormsg
 *   Contains error message if function fails.
 *
 * @param int $currentuid
 *   Contains optional current uid
 *
 * @return int
 *   Return UID if successful, -1 if fail, -2 if link expired
 */
function urllogin_decode($urlstr, $codekey, $codemin, $passkey, &$errormsg, $currentuid = -1) {
  if (!urllogin_base64dec($i, $j, $x, $urlstr)) {
    $errormsg = 'Invalid Base64 URL string';
    return -1;
  }
  if (!urllogin_decrypt($i, $j, $x, $passkey)) {
    $errormsg = "Invalid access string";
    return -1;
  }
  if ((($j < $codemin) or ($j > $codekey)) and ($i != $currentuid)) {
    $errormsg = "code: $j outside permitted range: $codemin to $codekey";
    return -2;
  }
  return $i;
}

