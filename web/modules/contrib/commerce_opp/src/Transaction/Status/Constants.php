<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Defines constant for the possible transaction status names.
 */
class Constants {

  /**
   * Type used for result codes for successfully processed transactions.
   */
  const TYPE_SUCCESS = 'success.success';

  /**
   * Type used for successfully processed transactions needing manual review.
   */
  const TYPE_SUCCESS_NEEDING_REVIEW = 'success.review';

  /**
   * Type used for result codes for pending transactions.
   */
  const TYPE_PENDING = 'success.pending';

  /**
   * Type used for rejections due to 3Dsecure and Intercard risk checks.
   */
  const TYPE_REJECTED_3DSECURE_INTERCARD = 'rejected.3dsecure_intercard';

  /**
   * Type used for rejections by the external bank or similar payment system.
   */
  const TYPE_REJECTED_EXTERNAL = 'rejected.external';

  /**
   * Type used for result codes for rejections due to communication errors.
   */
  const TYPE_REJECTED_COMMUNICATION_ERROR = 'rejected.communication_error';

  /**
   * Type used for result codes for rejections due to system errors.
   */
  const TYPE_REJECTED_SYSTEM_ERROR = 'rejected.system_error';

  /**
   * Type used for result codes for rejections due to error in async workflow.
   */
  const TYPE_REJECTED_ASYNC_ERROR = 'rejected.async_error';

  /**
   * Type used for rejections due to checks by external risk systems.
   */
  const TYPE_REJECTED_RISK_EXTERNAL = 'rejected.risk.external';

  /**
   * Type used for result codes for rejections due to address validation.
   */
  const TYPE_REJECTED_RISK_ADDRESS = 'rejected.risk.address';

  /**
   * Type used for result codes for rejections due to 3Dsecure.
   */
  const TYPE_REJECTED_RISK_3DSECURE = 'rejected.risk.3dsecure';

  /**
   * Type used for result codes for rejections due to blacklist validation.
   */
  const TYPE_REJECTED_RISK_BLACKLIST = 'rejected.risk.blacklist';

  /**
   * Type used for result codes for rejections due to risk validation.
   */
  const TYPE_REJECTED_RISK_VALIDATION = 'rejected.risk.validation';

  /**
   * Type used for result codes for rejections due to configuration validation.
   */
  const TYPE_REJECTED_VALIDATION_CONFIGURATION = 'rejected.validation.configuration';

  /**
   * Type used for result codes for rejections due to registration validation.
   */
  const TYPE_REJECTED_VALIDATION_REGISTRATION = 'rejected.validation.registration';

  /**
   * Type used for result codes for rejections due to job validation.
   */
  const TYPE_REJECTED_VALIDATION_JOB = 'rejected.validation.job';

  /**
   * Type used for result codes for rejections due to reference validation.
   */
  const TYPE_REJECTED_VALIDATION_REFERENCE = 'rejected.validation.reference';

  /**
   * Type used for result codes for rejections due to format validation.
   */
  const TYPE_REJECTED_VALIDATION_FORMAT = 'rejected.validation.format';

  /**
   * Type used for result codes for rejections due to address validation.
   */
  const TYPE_REJECTED_VALIDATION_ADDRESS = 'rejected.validation.address';

  /**
   * Type used for result codes for rejections due to contact validation.
   */
  const TYPE_REJECTED_VALIDATION_CONTACT = 'rejected.validation.contact';

  /**
   * Type used for result codes for rejections due to account validation.
   */
  const TYPE_REJECTED_VALIDATION_ACCOUNT = 'rejected.validation.account';

  /**
   * Type used for result codes for rejections due to amount validation.
   */
  const TYPE_REJECTED_VALIDATION_AMOUNT = 'rejected.validation.amount';

  /**
   * Type used for result codes for rejections due to risk management.
   */
  const TYPE_REJECTED_VALIDATION_RISK = 'rejected.validation.risk';

  /**
   * Type used for chargeback related result codes.
   */
  const TYPE_CHARGEBACK = 'chargeback';

}
