(function($) {
  'use strict';

  var urls = {
    csrfToken: '/session/token',
    certificateSigningRequest: '/certificate/signing-request'
  };

  var selectors = {
    form: 'form.pki-ra-generate-certificate',
    keyType: '#edit-key-type-and-size',
    password: '.pki-ra-generate-certificate .js-password-field',
    passwordConfirmation: '.pki-ra-generate-certificate .js-password-confirm',
    registrationId: '.pki-ra-generate-certificate input[name="registration_id"]'
  };

  var errors = {
    passwordEmpty: 'Please enter a password',
    passwordMismatch: 'The password and its confirmation do not match'
  };

  $(function() {
    $(selectors.form).submit(enroll);
  });

  function enroll(evt) {
    evt.preventDefault();
    evt.stopPropagation();

    var password = validatePassword();
    if (!password) {
      return; //error occurred
    }

    var options = {
      type: 'ECC',// TODO $(selectors.keyType).val().toUpperCase()
      subject: '/C=US/CN=Drupal Module ' + randomInt(), // TODO
      password: password
    };

    enrollment.csr.create({
      type: options.type,
      subject: options.subject
    }).then(function(result) {
      return sendCsr(result.csr)
      .then(function(certificateResponse) {
        var certificates = [certificateResponse.certificate];
        certificates.push(certificateResponse.ca_certificates);
        return {
          certificates: certificates,
          key: result.key
        };
      });
    }).then(function(result) {
      return enrollment.pkcs12.create({
        password: options.password,
        certificateChain: result.certificates,
        key: result.key
      });
    }).then(function(encodedP12) {
      var link = generateLink(encodedP12);
      // TODO Put this in a nicely styled result box
      document.body.appendChild(link);
    });
  }

  function displayError(error) {
    alert(error);
  }

  function validatePassword() {
    var password = $(selectors.password).val(),
        confirmation = $(selectors.passwordConfirmation).val();

    if (!password || password.length === 0) {
      return displayError(errors.passwordEmpty);
    }

    if (password !== confirmation) {
      return displayError(errors.passwordMismatch);
    }

    return password;
  }

  function sendCsr(csr) {
    return $.get(urls.csrfToken)
    .then(function(csrfToken) {
      return $.ajax({
        url: urls.certificateSigningRequest,
        method: 'POST',
        headers: {
          'X-CSRF-Token': csrfToken
        },
        contentType: 'application/hal+json',
        dataType: 'json',
        data: JSON.stringify({
          csr: csr,
          profile: 'foundational', // TODO backend should parse the JSON and add profile on its own
          registrationId: getRegistrationId()
        })
      });
    });
  }

  function generateLink(data) {
    var link = document.createElement('a');
    link.download = 'keystore.p12';
    link.setAttribute(
      'href',
      'data:application/x-pkcs12;base64,' + data
    );
    link.appendChild(document.createTextNode('Save PKCS#12 file'));
    return link;
  }

  function getRegistrationId() {
    return parseInt(
      $(selectors.registrationId).val(),
      10
    );
  }

  function randomInt() {
    return Math.floor(Math.random() * 10000);
  }

})(jQuery);

