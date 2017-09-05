=== Profiler QuickTally ===
Contributors: anthonyeden
Tags: fundraising, donations, tally, profiler
Requires at least: 4.0.0
Tested up to: 4.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Display your live Profiler Donations Tally on your website, via easy to use shortcodes.

== Description ==

Profiler is a CRM & Donation SaaS platform used by many non-profits around Australia. It has a special events module,
with realtime tally generation. This plugin allows you to embed these tallies on your website using some simple shortcodes.

== Installation ==

The easiest way to install this plugin is via the Wordpress Plugin Directory. Alternatively, download the ZIP and upload
it to your Wordpress website.

Once installed, you will need to specify your Profiler Tally XML URLs via the Profiler QuickTally Settings Screen.

Once configured, you can embed the following shortcodes anywhere on your website:

* [pftally_dollarsgoal friendly="true" nearestdollar="true" dollarsign="true"]
* [pftally_dollarscurrent friendly="true" nearestdollar="true" dollarsign="true"]
* [pftally_dollarsremaining friendly="true" nearestdollar="true" dollarsign="true"]
* [pftally_dollarspercentage round="0" percentagesign="true"]
* [pftally_comments random="true" limit="1"]

== Frequently Asked Questions ==

= What is 'Profiler'? =

Profiler is a CRM. 

= How do I configure the Profiler special events module? =

Please contact Profiler Support via the 'Help' link found in the bottom toolbar in Profiler. 

== Changelog ==

= 1.0.5 =
* Prepare release for the Wordpress Plugin directory

= 1.0.4 =
* Fix the shortcode instructions

= 1.0.3 =
* Fix button label.
* Add basic checking to ensure we don't cache non-existent data
* Add an indicator to show when the data re-cache task was run
* Settings screen: Display the times each individual feed was recached.
* Settings screen: Create a quick reference list of all shortcodes
* Set a really low action priority for the lazy recache

= 1.0.2 =
* Fix bug with not passing attributes correctly to numberHandling method.

= 1.0.1 =
* Add the [pftally_comments] shortcode

= 1.0.0 =
* Initial release
