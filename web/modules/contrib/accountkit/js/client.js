console.log("Account Kit client loaded");
// initialize Account Kit with CSRF protection
AccountKit_OnInteractive = function () {
  AccountKit.init(
    {
      appId: drupalSettings.accountkit.client.app_id,
      state: "{{'account-kit'}}",
      version: drupalSettings.accountkit.client.api_version,
      fbAppEventsEnabled: true,
      debug: true
    }
  );
};

if (document.getElementById("sms-login-submit")) {
  document.getElementById("sms-login-submit").addEventListener("click", function (e) {
    e.preventDefault()
    smsLogin();
    return false;
  });
}

if (document.getElementById("email-login-submit")) {
  document.getElementById("email-login-submit").addEventListener("click", function (e) {
    e.preventDefault()
    emailLogin();
    return false;
  });
}

// login callback
function loginCallback(response) {
  console.log("loginCallback");
  if (response.status === "PARTIALLY_AUTHENTICATED") {
    var code = response.code;
    var csrf = response.state;
    console.log(code);
    console.log(csrf);

    document.getElementById("code").value = code;
    document.getElementById("csrf").value = csrf;

    if (document.getElementById("sms-login-form")) {
      document.getElementById("sms-login-form").submit();
    }

    if (document.getElementById("email-login-form")) {
      document.getElementById("email-login-form").submit();
    }

    // Send code to server to exchange for access token
  }
  else if (response.status === "NOT_AUTHENTICATED") {
    // handle authentication failure
  }
  else if (response.status === "BAD_PARAMS") {
    // handle bad parameters
  }
}

// phone form submission handler
function smsLogin() {
  console.log("smsLogin");

  var countryCode = document.getElementById("edit-country-code").value;
  var phoneNumber = document.getElementById("edit-phone-number").value;
  AccountKit.login(
    'PHONE',
    {countryCode: countryCode, phoneNumber: phoneNumber}, // will use default values if not specified
    loginCallback
  );
}


// email form submission handler
function emailLogin() {
  console.log("emailLogin");

  var emailAddress = document.getElementById("edit-email").value;
  AccountKit.login(
    'EMAIL',
    {emailAddress: emailAddress},
    loginCallback
  );
}



