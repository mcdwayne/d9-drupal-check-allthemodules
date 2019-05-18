# Modal field formatter

Allows fields to be opened in a drupal modal by clicking a link that replaces the default field output.

## Installation and basic usage

* Add the module as usual and activate.
* Tick the checkbox in the field display settings and specify a placeholder. This can be:
  * A simple placeholder text
  * The output of a visible field of the same view mode. The original field will get hidden if defined as a placeholder.
* The field output gets replaced with a link. Clicking the link displays the modal with the field output.