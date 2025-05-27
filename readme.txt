=== News Sitemap Generator ===
Contributors: andausman
Tags: sitemap, news, google news, xml, seo
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple plugin to generate a Google News-compatible XML sitemap for your WordPress news publication.

== Description ==
This plugin generates a Google News XML sitemap at /newsfeed.xml for your WordPress site. It supports category filtering, Google News pinging, and a simple admin settings page.

== Installation ==
1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Permalinks** and click **Save Changes** to refresh your site's rewrite rules. (See Screenshot 3)
4. Configure settings under **Settings > News Sitemap**.

== Usage Guide ==
Follow these steps to use the News Sitemap Generator plugin:

1. **Install & Activate**
   - Upload and activate the plugin as described above.
   - ![Install & Activate](assets/screenshot-1.png)
2. **Refresh Permalinks**
   - Go to **Settings > Permalinks** in your WordPress dashboard.
   - Click **Save Changes** (no need to modify anything).
   - ![Permalink Settings](assets/screenshot-3.png)
   - This step is required for the sitemap to work at `/newsfeed.xml`.
3. **Configure Plugin Settings**
   - Go to **Settings > News Sitemap**.
   - Set your publication name, enable/disable Google News ping, and optionally filter by category.
   - ![Settings Page](assets/screenshot-1.png)
4. **View Your News Sitemap**
   - Visit `https://yourdomain.com/newsfeed.xml` to see your Google News-compatible sitemap.
   - ![Sitemap Example](assets/screenshot-2.png)
5. **Regenerate Sitemap or Clear Ping Log**
   - Use the buttons on the settings page to manually regenerate the sitemap or clear the ping log if needed.

== Frequently Asked Questions ==
= Where is my news sitemap? =
Your sitemap is available at https://yourdomain.com/newsfeed.xml

= Does this work with Yoast SEO? =
Yes, but it is fully standalone and does not require Yoast SEO.

= How do I change the publication name? =
Go to Settings > News Sitemap and update the Publication Name field.

== Changelog ==
= 1.0 =
* Initial release.

== Upgrade Notice ==
= 1.0 =
First public release.

== Screenshots ==
1. News Sitemap settings page (assets/screenshot-1.png)
2. Example XML output (assets/screenshot-2.png)
3. Permalink settings page (assets/screenshot-3.png)

== License ==
This plugin is free software, released under the GPLv2 or later.
