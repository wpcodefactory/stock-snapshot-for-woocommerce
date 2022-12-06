=== Stock Snapshot for WooCommerce ===
Contributors: wpcodefactory, algoritmika, anbinder
Tags: woocommerce, stock, woo commerce
Requires at least: 5.0
Tested up to: 6.1
Stable tag: 1.3.2
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Keep track of your products stock in WooCommerce.

== Description ==

**Stock Snapshot for WooCommerce** plugin lets you keep track of your products stock in WooCommerce.

### &#9989; Main Features ###

* Automatically take your products **stock snapshots**.
* Customize snapshots **time interval**.
* View **product's stock history**.
* View **all stock snapshots history**.
* Use **shortcode** to display **recently restocked products**.
* Optionally set up **system cron** for the snapshots.

### &#127942; Premium Version ###

[Stock Snapshot for WooCommerce Pro](https://wpfactory.com/item/stock-snapshot-for-woocommerce/) plugin version allows you to get stock snapshot **emails**.

### &#128472; Feedback ###

* We are open to your suggestions and feedback.
* Thank you for using or trying out one of our plugins!
* [Visit plugin site](https://wpfactory.com/item/stock-snapshot-for-woocommerce/).

== Installation ==

1. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Start by visiting plugin settings at "WooCommerce > Settings > Stock Snapshot".

== Changelog ==

= 1.3.2 - 06/12/2022 =
* Fix - Duplicated admin "Settings" link removed.

= 1.3.1 - 06/12/2022 =
* Tested up to: 6.1.
* WC tested up to: 7.1.
* Readme.txt updated.
* Deploy script added.

= 1.3.0 - 05/04/2022 =
* Dev - General - Variable Product Options - "Include variations" option added (defaults to `yes`).
* Dev - Emails - Product name is wrapped in product link now.
* Dev - Admin settings descriptions updated.

= 1.2.0 - 31/03/2022 =
* Dev - "History" section added.
* Dev - Tools - "Take snapshot" tool added.
* Dev - Tools - "Delete all snapshots" tool added.
* Dev - General - "Allow snapshots via URL" option added (defaults to `no`).
* Dev - General - "Periodic snapshots" (defaults to `yes`) and "Interval" (defaults to `86400` seconds, i.e. once daily) options added.
* Dev - General - Add child products stock - Counting zero (`0`) children stock as well now.
* Dev - Emails - "Email subject" option added.
* Dev - Emails - "Email heading" option added.
* Dev - Emails - "Email content" option added.
* Dev - Emails - Wrapping in WC email template now.
* Dev - Emails - Using `wc_mail()` function instead of `wp_mail()` function now.
* Dev - "WP Cron" replaced with "Action Scheduler".
* Dev - Properly escaping all output now.
* Dev - Admin settings split into sections: "Tools", "Emails".
* Dev - Code refactoring.
* Free plugin version released.
* WC tested up to: 6.3.
* Tested up to: 5.9.

= 1.1.3 - 10/09/2021 =
* Dev - Advanced - "Clear plugin transients" tool added.
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` - Algorithm optimized.
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` - Now `null` stock is processed the same as `0` stock.
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` - `orderby` (defaults to `name`) and `order` (defaults to `ASC`) attributes added. In addition to the standard `orderby` options (`none`, `ID`, `name`, `type`, `rand`, `date`, `modified`), custom `last_restocked` option added.
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` - `paginate` attribute added (defaults to `no`).
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` - "Filter products by brand" widget compatibility added ("Perfect Brands for WooCommerce" plugin (https://wordpress.org/plugins/perfect-woocommerce-brands/)).
* WC tested up to: 5.6.

= 1.1.2 - 17/08/2021 =
* Dev - "Add child products stock" option added.

= 1.1.1 - 10/08/2021 =
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` - `total_snapshots` attribute added (defaults to `1`).
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` - `new_stock` attribute added (defaults to `no`).

= 1.1.0 - 08/08/2021 =
* Dev - Shortcodes - `[alg_wc_stock_snapshot_restocked]` shortcode added.
* Dev - Plugin is initialized on the `plugins_loaded` action now.
* Dev - Localisation - `load_plugin_textdomain()` function moved to the `init` action.
* Dev - Admin settings descriptions updated.
* Dev - Code refactoring.
* WC tested up to: 5.5.
* Tested up to: 5.8.

= 1.0.2 - 03/03/2020 =
* Dev - Product meta box - Now showing stock changes only.

= 1.0.1 - 26/02/2020 =
* Dev - Underscore added to the meta name.

= 1.0.0 - 21/02/2020 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
This is the first release of the plugin.
