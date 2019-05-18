Extensions API

You can enable this module just like any other module. Once enabled you get a configuration page which you can find under Configuration » Development » Extensions API settings. The only setting available is the token, which you can use to view the extensions statistics. Make sure this token has a reasonable level of difficulty.

Once you have set the token you can find the Core, Module and Theme statistics on the following URL's:

All:
/rest/list/all/[token]

Core:
/rest/list/core/[token]

Module:
/rest/list/module/[token]

Theme:
/rest/list/theme/[token]

You will get the data in a JSON object which is easily read by external applications.
