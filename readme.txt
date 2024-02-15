=== Multilocaly ===
Contributors: NewfieSoft
Donate link: https://newfiesoft.com/donate
Tags: multisite, multilingual, language, localization, switch,
Requires at least: 4.9.0
Tested up to: 6.4.3
Stable tag: 1.0.0
Requires PHP:  5.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

Based on WordPress multisite creates an independent multilingual website that can be linked to each other and switched on an easy way to use.

No matter how the structure of your site, whether it has different domains or you have a multisite inside one domain where you use like /de /fr /it and many many more the final decision is on your side.

One of the points of this plugin is that where you can have an independent database table for any of the sites because that is how it works wordpress multisite. But for it is better because you can have independent themes, functions, and a team of people who can work on that site.

👉 Please visit the [Github page](https://github.com/newfiesoft/multilocaly "Github") for the latest code development, planned enhancements and known issues 👈

### Features

* Base on WordPress Multisite.
* Independent site name structure.
* Simple site switch in many ways like just site name, language name, country flags.
* Set different languages for any of your sites and in that way, you get full multilingual site.
* Types of languages that users can use inside wp-admin, independent of what can be used on the public front site.
* SEO structure optimisation but at the same moment independent for any multi-site.
* Disable Gutenberg style.
* Remove "WordPress" from the title on any case scenario and on all available Site Language inside WordPress settings.
* Disable the meta generator.


And all of that you can manage inside /wp-admin/network/, and full control right when you enable wordpress multisite.

One of our important features is that, for any site, you can set different languages, and in that way, you get full multilingual site. But the wp-admin language for any site can be different.

And all of that you can manage inside /wp-admin/network/, and full control right when you enable wordpress multisite.


== Screenshots ==



== Installation ==

= Simple Modern Way =
1. Go to the WordPress Dashboard, from the <strong>Plugins</strong> menu you can see Add New click on that.
2. On the right side, you can see the Search field. In that field enter <strong>Multilocaly</strong>.
3. Click on <strong>Install Now</strong>, then <strong>Activate</strong>.

= Manual Old Way =

1. Unzip the downloaded zip file
2. Upload the plugin folder into the <strong>wp-content/plugins/</strong> directory of your WordPress site.
3. Go to the <strong>WordPress Dashboard</strong>, and click on <strong>Plugins</strong> on the list of plugins you will see <strong>Multilocaly</strong> from the Plugins page.
4. Click on <strong>Activate</strong>.

= After Install =

When you activate the plugin, the plugin checks for who configured your wordpress. And if you do not have an active wordpress multisite, you get a message where you can follow how to enable wordpress multisite.


== Frequently Asked Questions ==

= Is this plugin free? =

Yes, this plugin version is 100% free.

= How to enable wordpress multisite? =

Inside you wp-config.php Add below this line of code:

/* Multisite */
<strong>define( 'WP_ALLOW_MULTISITE', true );</strong>

After line with content /* Add any custom values between this line and the "stop editing" line. */

When you add and save changes on wp-config.php you can just refresh wp-admin/plugins.php and inside the plugin, you can see Network Setup.

Or Navigate to Tools > Network Setup

where you can continue the configuration of future wordpress multisite and follow the prompts.

= If you get a message like Warning: Please deactivate your plugins before enabling the Network feature. =

You need to deactivate all your active plugins and back on Network Setup. In that case, if you get that message we suggest you, open the plugin on a new tab, and when you deactivate all messages just refresh the Network Setup page.


== Changelog ==

= 1.0.0 - 15.02.2024 =
* First release
