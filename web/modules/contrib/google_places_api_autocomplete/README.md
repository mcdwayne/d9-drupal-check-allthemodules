# Google Places API Autocomplete
Provides a widget for Drupal text fields with autocomplete suggestions from
the Google Places API.
Also you can use the autocomplete path it defines for your own FAPI
implementation.

# How to use the module
1. The module exposes a widget for text fields.
2. Set the Google Places Autocomplete widget for the text field you want.
3. When typing into the text field, you can see suggestions based on the
characters you typed.

# How to configure the module
1. Go to admin/config/services/places
2. Insert the Google API Key

# How to get a Google API key
1. Go to https://code.google.com/apis/console/
2. Create a new project
3. Enable Google Places API
4. Go to Credentials
5. Create New Key
6. Select Server key
7. Copy the API key.
8. Paste it in the module configuration page, at admin/config/services/places

# How to configure the parameters
Parameters such as location, country, language can be configured either per
field or in the general settings of the module. Documentation about the
parameters, can be found here
https://developers.google.com/places/webservice/autocomplete#place_autocomplete_requests
