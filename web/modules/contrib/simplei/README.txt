--- Drupal 8 changes ---

This module no longer supports the Admin menu module
See this issue for details:
https://www.drupal.org/node/2605272

--- Usage ---

1. Enable the module like any other. The module works with Drupal core
   Toolbar module.

2. Enter a line like the example in your settings.local.php file.

   $settings['simple_environment_indicator'] = 'DodgerBlue';
   or
   $settings['simple_environment_indicator'] = '#1E90FF DEV';
   or
   $settings['simple_environment_indicator'] = '@production';

   The color name (or hex value) is optionally followed by environment name.
   Environment name following @ sign will have predetermined background color.
   Recognized environment names are:
   - production, prod, prd, live (matches first two chars, pr & li)
   - staging, stage, stg, test (matches first two chars, st & te)
   - development, devel, local (or any string that does not match above)

3. The indicator for logged in users appears only when Toolbar is enabled.

4. To support anonymous users, add another line in settings.local.php,

   $settings['simple_environment_anonymous'] = TRUE;

   If you do not like default rendering of the environment indicator, you can
   set to string instead of boolean value, such as,

   $settings['simple_environment_anonymous'] = "body:after { 
     content: \"STAGE\" ;
     position: fixed;
     top: 0;
     left: 0;
     padding: 0.1em 0.5em;
     font-family: monospace;
     font-weight: bold;
     color: #fff;
     background: brown;
     border: 1px solid white; }";

   You would not want to display indicator for anonymous users in production
   environment, but nothing will stop you if you have a reason to do so.

--- Suggested color and environment name ---

For production
   $settings['simple_environment_indicator'] = 'FireBrick PRD';
   $settings['simple_environment_indicator'] = '@PRD';

For staging
   $settings['simple_environment_indicator'] = 'GoldenRod STG';
   $settings['simple_environment_indicator'] = '@STG';

Fore local development
   $settings['simple_environment_indicator'] = 'DodgerBlue DEV';
   $settings['simple_environment_indicator'] = '@DEV';
