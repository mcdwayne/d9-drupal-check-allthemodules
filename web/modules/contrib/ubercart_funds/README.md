# Ubercart funds

Ubercart Funds implements a Site Funds Management System using Ubercart.
It allows users to Deposit Funds in their account, Withdraw Funds,
Transfer Funds to other users and make Escrow Payment to other users.

## Ubercart Funds provides the site users the following functionalities:

- Users can Deposit Funds to their account,
  using any payment method available on the Site.
- Users can Transfer Funds to other user accounts using their username.
  If you give users the permission to see user profiles,
  you'll then get an autocomplete field.
- Users can create Escrow Payments and release it later in due time.
- Users can setup Withdrawal methods : Paypal, Skrill, Check and Bank Accoun.
- Users can submit Withdrawal requests,
  to be processed later by the site admins in due time.
- Users can View all Transactions, Withdrawal Requests,
  and Manage Outgoing and Incoming Escrow payments.
- The module provides a User Balance block which can be placed so,
  users know what their current balance is.
- The module provides an Operations block which has all the action links
  the user needs to Manage his account.

## Ubercart Funds provides the Site Administrators the following functionalities:
- Configure, Enable or Disable Withdrawal Methods.
- Manage Fees for all Operations Including:
  - Deposits for each enabled Payment Method
  (Both Percentage and Fixed Fees).
  - Withdrawals for each enabled Withdrawal Method
  (Both Percentage and Fixed Fees).
- Manage Withdrawal Requests and Approve or Decline each request.
- View and Search all Site Transactions.
- Site balance to follow earning from commissions.
- User balances on user profile pages to know the balance of users.

## Currently in progress :
- Ubercart Funds will provide a submodule
  named Ubercart Funds Payment, that implements
  an Account Funds payment method where users can pay for On-Site Products
  and Services using their Account Funds Balance.

## Known issues:
 - If you manually edit an deposit order status which were in "pending"
   state to another status and then revert it back to a pending status,
   you will credit the user again of the deposit amount. This issue is due
   to the lack of events available to rules (checkout is complete).

## Todo :
 - Integration with rules when Typed data will be implemented.
 - Tokens integration when rules will be available.

This module is developed and maintained by Orao-web.
