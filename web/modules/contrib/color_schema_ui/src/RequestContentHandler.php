<?php

namespace Drupal\color_schema_ui;

use Symfony\Component\HttpFoundation\Request;


class RequestContentHandler {

  public function computeColorReplacement(Request $request) {
    return get_object_vars(json_decode($request->getContent()));
  }

}
