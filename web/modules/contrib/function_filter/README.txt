Text filter for replace tokens [function:*] on function result.

Installation:
  1. Install module
  2. Enable filter "Function filter" on text format form.

Usage:

  1. Implements hook_filter_functions():

    <?php
    function mymodule_filter_functions() {
      return [
        'current_date' => [
          'function' => 'mymodule_current_date',
          'cache' => FALSE,
        ],
      ];
    }
    ?>

  2. Write mymodule_current_date() function:

    <?php
    function mymodule_current_date($format = 'r') {
      return date($format);
    }
    ?>

  3. Add in text token <code>[function:current_date:d.m.Y]</code> or [function:current_date].
