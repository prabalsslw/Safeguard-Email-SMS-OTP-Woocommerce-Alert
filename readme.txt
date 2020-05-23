=== Safegaurd ===
Contributors: Prabal MallicK
Tags: safe, secure, login, 2-step, verification, safety, otp, woocommerce, alert, email, pin
Author URI: https://prabalsslw.github.io/
Plugin URI: https://prabalsslw.github.io/
Version: 1.0.0
Requires at least: 3.0.1
Tested up to: 5.4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Safeguard is an SMS & Email based OTP service provider plugin. Also available woocommerce transactional alert.

== Prerequisites ==
- Wordpress 5.x.x
- WooCommerce 4.1.x
- cURL php extension.

== Download ==
* Using HTTPS 
$ git https://github.com/prabalsslw/Safeguard-Email-SMS-OTP-Woocommerce-Alert.git
* Using SSH 
$ git clone git@github.com:prabalsslw/Safeguard-Email-SMS-OTP-Woocommerce-Alert.git

== Description ==

Secure your WordPress site with WordPress Safegaurd.

WordPress Safegaurd provides 2-step verification on login. Once a user submits their login credentials, a One Time Pin (OTP) will be sent to them via SMS/Email. They will enter this OTP in order to continue to login. All it customer will able to get Woocommerce order alert notification via SMS.

Stop Brute force hacking attempts, and keep your data safe!

    * Easy to install!
    * Email & SMS both are integrated
    * Any SMS API can be Configurable
    * WordPress 4.0 Ready!

== Installation ==

- Step 1: Upload the plugin to wordpress admin panel. [Img-1]
- Step 2: Go to `Safeguard Login` > `OTP Settings` page.
- Step 3: `Enable Plugin` to activate all service.
- Step 4: Enable OTP checkbox for OTP & SMS Alert. `OTP SMS Text` must contain `{{OTP}}` dynamic variable. [Img-2]
- Step 5: Both GET & POST API can be configurable, use API Endpoint, pass the API parameter with dynamic variable. Fixed dynamic variables: `{{phone_number}}, {{unique_id}}, {{sms_text}}`
[Img-3]
- Step 6: In the `Woocommerce Alert Configuration` part must enable `
Enable Woocommerce Alert` for Woocommerce transaction alert. [Img-4]
- Step 7: Enable your required Hook for transactional alert. [Img-4]
- Step 8: You can change your `Woocommerce SMS Alert Templete`. Fixed dynamic variables: `{{name}}, {{status}}, {{amount}}, {{currency}}, {{order_id}}`. [Img-5]
- Step 9: You can change your `User Registration Alert Templete`. Fixed dynamic variables: `{{name}}`. [Img-5]
- Step 10: After successfully integrating the plugin you can see the reflection in the login and registration page. [Img-6]
- Step 11: Plugin will add an extra field(Phone Number) in the registration form. [Img-7]
- Step 12: Admin can check OTP & Alert SMS record from the admin panel . [Img-8]

**Note:** In order to use this plugin, you will need a valid WordPress install. This plugin will not work on a wordpress.com hosted site.

In order for this plugin to function correctly, you will need to use a permalink structure that uses rewrite rules. The "Post name" structure is recommended.

For accurate login time tracking, make sure your correct Timezone is selected under "Settings" > "General".

== License ==
- GPL2

== Changelog ==


