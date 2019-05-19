// Create angular app object named translate
var app = angular.module('translate', ['ngAnimate', 'ui.bootstrap', 'ngSanitize', 'ui.select']);

// Factory object for translate angular app.
// Used to do RESTful API calls to translate.com
app.factory('app_factory', ['$http', function ($http) {
  'use strict';
  // Base URL of translate.com REST API
  var base_url = 'https://www.translate.com/api_v2';
  var api_key = 'GrsYcRGeyWszS94M2my8J2ZwsZxBFG';
  var app_factory = {};

  // Transform the request data before sending the http request
  app_factory.trasform_request = function (obj) {
    var str = [];
    for (var p in obj) {
      if (Array.isArray(obj[p])) {
        for (var val in obj[p]) {
          if (val instanceof String) {
            str.push(encodeURIComponent(p) + '[]=' + encodeURIComponent(obj[p][val]));
          }
        }
      }
      else {
        str.push(encodeURIComponent(p) + '=' + encodeURIComponent(obj[p]));
      }
    }
    return str.join('&');
  };

  // Get all languages supported by translate.com and no of speakers for each language
  app_factory.get_all_language_names = function () {
    var url = base_url + '/get_languages';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: {api_key: api_key},
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Check if website is already added fon Translate.com for translation
  app_factory.check_url = function (hostname) {
    var url = base_url + '/check_url';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: {api_key: api_key, website_url: hostname},
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Get all language pairs supported for translation
  app_factory.get_language_pairs = function () {
    var url = base_url + '/get_all_human_translations_languages';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: {api_key: api_key},
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Create a user account with translate.com
  app_factory.create_translate_user = function (data) {
    var url = base_url + '/create_user';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Login to user account with translate.com
  app_factory.login_translate_user = function (data) {
    var url = base_url + '/login_user';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Change the user plan with translate.com account
  app_factory.change_user_plan = function (data) {
    var url = base_url + '/change_plan';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Get user plan details
  app_factory.get_plan = function (data) {
    var url = base_url + '/get_plan_status';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Add new site for translation for current user account on translate.com
  app_factory.add_new_site = function (data) {
    var url = base_url + '/add_new_site';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Get code for already registered site
  app_factory.get_embed_code = function (data) {
    var url = base_url + '/get_code';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Save embed code in Drupal configs
  app_factory.save_embed_code = function (url) {
    return $http({
      method: 'GET',
      url: url
    });
  };

  // Call plugin install API to inform translate team for new plugin install
  app_factory.plugin_install = function (data) {
    var url = base_url + '/website_translator_install';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Save credit card information of a user
  app_factory.save_credit_card = function (data) {
    var url = base_url + '/add_edit_credit_card';
    return $http({
      method: 'POST',
      url: url,
      transformRequest: app_factory.trasform_request,
      data: data,
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    });
  };

  // Delete embed code from drupal configs
  app_factory.sign_out = function (url) {
    return $http({
      method: 'GET',
      url: url
    });
  };

  // return the factory object
  return app_factory;
}]);

app.controller('app_controller', ['$scope', '$timeout', '$uibModal', '$log', 'app_factory', function ($scope, $timeout, $uibModal, $log, app_factory) {
  'use strict';
  // Step variable for tracking on which step we are in registration process
  $scope.step = 1;

  // JSON object which contains plans related information
  $scope.plans = [
    {name: 'Free', langs_count: 1, plan_id: 1, page_views: '10,000', ht: '200', mt: '100,000', icon: 'bg-heart', price: 0},
    {name: 'Starter', langs_count: 5, plan_id: 38, page_views: '250,000', ht: '500', mt: '150,000', icon: 'bg-rocket', price: 59},
    {name: 'Professional', langs_count: 15, plan_id: 44, page_views: '2,500,000', ht: '2500', mt: '750,000', icon: 'bg-briefcase', price: 299},
    {name: 'Enterprise', langs_count: 37, plan_id: 50, page_views: '10,000,000', ht: '10,000', mt: '3,000,000', icon: 'bg-building', price: 1199}
  ];

  $scope.credit_cards = [
    {file: 'visa.png', pattern: /^4[0-9]{12}(?:[0-9]{3})?$/},
    {file: 'master.png', pattern: /^5[1-5][0-9]{14}$/},
    {file: 'amex.png', pattern: /^3[47][0-9]{13}$/},
    {file: 'diners.png', pattern: /^3(?:0[0-5]|[68][0-9])[0-9]{11}$/},
    {file: 'discover.png', pattern: /^6(?:011|5[0-9]{2})[0-9]{12}$/},
    {file: 'jcb.png', pattern: /^(?:2131|1800|35\\d{3})\\d{11}$/}
  ];

  $scope.countries = [
    {name: 'Afghanistan', code: 'AF'},
    {name: 'Åland Islands', code: 'AX'},
    {name: 'Albania', code: 'AL'},
    {name: 'Algeria', code: 'DZ'},
    {name: 'American Samoa', code: 'AS'},
    {name: 'AndorrA', code: 'AD'},
    {name: 'Angola', code: 'AO'},
    {name: 'Anguilla', code: 'AI'},
    {name: 'Antarctica', code: 'AQ'},
    {name: 'Antigua and Barbuda', code: 'AG'},
    {name: 'Argentina', code: 'AR'},
    {name: 'Armenia', code: 'AM'},
    {name: 'Aruba', code: 'AW'},
    {name: 'Australia', code: 'AU'},
    {name: 'Austria', code: 'AT'},
    {name: 'Azerbaijan', code: 'AZ'},
    {name: 'Bahamas', code: 'BS'},
    {name: 'Bahrain', code: 'BH'},
    {name: 'Bangladesh', code: 'BD'},
    {name: 'Barbados', code: 'BB'},
    {name: 'Belarus', code: 'BY'},
    {name: 'Belgium', code: 'BE'},
    {name: 'Belize', code: 'BZ'},
    {name: 'Benin', code: 'BJ'},
    {name: 'Bermuda', code: 'BM'},
    {name: 'Bhutan', code: 'BT'},
    {name: 'Bolivia', code: 'BO'},
    {name: 'Bosnia and Herzegovina', code: 'BA'},
    {name: 'Botswana', code: 'BW'},
    {name: 'Bouvet Island', code: 'BV'},
    {name: 'Brazil', code: 'BR'},
    {name: 'British Indian Ocean Territory', code: 'IO'},
    {name: 'Brunei Darussalam', code: 'BN'},
    {name: 'Bulgaria', code: 'BG'},
    {name: 'Burkina Faso', code: 'BF'},
    {name: 'Burundi', code: 'BI'},
    {name: 'Cambodia', code: 'KH'},
    {name: 'Cameroon', code: 'CM'},
    {name: 'Canada', code: 'CA'},
    {name: 'Cape Verde', code: 'CV'},
    {name: 'Cayman Islands', code: 'KY'},
    {name: 'Central African Republic', code: 'CF'},
    {name: 'Chad', code: 'TD'},
    {name: 'Chile', code: 'CL'},
    {name: 'China', code: 'CN'},
    {name: 'Christmas Island', code: 'CX'},
    {name: 'Cocos (Keeling) Islands', code: 'CC'},
    {name: 'Colombia', code: 'CO'},
    {name: 'Comoros', code: 'KM'},
    {name: 'Congo', code: 'CG'},
    {name: 'Congo, The Democratic Republic of the', code: 'CD'},
    {name: 'Cook Islands', code: 'CK'},
    {name: 'Costa Rica', code: 'CR'},
    {name: 'Cote D\'Ivoire', code: 'CI'},
    {name: 'Croatia', code: 'HR'},
    {name: 'Cuba', code: 'CU'},
    {name: 'Cyprus', code: 'CY'},
    {name: 'Czech Republic', code: 'CZ'},
    {name: 'Denmark', code: 'DK'},
    {name: 'Djibouti', code: 'DJ'},
    {name: 'Dominica', code: 'DM'},
    {name: 'Dominican Republic', code: 'DO'},
    {name: 'Ecuador', code: 'EC'},
    {name: 'Egypt', code: 'EG'},
    {name: 'El Salvador', code: 'SV'},
    {name: 'Equatorial Guinea', code: 'GQ'},
    {name: 'Eritrea', code: 'ER'},
    {name: 'Estonia', code: 'EE'},
    {name: 'Ethiopia', code: 'ET'},
    {name: 'Falkland Islands (Malvinas)', code: 'FK'},
    {name: 'Faroe Islands', code: 'FO'},
    {name: 'Fiji', code: 'FJ'},
    {name: 'Finland', code: 'FI'},
    {name: 'France', code: 'FR'},
    {name: 'French Guiana', code: 'GF'},
    {name: 'French Polynesia', code: 'PF'},
    {name: 'French Southern Territories', code: 'TF'},
    {name: 'Gabon', code: 'GA'},
    {name: 'Gambia', code: 'GM'},
    {name: 'Georgia', code: 'GE'},
    {name: 'Germany', code: 'DE'},
    {name: 'Ghana', code: 'GH'},
    {name: 'Gibraltar', code: 'GI'},
    {name: 'Greece', code: 'GR'},
    {name: 'Greenland', code: 'GL'},
    {name: 'Grenada', code: 'GD'},
    {name: 'Guadeloupe', code: 'GP'},
    {name: 'Guam', code: 'GU'},
    {name: 'Guatemala', code: 'GT'},
    {name: 'Guernsey', code: 'GG'},
    {name: 'Guinea', code: 'GN'},
    {name: 'Guinea-Bissau', code: 'GW'},
    {name: 'Guyana', code: 'GY'},
    {name: 'Haiti', code: 'HT'},
    {name: 'Heard Island and Mcdonald Islands', code: 'HM'},
    {name: 'Holy See (Vatican City State)', code: 'VA'},
    {name: 'Honduras', code: 'HN'},
    {name: 'Hong Kong', code: 'HK'},
    {name: 'Hungary', code: 'HU'},
    {name: 'Iceland', code: 'IS'},
    {name: 'India', code: 'IN'},
    {name: 'Indonesia', code: 'ID'},
    {name: 'Iran, Islamic Republic Of', code: 'IR'},
    {name: 'Iraq', code: 'IQ'},
    {name: 'Ireland', code: 'IE'},
    {name: 'Isle of Man', code: 'IM'},
    {name: 'Israel', code: 'IL'},
    {name: 'Italy', code: 'IT'},
    {name: 'Jamaica', code: 'JM'},
    {name: 'Japan', code: 'JP'},
    {name: 'Jersey', code: 'JE'},
    {name: 'Jordan', code: 'JO'},
    {name: 'Kazakhstan', code: 'KZ'},
    {name: 'Kenya', code: 'KE'},
    {name: 'Kiribati', code: 'KI'},
    {name: 'Korea, Democratic People\'S Republic of', code: 'KP'},
    {name: 'Korea, Republic of', code: 'KR'},
    {name: 'Kuwait', code: 'KW'},
    {name: 'Kyrgyzstan', code: 'KG'},
    {name: 'Lao People\'S Democratic Republic', code: 'LA'},
    {name: 'Latvia', code: 'LV'},
    {name: 'Lebanon', code: 'LB'},
    {name: 'Lesotho', code: 'LS'},
    {name: 'Liberia', code: 'LR'},
    {name: 'Libyan Arab Jamahiriya', code: 'LY'},
    {name: 'Liechtenstein', code: 'LI'},
    {name: 'Lithuania', code: 'LT'},
    {name: 'Luxembourg', code: 'LU'},
    {name: 'Macao', code: 'MO'},
    {name: 'Macedonia, The Former Yugoslav Republic of', code: 'MK'},
    {name: 'Madagascar', code: 'MG'},
    {name: 'Malawi', code: 'MW'},
    {name: 'Malaysia', code: 'MY'},
    {name: 'Maldives', code: 'MV'},
    {name: 'Mali', code: 'ML'},
    {name: 'Malta', code: 'MT'},
    {name: 'Marshall Islands', code: 'MH'},
    {name: 'Martinique', code: 'MQ'},
    {name: 'Mauritania', code: 'MR'},
    {name: 'Mauritius', code: 'MU'},
    {name: 'Mayotte', code: 'YT'},
    {name: 'Mexico', code: 'MX'},
    {name: 'Micronesia, Federated States of', code: 'FM'},
    {name: 'Moldova, Republic of', code: 'MD'},
    {name: 'Monaco', code: 'MC'},
    {name: 'Mongolia', code: 'MN'},
    {name: 'Montserrat', code: 'MS'},
    {name: 'Morocco', code: 'MA'},
    {name: 'Mozambique', code: 'MZ'},
    {name: 'Myanmar', code: 'MM'},
    {name: 'Namibia', code: 'NA'},
    {name: 'Nauru', code: 'NR'},
    {name: 'Nepal', code: 'NP'},
    {name: 'Netherlands', code: 'NL'},
    {name: 'Netherlands Antilles', code: 'AN'},
    {name: 'New Caledonia', code: 'NC'},
    {name: 'New Zealand', code: 'NZ'},
    {name: 'Nicaragua', code: 'NI'},
    {name: 'Niger', code: 'NE'},
    {name: 'Nigeria', code: 'NG'},
    {name: 'Niue', code: 'NU'},
    {name: 'Norfolk Island', code: 'NF'},
    {name: 'Northern Mariana Islands', code: 'MP'},
    {name: 'Norway', code: 'NO'},
    {name: 'Oman', code: 'OM'},
    {name: 'Pakistan', code: 'PK'},
    {name: 'Palau', code: 'PW'},
    {name: 'Palestinian Territory, Occupied', code: 'PS'},
    {name: 'Panama', code: 'PA'},
    {name: 'Papua New Guinea', code: 'PG'},
    {name: 'Paraguay', code: 'PY'},
    {name: 'Peru', code: 'PE'},
    {name: 'Philippines', code: 'PH'},
    {name: 'Pitcairn', code: 'PN'},
    {name: 'Poland', code: 'PL'},
    {name: 'Portugal', code: 'PT'},
    {name: 'Puerto Rico', code: 'PR'},
    {name: 'Qatar', code: 'QA'},
    {name: 'Reunion', code: 'RE'},
    {name: 'Romania', code: 'RO'},
    {name: 'Russian Federation', code: 'RU'},
    {name: 'RWANDA', code: 'RW'},
    {name: 'Saint Helena', code: 'SH'},
    {name: 'Saint Kitts and Nevis', code: 'KN'},
    {name: 'Saint Lucia', code: 'LC'},
    {name: 'Saint Pierre and Miquelon', code: 'PM'},
    {name: 'Saint Vincent and the Grenadines', code: 'VC'},
    {name: 'Samoa', code: 'WS'},
    {name: 'San Marino', code: 'SM'},
    {name: 'Sao Tome and Principe', code: 'ST'},
    {name: 'Saudi Arabia', code: 'SA'},
    {name: 'Senegal', code: 'SN'},
    {name: 'Serbia and Montenegro', code: 'CS'},
    {name: 'Seychelles', code: 'SC'},
    {name: 'Sierra Leone', code: 'SL'},
    {name: 'Singapore', code: 'SG'},
    {name: 'Slovakia', code: 'SK'},
    {name: 'Slovenia', code: 'SI'},
    {name: 'Solomon Islands', code: 'SB'},
    {name: 'Somalia', code: 'SO'},
    {name: 'South Africa', code: 'ZA'},
    {name: 'South Georgia and the South Sandwich Islands', code: 'GS'},
    {name: 'Spain', code: 'ES'},
    {name: 'Sri Lanka', code: 'LK'},
    {name: 'Sudan', code: 'SD'},
    {name: 'Suriname', code: 'SR'},
    {name: 'Svalbard and Jan Mayen', code: 'SJ'},
    {name: 'Swaziland', code: 'SZ'},
    {name: 'Sweden', code: 'SE'},
    {name: 'Switzerland', code: 'CH'},
    {name: 'Syrian Arab Republic', code: 'SY'},
    {name: 'Taiwan, Province of China', code: 'TW'},
    {name: 'Tajikistan', code: 'TJ'},
    {name: 'Tanzania, United Republic of', code: 'TZ'},
    {name: 'Thailand', code: 'TH'},
    {name: 'Timor-Leste', code: 'TL'},
    {name: 'Togo', code: 'TG'},
    {name: 'Tokelau', code: 'TK'},
    {name: 'Tonga', code: 'TO'},
    {name: 'Trinidad and Tobago', code: 'TT'},
    {name: 'Tunisia', code: 'TN'},
    {name: 'Turkey', code: 'TR'},
    {name: 'Turkmenistan', code: 'TM'},
    {name: 'Turks and Caicos Islands', code: 'TC'},
    {name: 'Tuvalu', code: 'TV'},
    {name: 'Uganda', code: 'UG'},
    {name: 'Ukraine', code: 'UA'},
    {name: 'United Arab Emirates', code: 'AE'},
    {name: 'United Kingdom', code: 'GB'},
    {name: 'United States', code: 'US'},
    {name: 'United States Minor Outlying Islands', code: 'UM'},
    {name: 'Uruguay', code: 'UY'},
    {name: 'Uzbekistan', code: 'UZ'},
    {name: 'Vanuatu', code: 'VU'},
    {name: 'Venezuela', code: 'VE'},
    {name: 'Viet Nam', code: 'VN'},
    {name: 'Virgin Islands, British', code: 'VG'},
    {name: 'Virgin Islands, U.S.', code: 'VI'},
    {name: 'Wallis and Futuna', code: 'WF'},
    {name: 'Western Sahara', code: 'EH'},
    {name: 'Yemen', code: 'YE'},
    {name: 'Zambia', code: 'ZM'},
    {name: 'Zimbabwe', code: 'ZW'}
  ];

  var initialize = function () {
    // No. of languages supported for current plan
    $scope.langs_count = 0;
    // Setting defualt plan to free plan
    $scope.plan = $scope.plans[0];
    // Setting plan search query to empty
    $scope.query = '';
    $scope.direction = 1;
    $scope.err_msg = '';
    var domain = (!$scope.user) ? localStorage.getItem('domain') : $scope.user.domain;
    $scope.show_card_logo = false;

    // Setting default user information
    $scope.user = {
      language: 'en',
      translation_languages: [],
      domain: domain
    };
    $scope.password_type = 'password';
    $scope.cf_password_type = 'password';
    $scope.payment = {device_data: {}};
    $scope.is_card_saving = false;
  };

  // Initialize variables
  initialize();

  // Load all supported languages
  app_factory.get_all_language_names()
    .then(function (res) {
      // Save the response in speakers object
      $scope.speakers = res.data;

      // Get language pairs supported by translate.com
      app_factory.get_language_pairs()
        .then(function (res) {
          var start = '';
          // Save the response in langs_pairs object
          $scope.langs_pairs = res.data.languages;

          // Create soure_langs object
          $scope.source_langs = [];
          Object.keys($scope.langs_pairs)
            .map(function (i, val) {
              if ($scope.langs_pairs[i].source_language_code !== start) {
                start = $scope.langs_pairs[i].source_language_code;
                $scope.source_langs.push({
                  name: $scope.langs_pairs[i].source_language_name,
                  abbr: $scope.langs_pairs[i].source_language_code
                });
              }
            });

          // Update translation langs for default language
          $scope.update_translation_langs();
        });
    });

  // Site is already registered for translation
  if (localStorage.getItem('is_registered')) {
    $scope.step = 6;
  }

  // Update translation languages array for newly selected source language
  $scope.update_translation_langs = function () {
    $scope.trans_langs = [];

    for (var i in $scope.langs_pairs) {
      if ($scope.langs_pairs[i].source_language_code === $scope.user.language) {
        var lang = {
          name: $scope.langs_pairs[i].translation_language_name,
          abbr: $scope.langs_pairs[i].translation_language_code,
          speakers: $scope.speakers[$scope.langs_pairs[i].translation_language_code].speakers
        };
        $scope.trans_langs.push(lang);
      }
    }

    // Reset everything when sourece language changes
    $scope.user.translation_languages = [];
    $scope.langs_count = 0;
    $scope.query = '';
  };

  // Detect credit card
  $scope.detect_card = function () {
    for (var i in $scope.credit_cards) {
      if ($scope.credit_cards[i].pattern.test($scope.payment.card_number)) {
        $scope.credit_card_logo = $scope.credit_cards[i].file;
        $scope.show_card_logo = true;
      }
    }
  };

  // Check if website already exists in database
  $scope.check_url = function () {
    app_factory.check_url($scope.user.domain)
      .then(function (res) {
        if (res.data.result && res.data.website_does_exist) {
          $scope.site_exists = true;
        }
        else {
          $scope.site_exists = false;
        }
      });
  };

  // Change the user plan
  $scope.change_plan = function (name) {

    $scope.langs_count = $scope.user.translation_languages.length;

    for (var i in $scope.plans) {
      if ($scope.langs_count <= $scope.plans[i].langs_count && $scope.plans[i].name === name) {
        $scope.plan = $scope.plans[i];
        break;
      }
    }
  };

  // Show plan pop up
  $scope.show_plan_popup = function () {
    if ($scope.plan.name === 'Free') {
      $uibModal.open({
        templateUrl: 'plan.html', //  loads the template
        backdrop: true, //  setting backdrop allows us to close the modal window on clicking outside the modal window
        windowClass: 'plan_popup', //  windowClass - additional CSS class(es) to be added to a modal window template
        controller: 'plan_controller',
        scope: $scope
      }).result.then(function (data) {
        if (data) {
          $scope.plan = data;
          $scope.next_step(3);
        }
      });
    }
    else {
      $scope.next_step(3);
    }
  };

  // Select translation language to translate from source language
  $scope.select_language = function ($event, lang) {

    var isSelected = $event.currentTarget.classList.contains('selected');
    if (!isSelected) {

      // In case of old user don't let him select languages more than his current plan
      if ($scope.step === 7 && $scope.langs_count === $scope.plan.langs_count) {
        return;
      }
      else {
        $scope.user.translation_languages.push(lang);
      }
    }
    else {
      var index = $scope.user.translation_languages.indexOf(lang);
      $scope.user.translation_languages.splice(index, 1);
      $event.currentTarget.classList.remove('trashable');
    }

    $scope.langs_count = $scope.user.translation_languages.length;

    if ($scope.langs_count > $scope.plan.langs_count) {
      for (var i in $scope.plans) {
        if ($scope.langs_count <= $scope.plans[i].langs_count) {
          $scope.plan = $scope.plans[i];
          break;
        }

      }
    }

    $event.currentTarget.classList.toggle('selected');
  };

  // Unselect previously selected language for translation
  $scope.remove_language = function ($event) {

    var isSelected = $event.currentTarget.classList.contains('selected');

    if (isSelected) {
      $event.currentTarget.classList.add('trashable');
    }
  };

  // Create user account with translate.com with details provided in form
  $scope.save_user = function (is_free) {

    $scope.err_msg = '';
    $scope.close = false;

    $scope.password_insecure = false;
    $scope.password_nomatch = false;

    var invalid_passwords = ['password', 'abc123', '123456', '12345', '12345678', '123456789', 'abcd1234', '12345678', 'qwerty'];

    // Check password for validity
    if (invalid_passwords.indexOf($scope.user.password) > -1 || $scope.user.password.length < 5) {
      $scope.password_insecure = true;
      return;
    }
    else if ($scope.user.password !== $scope.user.cf_password) {
      $scope.password_nomatch = true;
      return;
    }

    var data = {
      first_name: $scope.user.first_name,
      last_name: $scope.user.last_name,
      email_address: $scope.user.email,
      password: $scope.user.password
    };

    // Create user account with translate.com
    app_factory.create_translate_user(data)
      .then(function (res) {
        if (res.data.result) {
          $scope.user.api_key = res.data.new_api_key;
          $scope.user.translate_user_id = res.data.user_id;

          if (is_free) {
            $scope.add_new_site();
          }
          else {
            $scope.step = 8;
          }
        }
        else if (res.data.error_code === 3001) {
          // Go to login page if user already has account with translate.com
          $scope.show_login(3);
        }
      });
  };

  $scope.save_credit_card = function () {
    $scope.close = false;
    var expire = $scope.payment.card_expire.split('/');
    $scope.payment.card_month = expire[0];
    $scope.payment.card_year = expire[1];
    $scope.payment.api_key = $scope.user.api_key;

    $scope.is_card_saving = true;
    app_factory.save_credit_card($scope.payment)
      .then(function (res) {
        if (res.data.result) {
          var data = {api_key: $scope.user.api_key, plan_id: $scope.plan.plan_id};
          app_factory.change_user_plan(data)
            .then(function (res) {
              $scope.add_new_site();
            });
        }
        else {
          $scope.err_msg = res.data.message;
          $scope.is_card_saving = false;
        }
      });
  };

  $scope.reset_user = function () {
    initialize();
    $scope.step = 2;
  };

  $scope.add_new_site = function () {

    var data = {
      user_id: $scope.user.translate_user_id,
      platform: 'drupal_7',
      website_url: $scope.user.domain,
      details: 'Drupal plugin is installed.'
    };
    // Call plugin install API
    app_factory.plugin_install(data);

    data = {
      api_key: $scope.user.api_key,
      hostname: $scope.user.domain,
      source_language: $scope.user.language,
      translation_languages: $scope.user.translation_languages
    };

    // Add new site for translation with current user account on translate.com
    app_factory.add_new_site(data)
      .then(function (res) {
        if (res.data.result) {
          save_embed_code(res.data.embed_code);
        }
        else {
          // Show error message
          $scope.err_msg = res.data.message;
        }
      });
  };

  var save_embed_code = function (embed_code) {
    // Save embed_code in localstorage so that we can check.
    // User is already registered with this site.
    localStorage.setItem('is_registered', 'yes');
    // Save embed code in drupal database.
    var url = window.location.protocol + '//' + window.location.host +
    '/admin/config/services/translate/embed?embed_code=' + embed_code;
    app_factory.save_embed_code(url);
    // Show the success screen.
    $scope.step = 5;
  };

  $scope.sign_out = function () {
    var url = window.location.protocol + '//' + window.location.host +
      '/admin/config/services/translate/signout';
    app_factory.sign_out(url)
      .then(function (res) {
        $uibModal.open({
          templateUrl: 'logout.html',
          backdrop: 'static',
          windowClass: 'modal',
          controller: 'logout_controller'
        }).result.then(function (data) {
          $scope.step = 1;
        });
      });
  };

  // Go to next step
  $scope.next_step = function (step) {
    $scope.direction = ($scope.step > step) ? 0 : 1;
    $timeout(function () {
      if (step === 2 && $scope.user.hasOwnProperty('api_key')) {
        step = 7;
      }
      $scope.step = step;
    }, 100);
  };

  $scope.show_login = function (form_type) {

    $scope.form_type = form_type;

    // Modal.html is present settings.html as ng-template
    $uibModal.open({
      templateUrl: 'modal.html', //  loads the template
      backdrop: true, //  setting backdrop allows us to close the modal window on clicking outside the modal window
      windowClass: 'modal', //  windowClass - additional CSS class(es) to be added to a modal window template
      controller: 'login_controller',
      scope: $scope
    }).result.then(function (data) {
      if (data) {
        $scope.user = data;

        for (var i in $scope.plans) {
          if ($scope.user.plan_name === $scope.plans[i].name) {
            $scope.plan = $scope.plans[i];
            break;
          }
        }

        if ($scope.site_exists) {
          save_embed_code($scope.user.embed_code);
        }
        else {
          $scope.langs_count = 0;
          $scope.user.translation_languages = [];
          $scope.step = 7;
        }
      }
    });
  };

}]);

app.controller('logout_controller', function ($scope, $uibModalInstance) {
  'use strict';
  $scope.cancel = function () {
    $uibModalInstance.close(false);
  };
});

app.controller('plan_controller', function ($scope, $uibModalInstance) {
  'use strict';
  $scope.plan = $scope.plans[1];
  $scope.close = function (plan) {
    $scope.plan = plan;
    $uibModalInstance.close($scope.plan);
  };
});

app.controller('login_controller', function ($scope, $uibModalInstance, $log, app_factory) {
  'use strict';
  $scope.is_loading = false;

  $scope.login_user = function () {

    // Type of error to show red or yellow
    $scope.err_msg = '';
    $scope.err_type = 'danger';
    $scope.close = false;
    $scope.invalid_plan = false;

    var data = {
      email_address: $scope.user.email,
      password: $scope.user.password
    };

    $scope.is_loading = true;
    // Login user account with translate.com
    app_factory.login_translate_user(data)
      .then(function (res) {
        if (res.data.result) {
          // After login get the api_key of user
          $scope.user.api_key = res.data.api_key;
          $scope.user.translate_user_id = res.data.user_details.user_id;
          $scope.user.first_name = res.data.user_details.first_name;
          $scope.user.last_name = res.data.user_details.last_name;

          // Get plan of the user
          app_factory.get_plan({api_key: $scope.user.api_key})
            .then(function (res) {
              if (res.data.result) {
                // Set plan name in user object
                var split = res.data.plan_name.replace('Legacy ', '').split(' ');
                var plan_name = split[0];
                if (plan_name === 'Free' || plan_name === 'Enterprise' || plan_name === 'Pro' || plan_name === 'Starter') {
                  $scope.user.plan_name = split[0];

                  //  Setting proper plan name
                  if ($scope.user.plan_name === 'Pro') {
                    $scope.user.plan_name = 'Professional';
                  }
                }
                else {
                  $scope.invalid_plan = true;
                }
              }

              if ($scope.invalid_plan) {
                $scope.is_loading = false;
                return;
              }

              // Check if site_exists then get code from Translate.com API
              if ($scope.site_exists) {
                $scope.is_loading = true;
                // Website is already submitted get code using API and use that code
                var data = {hostname: $scope.user.domain, api_key: $scope.user.api_key};
                app_factory.get_embed_code(data)
                  .then(function (res) {
                    if (!res.data.result) {
                      // Website doesn't belongs to current user show error
                      $scope.is_loading = false;
                      $scope.err_wrong_account = true;
                    }
                    else {
                      // Specified website is assosiated with current user
                      $scope.user.embed_code = res.data.embed_code;
                      $uibModalInstance.close($scope.user);
                    }
                  });
              }
              else {
                // Close the modal
                $uibModalInstance.close($scope.user);
              }
              $scope.is_loading = false;
            });
        }
        else {
          $scope.is_loading = false;
          // Show error message
          $scope.err_msg = res.data.message;
        }
      });
  };

  $scope.cancel = function () {
    $uibModalInstance.close(false);
  };
});
