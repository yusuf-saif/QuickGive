=== QuickGive ===
Contributors: saif2002
Tags: donations, paystack, fundraising, payment, charity
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

QuickGive lets WordPress sites collect secure one-time donations through Paystack.

== Description ==

QuickGive is a simple WordPress donation plugin for accepting donations through Paystack. It provides an easy donation form, configurable donation amounts, donor information collection, and server-side payment verification.

This first release supports Paystack only.

**External service disclosure:**
QuickGive connects to Paystack to process and verify payments. When a donor submits a donation, payment information is sent to Paystack for transaction processing. Site administrators must use their own Paystack public and secret keys.

Paystack Terms of Service:
https://paystack.com/terms

Paystack Privacy Policy:
https://paystack.com/privacy

== Installation ==

1. Upload the `quickgive` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress Plugins screen.
3. Go to the QuickGive settings page.
4. Enter your Paystack public key and secret key.
5. Configure your donation amounts and currency.
6. Add the donation shortcode to any page or post.

== Frequently Asked Questions ==

= Does QuickGive support gateways other than Paystack? =

No. Version 1.0 supports Paystack only.

= Do I need a Paystack account? =

Yes. You need a Paystack account and API keys to receive payments.

= Are Paystack keys included in the plugin? =

No. Site administrators must add their own Paystack keys in the plugin settings.

= What shortcode do I use? =

Add `[quickgive_donation_popup]` to any page or post to display the donation button.

= Is the payment verification done server-side? =

Yes. After a donor completes payment in the Paystack popup, the plugin verifies the transaction server-side using your secret key before recording it as successful. The secret key is never sent to the browser.

== Screenshots ==

1. The donation button and popup modal displayed on the front end.
2. The QuickGive settings page in the WordPress admin.
3. The donation log showing all transactions.

== Changelog ==

= 1.0 =
* Initial release.
* Added Paystack donation payment support.
* Added donation form shortcode [quickgive_donation_popup].
* Added admin settings for Paystack keys and donation configuration.
* Added server-side transaction verification.
* Added donation log in the WordPress admin.
* Added configurable preset donation amounts and optional custom amount entry.
* Added optional donor thank-you email after verified payment.

== Upgrade Notice ==

= 1.0 =
Initial release.
