<?php

/**
 * @file
 * Functions file for Capture Entity Changes.
 *
 * Functions that capture all values.
 */

/**
 * Return string values from array.
 */
function obtain_string_values(array $_values, array $_aux) {
  /* Verifico si existe una key con el nombre value
   * y si existe cambio el valor del array por value */
  for ($i = 0; $i < count($_aux['current']); $i++) {
    if (array_key_exists('value', $_aux['current'][$i])) {
      if (isset($_aux['current'][$i]['value'])) {
        $_aux['current'][$i] = (string) $_aux['current'][$i]['value'];
      }
    }
    if (array_key_exists('value', $_aux['original'][$i])) {
      if (isset($_aux['original'][$i]['value'])) {
        $_aux['original'][$i] = (string) $_aux['original'][$i]['value'];
      }
    }

    /* Verifico si se han producido cambios y lo indico con
     * un valor boolean */
    if ($_aux['current'][$i] !== $_aux['original'][$i]) {
      $_aux['updated'][$i] = TRUE;
    }
    else {
      $_aux['updated'][$i] = FALSE;
    }
  }

  return $_aux;
}

/**
 * Return not supported values from array.
 */
function obtain_no_supported_values(array $_values, array $_aux) {
  for ($i = 0; $i < count($_aux['current']); $i++) {
    $_aux['current'][$i] = t('Field type not supported');
    $_aux['original'][$i] = t('Field type not supported');
    $_aux['updated'][$i] = FALSE;
  }

  return $_aux;
}

/**
 * Return link values from array.
 */
function obtain_link_values(array $_values, array $_aux) {
  for ($i = 0; $i < count($_aux['current']); $i++) {
    if (isset($_aux['current'][$i]['title'])) {
      $_aux['current'][$i] = $_aux['current'][$i]['title'] . ' - ' . $_aux['current'][$i]['uri'];
    }
    else {
      $_aux['current'][$i] = "";
    }

    if (isset($_aux['original'][$i]['title'])) {
      $_aux['original'][$i] = $_aux['original'][$i]['title'] . ' - ' . $_aux['original'][$i]['uri'];
    }
    else {
      $_aux['original'][$i] = "";
    }

    if ($_aux['current'][$i] !== $_aux['original'][$i]) {
      $_aux['updated'][$i] = TRUE;
    }
    else {
      $_aux['updated'][$i] = FALSE;
    }
  }

  return $_aux;
}
