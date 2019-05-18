<?php

namespace Drupal\janrain_connect\Constants;

/**
 * Janrain Connect WebServiceConstants.
 */
class JanrainConnectWebServiceConstants {

  /**
   * Name of the 'error_description'.
   */
  const JANRAIN_CONNECT_ERROR_DESCRIPTION = 'error_description';

  /**
   * The 'invalid_fields' key.
   */
  const JANRAIN_CONNECT_INVALID_FIELDS = 'invalid_fields';

  /**
   * The status key on janrain.
   */
  const JANRAIN_CONNECT_STATUS = 'stat';

  /**
   * The status 'success' on janrain.
   */
  const JANRAIN_CONNECT_STATUS_SUCCESS = 'ok';

  /**
   * The status 'error' on janrain.
   */
  const JANRAIN_CONNECT_STATUS_ERROR = 'error';

  /**
   * Name of the field 'birthdate.day'.
   */
  const JANRAIN_CONNECT_FIELD_DATESELECT_DAY = 'dateselect_day';

  /**
   * Name of the field 'birthdate.month'.
   */
  const JANRAIN_CONNECT_FIELD_DATESELECT_MONTH = 'dateselect_month';

  /**
   * Name of the field 'birthdate.year'.
   */
  const JANRAIN_CONNECT_FIELD_DATESELECT_YEAR = 'dateselect_year';

  /**
   * Name of the registration form.
   */
  const JANRAIN_CONNECT_FORM_REGISTRATION = 'registrationForm';

  /**
   * Name of the forgot password form.
   */
  const JANRAIN_CONNECT_FORM_FORGOT_PASSWORD = 'forgotPasswordForm';

  /**
   * Name of the change password form.
   */
  const JANRAIN_CONNECT_FORM_CHANGE_PASSWORD = 'changePasswordForm';

  /**
   * Name of the change password form, via Forgot Password.
   */
  const JANRAIN_CONNECT_FORM_CHANGE_PASSWORD_FORGOTTEN = 'changePasswordFormNoAuth';

  /**
   * Name of the resend verification email.
   */
  const JANRAIN_CONNECT_FORM_RESEND_VERIFICATION_EMAIL = 'resendVerificationForm';

  /**
   * Name of the sign in form.
   */
  const JANRAIN_CONNECT_FORM_SIGNIN = 'signInForm';

  /**
   * Name of the edit profile form.
   */
  const JANRAIN_CONNECT_FORM_EDIT_PROFILE = 'editProfileForm';

  /**
   * Name of the change password form no auth.
   */
  const JANRAIN_CONNECT_FORM_CHANGE_PASSWORD_NO_AUTH = 'changePasswordFormNoAuth';

  /**
   * Name of the key 'validation'.
   */
  const JANRAIN_CONNECT_VALIDATION = 'validation';

  /**
   * Name of the key 'field_data'.
   */
  const JANRAIN_CONNECT_FIELD_DATA = 'field_data';

  /**
   * Name of the key 'form_id'.
   */
  const JANRAIN_CONNECT_FORM_ID = 'form_id';

  /**
   * Name of the key 'next'.
   */
  const JANRAIN_CONNECT_NEXT = 'next';

  /**
   * Name of the key 'action'.
   */
  const JANRAIN_CONNECT_ACTION = 'action';

  /**
   * Name of the key 'fields'.
   */
  const JANRAIN_CONNECT_FIELDS = 'fields';

  /**
   * Name of the key 'authFields'.
   */
  const JANRAIN_CONNECT_AUTH_FIELDS = 'authFields';

  /**
   * Name of the token form.
   */
  const JANRAIN_CONNECT_TOKEN = 'authorization_code';

  /**
   * Name of the refresh token form.
   */
  const JANRAIN_CONNECT_REFRESH_TOKEN = 'refresh_token';

  /**
   * Flow.js URL.
   */
  const JANRAIN_CONNECT_FLOW_JS = 'https://ssl-static.janraincapture.com/widget_data/flow.js';

  /**
   * Configuration Server URL.
   */
  const JANRAIN_CONNECT_CONFIG_SERVER = 'https://v1.api.us.janrain.com';

  /**
   * Name of the social sign in form.
   */
  const JANRAIN_CONNECT_SOCIAL_FORM_SIGNIN = 'socialRegistrationForm';

  /**
   * Id of traditional as provider.
   */
  const JANRAIN_CONNECT_SOCIAL_CAPTURE = 'capture';

  /**
   * Has errors.
   */
  const JANRAIN_CONNECT_HAS_ERRORS = 'has_errors';

  /**
   * Results.
   */
  const JANRAIN_CONNECT_RESULTS = 'results';

}
