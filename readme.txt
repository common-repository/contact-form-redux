=== Contact Form Redux ===
Contributors: linux4me2
Tags: contact, form, contact form, feedback, email, ajax, google recaptcha, akismet
Requires at least: 5.0
Tested up to: 6.4.1
Requires PHP: 7.4
Stable tag: 1.3.7
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html

A simple but flexible contact form that (hopefully) won't get in your way.

== Description ==

Contact Form Redux can manage multiple contact forms. The forms and mail contents can be flexibly customized with simple markup. 

= Features =

* AJAX-powered form submission
* JavaScript and CSS loaded only on specific pages
* load minified JavaScript and CSS
* set default form values with POST and GET variables
* compatible with PHP through v. 8.1
* quiz verification
* Akismet spam-filtering
* Google v2 reCAPTCHA
* and much more

= Documentation and Support =

You can find detailed setup instructions, advanced techniques, and troubleshooting information in the [documentation](https://cfr.backwoodsbytes.com/). If you are unable to find the answer to your question, check the [support forum](https://wordpress.org/support/plugin/contact-form-redux/) on WordPress.org.

= Privacy Notice =

With the default configuration this plugin does not:

* track users by stealth
* write personal user data to the database
* send data to external servers
* use cookies

If you activate some features of this plugin, the contact form submitter's personal data, including their IP address, may be sent to the service provider. Thus, confirming the provider's privacy policy is recommended. These features include:

* reCAPTCHA ([Google](https://policies.google.com/?hl=en))
* Akismet ([Automattic](https://automattic.com/privacy/))

= Translations =

You can translate Contact Form Redux on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/contact-form-redux).

== Installation ==

1. Upload the entire `contact-form-redux` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

You will find a 'Contact' menu in your WordPress admin panel after installation and activation.

For complete documentation, visit the [plugin web site](https://cfr.backwoodsbytes.com/).

== Frequently Asked Questions ==

Do you have questions or issues with Contact Form Redux? Use these support channels:

1. [Documentation](https://cfr.backwoodsbytes.com/)
1. [Support Forum](https://wordpress.org/support/plugin/contact-form-redux/)

== Screenshots ==

1. The Contact Forms Page
2. The Edit Contact Form Page
3. The Options Page
4. A Basic Contact Form with Google reCAPTCHA v2

== Changelog ==

= 1.3.7 =

* Updates for PHP 8.2 compatibility.

= 1.3.6 =

* Updates for PHP 8.2 compatibility.

= 1.3.5 =

* Remove debugging code in JavaScript files.

= 1.3.4 =

* Updated contact-form-functions.php to replace get_page_by_title(), deprecated since WP 6.2.0.

= 1.3.3 =

* Changed the Welcome Panel CSS classes that conflicted with the WordPress 5.9 Welcome Panel CSS.
* Changed the Welcome Panel JavaScript function to avoid conflicts.

= 1.3.2 =

* Add default value user_full_name for form tags.

= 1.3.1 =

* Fix URLs to Configuration Validator documentation.
* Fix URL to Additional Settings Tab documentation in Editor.

= 1.3.0 =

* Add capability check to cfredux_upgrade.
* Add capability check to cfredux_admin_init.
* Sanitize filenames before file types check.
* Add WEBP file format to default allowed types.
* Add HTML, PHAR, JS, and SVG to proscribed file types.
* Some minor code cleanup.

= 1.2.3 =

* Add user IP and user agent to contact form template Body by default.
* Change fallback From email address to system rather than noreply.

= 1.2.2 =

* Update beforeunload change function in /admin/js/scripts.js.
* Remove code to constantly display Bulk Validation link.
* Remove jQuery pseudo-classes for form element selection in /admin/js/scripts.js and tag-generator.js.
* Remove jQuery pseudo-classes for form element selection in /includes/js/scripts.js.
* Remove unused captcha challenge in cfredux.refill in /includes/js/scripts.js.

= 1.2.1 =

* Remove Admin Thickbox dependency.
* Cleaner, more-legible, custom modal dialogs for Admin Tag Generator.
* Change Date Tag default value field to date type input.
* Remove deprecated jQuery pseudo-classes in JavaScript files.

= 1.2.0 =

* Fix bug preventing deletion of contact forms from the contact form edit pages.
* Minify CSS and JavaScript for admin, too, when the Option is selected.
* Add missing package name for Help Tabs translation.
* PHP clean-up.
* Remove pre-HTML5 jQuery UI fallback.
* Remove support for pre-HTML5, legacy themes.
* Remove admin jQuery UI elements.
* Remove inline JavaScript in admin and modules.

= 1.1.4 =

* Fix file upload vulnerability.
* Clean admin JavaScript.

= 1.1.3 =

* Update JavaScript.

= 1.1.2 =

* Remove Donate links.
* Update documentation URL.

= 1.1.1 =

* Added missing translation code for Spamhaus flag.

= 1.1.0 =

* Update for WP 5.5+, change blacklist_keys to disallowed_keys.
* Fix for Screen Options number of contact forms displayed.
* Add JavaScript-only anti-spam measure to Options.
* Add Spamhaus.org RBL check anti-spam measure to Options.
* Add Spamhaus.org RBL tag-only to anti-spam Options.

= 1.0.9 =

* Fix for refill permissions in /includes/rest-api.php for WP 5.5.

= 1.0.8 =

* Updates to /includes/rest-api.php for WP 5.5.

= 1.0.7 =

* Made Validate Contact Forms utility link on Contact Forms Page available at all times. 

= 1.0.6 =

* Fixed filter_var_array() expects parameter 1 to be array warning in /modules/checkbox.php.

= 1.0.5 =

* CSS fix for Twenty Twenty theme.
* CSS fix for Google reCAPTCHA.

= 1.0.4 =

* Added the capability to have multiple answers to quiz tag questions.

= 1.0.3 =

* Fixed an issue with post variable sanitization.
* Improved get variable sanitization.

= 1.0.1 =

* Fixed an issue with get varable sanitization.

= 1.0.0 =

* Initial release.

