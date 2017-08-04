# Release Notes - heidelpay Payment Gateway for Magento 2

## Versioning

This project does not follow a versioning standard. Versions are crafted after the dates; for example, the version 17.7.25 was released on July, 25th in 2017

## 17.8.3

### Fixed
- A bug which thrown an exception when a language different from de_DE and en_US was used in Magento
- Removed canSendNewInvoiceEmail checks when just creating Invoices

## 17.7.25

### Added
- Prepayment Invoice details in backend, e-mail and pdf invoices
- Customer's ip address will now be added to the payment api request

### Fixed
- Fixed a missing redirect back to cart on nok transaction (error) responses
- Re-added the name input field in Secured Invoice
- Pre-filled 'undefined undefined undefined' in secured payment methods
- Removed readonly attribute in full name input (Secured Invoice)

### Changed
- Changed 'Security Sender' to 'Sender-ID' in heidelpay backend configuration
- Reversed the order of the year selection in the secured payment method input forms

## 17.7.14

### Fixed
- Update php-api because of response issue in case of prepayment and invoice
- remove name input field on secured invoice

## v17.6.16

### Added
- Invoice (non-secure) payment method
- Online Capture and Refund functionality for all payment methods

### Fixed
- Wrong parent transaction id was set for Receipts in Push notifications
- Added an additional validator for sending invoices (PR #24)

## v17.5.9

### Fixed
- Bugfix for isAvailable() method on both B2C payment methods where an exception was thrown (tested on 2.1.1) when loading the checkout and the quote was null for some reason

## v17.5.3

### Changed/Fixed
- Removed the usage of the unsed Class 'ShipmentValidatorInterface', which wasn't present in <=2.0.8 and <=2.1.1

## v17.4.16

### Added
- Secured Invoice (B2C) Payment Method (DE, AT, CH)
- Sending Finalize notifications to heidelpay when Shipment is created (Secured Invoice only)
- Added Push functionality to e.g. receive REC requests for Prepayment and Invoice payment methods

### Changed
- Invoices now contain information about payments (e.g. where to send the amount, in case of Prepayment, Invoice)
- When errors after sending the request or during the redirect are occuring, the customer will be redirected to the checkout/cart instead of seeing a blank page

## v17.3.28

### Added
- Direct Debit Secured (B2C) Payment method (DE, AT, CH)
- Enabled Min/Max Total Order sum configurations for every available payment method

### Changed
- Input fields for heidelpay payment methods will now be validated before placing the order.
- B2C payment methods cannot be used when a company is given.
- Replaced the in-module result code mapping with the [heidelpay php-customer-messages package](https://github.com/heidelpay/php-customer-messages)

### Fixed
- Fixed issue #11: heidelpay Payment Gateway now uses the currency from the customer quote instead of the system default.

## v17.3.13

### Added
- Direct Debit Payment method
- giropay Payment method
- Recognition option for Direct Debit payment

### Changed
- updated heidelpay php-api version to 17.3.2
- heidelpay payment methods cannot be selected anymore when creating orders in the Admin Backend
- Exact Magento Shop and heidelpay Module versions are now being sent to the heidelpay payment instead of hardcoded versions 

### Fixed
- The minicart (on the top right) will now be cleared after the successful checkout

### Removed
- Unused Customer Model and it's table definition
