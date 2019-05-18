---SUMMARY---

This module allows the access the Microsoft Translator API.

For a full description visit project page:
https://www.drupal.org/project/microsoft_translator_api

Bug reports, feature suggestions and latest developments:
http://drupal.org/project/issues/microsoft_translator_api


---INTRODUCTION---


Follow the instructions on 
http://docs.microsofttranslator.com/text-translate.html
for getting your key, then submit this key on the module's 
administration page at 
admin/config/services/microsoft_translator_api

If the key is valid, you can use translation services as the follow:

microsoft_translator_api_detect('Submit')
This call returns the code of the detected language (en)

microsoft_translator_api_translate('Submit', 'en', 'hu')
This call returns the translated text from English to Hungarian.

There is a last, "force" parameter for these functions, and when
that is true, then the usage permission check skipped.

You can translate 2 million characters per month for free.


---REQUIREMENTS---


A Microsoft Translator Text API key.


---INSTALLATION---


Install as usual. Place the entirety of this directory in the /modules 
folder of your Drupal installation. 


---CONFIGURATION---


Set your Microsoft Translator Text API key on
admin/config/services/microsoft-translator-api.


---CONTACT---

Current Maintainers:
*Balogh Zoltán (zlyware) - https://www.drupal.org/u/u/zoltán-balogh
