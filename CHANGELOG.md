# Release Notes - heidelpay Payment Gateway for Magento 2

## Versioning

This project does not follow a versioning standard. Versions are crafted after the dates; for example, the version 17.7.25 was released on July, 25th in 2017
## xx.xx.xx
### Added
- Hash validation to push requests.
- Compatibility with Magento 2.3

### Fixed
- Use the correct store config now for orders made in sub stores.

### Changed
- Redirect controller: redirect to cart if oder could not be loaded correctly.

## 19.7.29

### Added
- Santander hire purchase payment method.
- Check whether customer is already 18 if payment method requires it.

### Fixed
- A problem where a transaction was processed a second time via push when using "sofort". That caused the paid amount to be displayed
incorrectly.
- A problem which led to a missing error message for the customer.
- An issue with basket calculation when order has a discount.

### Changed 
- Refactored paymentMethods. Simplify configuration and reduce duplicate code in payment methods.
- Compatibility with magento 2.2.9: Change address handling in checkout.

## 19.5.8
### Fixed
- An issue that prevented an invoice to be (partly) refunded twice.
- An issue where different currencies can cause that order is created in base currency.

### Changed
- Improved response handling if Post data is empty: Customer gets redirected to cart with an error message. Wording of
log entry was changed for a better understanding.
- Removed static version from composer command in installation manual.
- improved status handling to ensure compatibility with magento 2.2.8 

## 19.1.30

### Fixed
- Fixed a bug which resulted in an problem when sending the new order mail which resulted in the customer being redirected to an empty cart instead of the success page.
- iDEAL: Add missing checkbox for terms of conditions.
- iDEAL: Set correct default channel for test system.

### Added
- Support information to readme.

## 18.10.1

### Added
- payment method iDeal

## 18.9.20

### Fixed
- An issue which resulted in an error on checkout with secured payment methods with magento security patch SUPEE-10888 (Magento Version 2.2.6).
- An issue concerning the versions of the dependencies.

## 18.8.9

### Fixed
- An issue which resulted in the order mail being sent twice.

## 18.6.11

### Fixed
- An issue which resulted in basked requests always being performed in sandbox mode.

## 18.6.7

### Fixed
- A bug which resulted in a problem in developer mode forcing the shop-owner to perform di:compile on install.

## 18.4.23

### Fixed
- A bug which resulted in an error in some circumstances when a customer was paying via invoice secured payment method.

## 18.4.12

### Added
- BasketApi support in general and activated it for InvoiceSecuredPaymentMethod.
- Basket helper class to provide for general BasketApi support.
- Trait to be able to dump getter results from a class to an array.
- BasketApi integration tests.
- Refactored payment method classes with regards to the payment code. 

### Changed
- Refactored configuration reading (heidelpay main and payment specific config) in order to inject it where needed.
- Replaced obsolete PhpApi with PhpPaymentApi.

### Fixed
- Set Order to the correct state when receiving a receipt via push.

## 18.3.1

### Changed
- Tlds from de to com.
- Fixed links to point to the correct magento version.

## 18.2.28

### Changed
- Renamed 'Heidelberger Payment GmbH' to 'heidelpay GmbH' due to re-branding.

## 18.1.24

### Fixed
- Bug which resulted in a REC (push) not being referenceable to the corresponding order if the payment has been received >30 days after order placement.

### Changed
- Replaced php-customer-message with php-message-code-mapper.

## 17.10.12

### Fixed
- Potential bug that could occur when the shipping address step during checkout is skipped/inactive and/or visited as guest

## 17.10.11

### Added
- Magento 2.2 Support

### Fixed
- Changed jQuery selector for checkout agreements in certain Magento versions

## 17.8.17

### Changed
- Renamed "SOFORT Überweisung" to "Sofort." due to re-branding
- Package requirements for Magento 2.2 support

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
