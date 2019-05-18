# JavaScript Manager

Allows external and inline JS to be added to pages.

## Installation
 * Install as you would normally install a contributed Drupal module.
   See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

## Permissions
* `Manage JavaScript Items`: Allows management of external & inline JavaScript.

## Usage
JavaScript items can be configured at `/admin/structure/js_manager`

  | Property               | Description                                      |
  | ---------------------- | ------------------------------------------------ |
  | Name                   | Descriptive machine name for the JavaScript item |
  | Type                   | Internal / External                              |
  | External URL           | Full URL to external script                      |
  | Load Asynchronously    | Load the external script asynchronously          |
  | Exclude on admin paths | Excludes the script on admin paths               |
  | Snippet                | Inline JavaScript snippet                        |
  | Weight                 | Script weight (integer) to control ordering      |
  | Scope                  | Header / Footer                                  |
