<?php

namespace Drupal\color_schema_ui;

use Leafo\ScssPhp\Compiler;


class SCSSPhpCompilerFactory {

  public function create(): Compiler {
    return new Compiler();
  }

}
