Description:
---------------
The Wunderground weather module provides two blocks, one with current weather
conditions and one with weatherforecasts for a certain location. The weather
data is retrieved by making calls to the Wunderground API.

In order to use this module, you will need to create an API at
http://www.wunderground.com/weather/api. You can either choose for a free
account or you can sign up for a payed account, in which case you will be
allowed to make lot more requests per day.

Once you have obtained your API key, you can go to the Wunderground weather
settings page and fill in the key. Here you should also enter the language you 
would like to use and the location in which you want your blocks to be
displayed. On the settings form you can choose to use caching or not. Caching 
will reduce the number of calls to Wunderground and is a lot faster. If you
choose not to cache, you will have the most up to date data in your blocks.
When you have completed the settings form, you can place your block on the
page.

How to Install:
---------------
Get your API key at http://www.wunderground.com/weather/api
Enable this module
Go to admin/config/services/wunderground-weather
Fill in your api key and the WUnderground api url
Choose your language and location
Select if you want to use cache or not
Place your blocks on the page
