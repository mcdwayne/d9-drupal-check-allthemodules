Language Tokens
===============
The token modules provides only tokens about the language a node is in.

There are use cases where, for example a field help text, needs to link to
another page prefixed by the current interface language code.

The Language Tokens module adds the missing tokens about the interface language:

* Current language name.
* Current language code.


Installation
------------
This module requires the token module.

* Download and extract this module to the modules/contrib directory or download using composer.
* Enable the module.


Usage
-----
Use the token through the tokens interface:

* [current-language:name] : The current language name.
* [current-language:code] : The current language code.
