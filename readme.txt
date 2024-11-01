=== XML Sitemap for Stella ===
Contributors: luistinygod
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal%40geethemes%2ecom&item_name=XML%20Sitemap%20for%20Stella&no_shipping=1&cn=Donation%20Notes&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: google, xml sitemap, google sitemap, sitemaps, yahoo, bing, ask.com, moreover.com, aol.com, Stella, multi-language, language localization, robots
Requires at least: 3.5
Tested up to: 4.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generates a XML Sitemap on your WordPress multi-language website when powered by Stella multi-language plugin.

== Description ==

Generates a XML Sitemap on your WordPress multi-language website when powered by Stella multi-language plugin.
This plugin generates multi-language XML Sitemap on the fly, automatically pings Google and Bing everytime content is published, and includes sitemap url in WordPress robots.txt.

Why a Sitemap is so important? It's much easier for the crawlers to see the complete structure of your site and retrieve it more efficiently. By adding the Sitemap url to the robots.txt file search engines like ask.com, Yahoo and others will also be able to crawl your site more easily, increasing your website awareness.


= Features =
* Generates Sitemap files, for each language, on the fly and automatically (no static files in the disk)
* Sitemaps include post/page images, according to sitemap protocol (sitemaps.org)
* Automatically notifies Google and Bing everytime content is published
* Inserts the Sitemap index URL into WordPress robots.txt file according to best SEO practices.
* Works with custom post types and custom taxonomies. You may exclude any of these if needed.


Very easy to use and to setup!

= Translations =
Do you want to help translating this plugin? If you'd like to help, [please let me know]( http://tinygod.pt/contact/ "Contact form").



= Notes =
* Ask.com, moreover.com, aol.com don't support sitemap submission anymore. Their recommendation is to include the Sitemap URL in the robots.txt file.
* This release is not compatible with the new multisite feature of WordPress 3.0 yet. We're working on that!
* This release is compatible with all WordPress versions since 3.5. If you are still using an older one, upgrade your WordPress **NOW!**



== Installation ==

1. Upload the `xml-sitemap-for-stella` folder to the `/wp-content/plugins/` directory
1. Activate the **Sitemaps for Stella** plugin through the 'Plugins' menu in WordPress
1. Configure the plugin by going to the **Sitemaps for Stella** menu that appears in your *Settings* menu

That’s all! We hope that you’ll like our plugin. Suggestions, questions and other feedback are welcome: [Contact]( http://tinygod.pt/contact/ "Contact form")

== Frequently Asked Questions ==

= Does it support host names for multiple languages? =

Yes, it does support both Stella modes: with or without hostnames per language.


= Which sitemap should be submitted to Google Webmaster Tools? =

If you'd like to manually submit your sitemap into Webmaster tools like Google, use the index file *sitemap-index.xml*.




== Screenshots ==

1. Plugin settings page




== Changelog ==
= 1.1.0 =
* Revision for WP 3.7
* i18n ready

= 1.0.5 =
* small revision for WP 3.6

= 1.0.4 =
* Allow activation when using Stella free version.

= 1.0.3 =
* Removed wp_die() when checking if Stella plugin is installed.
* Strong mechanism to verify if Stella plugin is activated, self-deactivation in case Stella plugin is deactivated.

= 1.0.2 =
* Minor changes sitemap stylesheet

= 1.0.1 =
* Minor changes on error messages and external links

= 1.0.0 =
* Initial release.
* No multisite or XML News Sitemap features. More to come shortly.

== Upgrade Notice ==

Nothing to declare
