<?php

namespace Drupal\commerce_iats\Rest;

/**
 * Interface GatewayInterface.
 */
interface GatewayInterface {

  /**
   * Performs a POST request to the API.
   *
   * @param string $function
   *   The API function to call.
   * @param array $data
   *   Request data, optional.
   *
   * @return array
   *   Response from the request.
   */
  public function post($function, array $data = []);

  /**
   * Gets available ACH categories.
   *
   * @return array
   *   The ACH categories.
   */
  public function achGetCategories();

  /**
   * Credits a previously-settled credit card transaction.
   *
   * @param array $data
   *   Transaction data.
   *
   * @return \stdClass
   *   Credit response data.
   */
  public function creditCardCredit(array $data);

  /**
   * Settles a previously-authorized credit card transaction.
   *
   * @param array $data
   *   Transaction data.
   *
   * @return \stdClass
   *   Settlement response data.
   */
  public function creditCardSettle(array $data);

  /**
   * Voids a previously-authorized credit card transaction.
   *
   * @param array $data
   *   Transaction data.
   *
   * @return \stdClass
   *   Settlement response data.
   */
  public function creditCardVoid(array $data);

  /**
   * Perform credit with 1stPayVault ACH.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   ACH ID.
   * @param array $data
   *   ACH data.
   *
   * @return \stdClass
   *   Response from the 1stPayVault ACH credit.
   */
  public function firstPayAchCredit($vaultKey, $id, array $data);

  /**
   * Perform debit with 1stPayVault ACH.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   ACH ID.
   * @param array $data
   *   ACH data.
   *
   * @return \stdClass
   *   Response from the 1stPayVault ACH debit.
   */
  public function firstPayAchDebit($vaultKey, $id, array $data);

  /**
   * Perform authorization with 1stPayVault credit card.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   Credit card ID.
   * @param array $data
   *   Credit card data.
   *
   * @return \stdClass
   *   Response from the 1stPayVault credit card authorization.
   */
  public function firstPayCcAuth($vaultKey, $id, array $data);

  /**
   * Perform sale with 1stPayVault credit card.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   Credit card ID.
   * @param array $data
   *   Credit card data.
   *
   * @return \stdClass
   *   Response from the 1stPayVault credit card sale.
   */
  public function firstPayCcSale($vaultKey, $id, array $data);

  /**
   * Add an ACH account to a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param array $data
   *   ACH account data.
   *
   * @return \stdClass
   *   Response from the vault add ACH account request.
   */
  public function vaultAchCreate($vaultKey, array $data);

  /**
   * Delete a bank account from a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   Bank account ID.
   */
  public function vaultAchDelete($vaultKey, $id);

  /**
   * Load bank account data from a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   Bank account ID.
   *
   * @return \stdClass|null
   *   Bank account data, or NULL if not found.
   */
  public function vaultAchLoad($vaultKey, $id);

  /**
   * Query bank account data from a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param array $data
   *   Bank account query data, optional.
   *
   * @return \stdClass[]
   *   Array of bank account data.
   */
  public function vaultAchQuery($vaultKey, array $data = []);

  /**
   * Add a credit card to a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param array $data
   *   Credit card data.
   *
   * @return \stdClass
   *   Response from the vault add credit card request.
   */
  public function vaultCcCreate($vaultKey, array $data);

  /**
   * Delete a credit card from a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   Credit card ID.
   */
  public function vaultCcDelete($vaultKey, $id);

  /**
   * Load credit card data from a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param string $id
   *   Credit card ID.
   *
   * @return \stdClass|null
   *   Credit card data, or NULL if not found.
   */
  public function vaultCcLoad($vaultKey, $id);

  /**
   * Query credit card data from a vault.
   *
   * @param string $vaultKey
   *   The vault ID.
   * @param array $data
   *   Credit card query data, optional.
   *
   * @return \stdClass[]
   *   Array of credit card data.
   */
  public function vaultCcQuery($vaultKey, array $data = []);

  /**
   * Query vaults.
   *
   * @param array $data
   *   Search parameters for filtering the query.
   *
   * @return array
   *   Vaults found matching the search parameters, keyed by vault ID.
   */
  public function vaultQuery(array $data = []);

}
