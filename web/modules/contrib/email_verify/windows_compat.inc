<?php
/**
 * @file
 * Definitions for functions that are missing from the Windows implementation of
 * PHP.
 *
 * @see http://drupal.org/node/508004
 */

if (!function_exists('getmxrr')) {
  function getmxrr($hostname, &$mxhosts, &$mxweight = FALSE) {
    return win_getmxrr($hostname, $mxhosts, $mxweight);
  }
}

/**
 * getmxrr() support for Windows prior to PHP 5.3.0 by HM2K <php [at] hm2k.org>.
 *
 * @param string $hostname
 *   The Internet host name.
 * @param array $mxhosts
 *   A list of the MX records found is placed into the array mxhosts.
 * @param array $mxweight
 *   If the weight array is given, it will be filled with the weight information
 *   gathered.
 *
 * @return boolean
 *   Returns TRUE if any records are found; returns FALSE if no records were
 *   found or if an error occurred.
 *
 * @see http://php.net/manual/function.getmxrr.php#88033
 */
function win_getmxrr($hostname, &$mxhosts, &$mxweight = FALSE) {
  if (empty($hostname)) {
    return;
  }
  if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
    return;
  }

  if (!is_array($mxhosts)) {
    $mxhosts = array();
  }

  $exec = 'nslookup -type=MX ' . escapeshellarg($hostname);
  @exec($exec, $output);
  if (empty($output)) {
    return;
  }

  $i = -1;
  foreach ($output as $line) {
    $i++;
    if (preg_match("/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.+)$/i", $line, $parts)) {
      $mxweight[$i] = trim($parts[1]);
      $mxhosts[$i] = trim($parts[2]);
    }
    if (preg_match('/responsible mail addr = (.+)$/i', $line, $parts)) {
      $mxweight[$i] = $i;
      $mxhosts[$i] = trim($parts[1]);
    }
  }

  return ($i != -1);
}

/**
 * checkdnsrr() support for Windows prior to PHP 5.3.0.
 *
 * @param string $host
 *   May either be the IP address in dotted-quad notation or the host name.
 * @param string $type
 *   May be any one of: A, MX, NS, SOA, PTR, CNAME, AAAA, A6, SRV, NAPTR, TXT or
 *   ANY.
 *
 * @return boolean
 *   Returns TRUE if any records are found; returns FALSE if no records were
 *   found or if an error occurred.
 *
 * @see http://php.net/manual/function.checkdnsrr.php
 */
if (!function_exists('checkdnsrr')) {
  function checkdnsrr($host, $type = '') {
    if (!empty($host)) {
      $type = (empty($type)) ? 'MX' : $type;
      exec('nslookup -type=' . $type . ' ' . escapeshellcmd($host), $result);
      $it = new ArrayIterator($result);
      foreach (new RegexIterator($it, '~^' . $host . '~', RegexIterator::GET_MATCH) as $result) {
        if ($result) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }
}
