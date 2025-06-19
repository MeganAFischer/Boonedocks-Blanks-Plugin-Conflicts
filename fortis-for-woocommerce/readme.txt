=== Fortis  for WooCommerce ===
Contributors: fortispay
Tags: e-commerce, woocommerce, payment, fortis, credit card
Requires at least: 6.0
Tested up to: 6.8.0
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This is the official Fortis extension to receive payments for WooCommerce.

== Description ==

The Fortis plugin for WooCommerce lets you accept online payments including credit cards, debit cards and ACH.
Features:
1. Embedded payments iframe on the checkout page
2. ACH and CC payment methods supported
3. Level 2 and Level 3 processing supported for interchange optimization
    3.1 To make use of this function, you will need to add the following product attributes to each product:
        commodity_code e.g. cc123456
        unit_code      e.g. gll
4. Webhooks for ACH status update on Settlement
5. Logged-in customers can store CC and ACH details to use for future payments
6. Refund and Void from the Order screen
7. Sandbox mode for testing
8. Customizable payments iframe appearance with two different view options
9. Compatible with WooCommerce Blocks

== About Fortis ==

Fortis delivers comprehensive payment solutions and commerce enablement to software partners and developers, processing billions of dollars annually. The company’s mission is to forge a holistic commerce experience, guiding businesses to reach uncharted growth and scale.
Fortis for WooCommerce enables you to offer a seamless and secure payment experience for customers using your e-commerce store. Guest customers make payments via a secure and customizable payments iframe hosted by Fortis. Customers that create accounts have the option to store multiple payment cards to use for future payments that they can select on checkout for a seamless payment experience.

== FAQ ==

= Does this require a Fortis merchant account? =

Yes! To use this plugin you will need an account with Fortis. For more information on application and pricing please [contact us](https://go.fortispay.com/woocommerce)

= How do I test the plugin? =

We recommend testing the plugin in Sandbox mode before you release it on your live e-commerce website. To sign up for a test account please sign up on our [Developer Portal](https://developer.fortis.tech/users/register).

= Does this require an SSL certificate? =

Yes! An SSL certificate must be installed on your site to use Fortis.

= Where can I find documentation? =

For help setting up and configuring the Fortis plugin please refer to our [installation guide](https://docs.fortispay.com/merchants/woocommerce-new/plugin-installation) and [setup guide](https://docs.fortispay.com/merchants/woocommerce-new/plugin-setup).

= Where can I get support? =

If you have any questions please send an email to devsupport@fortispay.com.

== Screenshots ==

1. WooCommerce Checkout Page with Credit Card
2. WooCommerce Checkout Page with Credit Card and ACH
3. WooCommerce Admin Fortis Configuration Page
4. WooCommerce Admin Fortis Appearance Configuration Page

== Third Party services ==

https://api.sandbox.fortis.tech
https://api.fortis.tech
https://js.sandbox.fortis.tech/commercejs-v1.0.0.min.js

== Service Terms ==

https://fortispay.com/fortisplatform-serviceagreement/

== Privacy Policy ==

https://fortispay.com/privacy-policy-2023/

== Changelog ==

= 1.1.2 - 2025-04-22 =
* Addressed bug fixes and security improvements.

= 1.1.1 - 2025-01-30 =
* Resolved compatibility issue with WooCommerce Blocks on sites hosted in subdirectories, ensuring proper AJAX request handling.
* Updated the order status flow for pending ACH payments, changing it from “Pending Payment” to “On Hold”.

= 1.1.0 - 2024-11-22 =
* Support for capturing authorization-only transactions.
* Resolved an issue where the payment total due did not include the shipping amount on the classic checkout page.
* Verified compatibility with WordPress 6.7 and WooCommerce 9.4.1.

= 1.0.8 - 2024-09-22 =
* Tested on WordPress 6.6.2 and WooCommerce 9.3.2
* Corrected plugin slug on text domains

= 1.0.7 - 2024-07-15 =
* Tested on WordPress 6.5.5 and WooCommerce 9.1.1
* Apple Pay and Google Pay support
* Code quality improvements

= 1.0.6 - 2024-05-21 =
* Tested on WordPress 6.5.3 and WooCommerce 8.8.3.
* Code quality improvements.

= 1.0.5 - 2024-02-21 =
* Improve compatibility with WooCommerce Blocks.
* Code quality improvements.
* Tested on WordPress 6.4.2 and WooCommerce 8.5.1.
* Fix refund compatibility when HPOS is enabled.
* ACH reliability improvements.
* ACH refunds feature added.
* Add option to enable/disable the agreement checkbox.

= 1.0.4 - 2023-10-05 =
* Fix issue adding Tokenized card using 'Add Payment Method' from 'My Account'.
* Test on WooCommerce 8.1.1.

= 1.0.3 - 2023-09-15 =
* Load elements iFrame on checkout after billing and shipping address is entered.
* Add support for High Performance order storage.
* Test on WooCommerce 8.1.0 and WordPress 6.3.1.

= 1.0.2 - 2023-07-31 =
* Improve billing and shipping detail compatibility.

= 1.0.1 - 2023-07-26 =
* Bug fixes and improvements.

= 1.0.0 - 2022-10-06 =
* Initial release.
