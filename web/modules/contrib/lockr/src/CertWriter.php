<?php

namespace Drupal\lockr;

class CertWriter {

  public static function writeCerts($env, $texts) {
    $base = "private://lockr/{$env}";
    @mkdir($base, 0750, TRUE);

    $key_file = "{$base}/key.pem";
    $key_fp = fopen($key_file, 'w');
    fwrite($key_fp, $texts['key_text']);
    fclose($key_fp);
    chmod($key_file, 0640);

    $cert_file = "{$base}/crt.pem";
    $cert_fp = fopen($cert_file, 'w');
    fwrite($cert_fp, $texts['cert_text']);
    fclose($cert_fp);
    chmod($cert_file, 0640);

    $pair_file = "{$base}/pair.pem";
    $pair_fp = fopen($pair_file, 'w');
    fwrite($pair_fp, $texts['key_text']);
    fwrite($pair_fp, $texts['cert_text']);
    fclose($pair_fp);
    chmod($pair_file, 0640);
  }

}
