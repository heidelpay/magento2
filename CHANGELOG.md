# Release Notes - heidelpay Payment Gateway for Magento 2

## v17.3.1x

### Added
- Direct Debit Secured (B2C) Payment method (DE, AT, CH)

### Changed
- Input fields for heidelpay payment methods will now be validated before placing the order.
- B2C payment methods cannot be used when a company is given.

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
