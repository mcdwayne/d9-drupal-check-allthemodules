/**
 * @file
 * Provide interactions in the IdP configuration form.
 */

(($, Drupal) => {
  Drupal.samlSp = Drupal.samlSp || {};
  Drupal.samlSp.machineName = false;
  Drupal.samlSp.addCert = false;
  Drupal.samlSp.certs = {};

  Drupal.samlSp.idpMetadataParse = () => {
    const xml = $.parseXML(
      $('textarea[name="idp_metadata"]')
        .val()
        .trim()
    );
    Drupal.samlSp.idpMetadataXML = $(xml);
    const entityID = Drupal.samlSp.idpMetadataXML
      .find("EntityDescriptor, md\\:EntityDescriptor")
      .attr("entityID");

    if (typeof entityID === "string" && entityID !== "") {
      $("input#edit-idp-entity-id").val(entityID.trim());
      const parser = document.createElement("a");
      parser.href = entityID;
      $("input#edit-idp-label")
        .val(
          Drupal.samlSp.idpMetadataXML.find("OrganizationDisplayName").text()
            ? $(
                Drupal.samlSp.idpMetadataXML.find("OrganizationDisplayName")[0]
              ).text()
            : parser.hostname
        )
        .change();
      Drupal.samlSp.machineName = true;
    }

    $(
      Drupal.samlSp.idpMetadataXML.find(
        "SingleSignOnService, md\\:SingleSignOnService"
      )
    ).each(() => {
      if (
        $(this).attr("Binding") ===
        "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
      ) {
        $("input#edit-idp-login-url").val(
          $(this)
            .attr("Location")
            .trim()
        );
      }
    });

    $(
      Drupal.samlSp.idpMetadataXML.find(
        "SingleLogoutService, md\\:SingleSignOnService"
      )
    ).each(() => {
      if (
        $(this).attr("Binding") ===
        "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
      ) {
        $("input#edit-idp-logout-url").val(
          $(this)
            .attr("Location")
            .trim()
        );
      }
    });

    $(
      Drupal.samlSp.idpMetadataXML.find("X509Certificate, ds\\:X509Certificate")
    ).each(() => {
      // we put the certs in an object to ensure that none are duplicated
      // the certificate for signing needs to have whitespace trimmed and the new lines removed
      Drupal.samlSp.certs[$(this).text()] = $(this)
        .text()
        .trim();
    });
    $(Drupal.samlSp.idpMetadataXML.find("KeyDescriptor")).each(() => {
      // we put the certs in an object to ensure that none are duplicated
      // the certificate for signing needs to have whitespace trimmed and the new lines removed
      Drupal.samlSp.certs[$(this).text()] = $(this)
        .text()
        .trim();
    });
  };

  /**
   * Add one cert to the form, trigger the "Add one more" action
   */
  Drupal.samlSp.AddCert = () => {
    Drupal.samlSp.certs.forEach((i, cert) => {
      if (cert.hasOwnProperty) {
        $(
          $("textarea[data-drupal-selector*=edit-idp-x509-cert-]")[
            $("textarea[data-drupal-selector*=edit-idp-x509-cert-]").length - 1
          ]
        ).val(i.replace(/\s+/g, ""));
        delete Drupal.samlSp.certs[i];
        $(
          "fieldset[data-drupal-selector=edit-idp-x509-cert] input:submit[data-drupal-selector=edit-idp-x509-cert-actions-add-cert]"
        ).mousedown();
      }
    });
  };

  $(document).ajaxComplete((event, xhr, settings) => {
    // We need to ensure that the ajax submissions have completed before we
    // start a new one.
    if (
      settings.url.search("machine_name/transliterate") !== -1 ||
      settings.url.search("saml_sp/idp/add") !== -1
    ) {
      Drupal.samlSp.AddCert();
    }
  });

  /**
   * Watches the IdP metadata field to parse it into constituent fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Attaches keyup and mouseup responses to the IdP metadata field.
   */
  Drupal.behaviors.samlSpIdpForm = {
    attach: context => {
      $('textarea[name="idp_metadata"]:not(.idp-form-processed)', context)
        .addClass("idp-form-processed")
        .keyup(() => {
          Drupal.samlSp.idpMetadataParse();
        })
        .mouseup(() => {
          Drupal.samlSp.idpMetadataParse();
        });
    }
  };
})(jQuery, Drupal);
