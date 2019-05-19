Warning: This module is dangerous and should not be used at all costs.

After enabling the module, it will only work if specific config options are set.

The various config options to enable PHP filters for twig_php.settings are:
allow_function_execution - Allows you to execute any PHP function.
allow_require_file - Allows you to 'require' a file.
allow_include_file - Allows you to 'include' a file.
allow_php_execution - Allows you to execute arbitrary PHP code. DANGEROUS!!!

You can set config options using Drupal Console:

  To enable:
  > drupal config:override twig_php.settings allow_function_execution 1

  To disable:
  > drupal config:override twig_php.settings allow_function_execution 0

You can enable only specific functions by setting the allowed_functions config option:
> drupal config:edit twig_php.settings
allowed_functions:
  - print_r
  - var_dump
  - var_export
  - user_load

This will prevent all other function calls from working.

See /examples/examples.html.twig to see how PHP code can be executed in Twig template files.
