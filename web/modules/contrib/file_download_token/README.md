# File download token

The module provides a very basic API to grant download access to a file with a tokenized url that is valid for a certain amount of time.

The included submodule "File download token webform" uses this to enable you to provide a file download link in the handlers of a webform (e.g. in an email notification). The file can be selected in the "Confirmation"-Configuration of the webform.

## Installation and basic usage

* Add the module as usual and activate.
* File download token webform:
  * Specify a file in the "Settings" -> "Confirmation tab" under "Download token file".
  * Add the [webform:file-download-token-url]-Token to the textfield of a handler.



