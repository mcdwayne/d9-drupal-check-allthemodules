<?php
namespace Drupal\miniorange_saml;

class mo_options_enum_service_provider extends BasicEnum
{
	
    const Identity_name ='miniorange_saml_idp_name';
    const Login_URL = 'miniorange_saml_idp_login_url';
    const Issuer = 'miniorange_saml_idp_issuer';
    const Name_ID_format ='miniorange_saml_nameid_format';
    const X509_certificate = 'miniorange_saml_idp_x509_certificate';
    const Enable_login_with_SAML = 'miniorange_saml_enable_login';
}




