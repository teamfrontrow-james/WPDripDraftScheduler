=== Draft Drip Scheduler ===
Contributors: teamfrontrow-james
Tags: scheduling, drafts, drip content, bulk actions, publishing
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk schedule draft posts to publish sequentially in the future with weekend skipping and time jitter options.

== Description ==

Draft Drip Scheduler solves the problem of flooding your WordPress site when bulk-creating draft posts. Instead of manually scheduling each post, you can select multiple drafts and automatically schedule them to publish sequentially with intelligent spacing.

= Key Features =

* Universal Post Type Support: Works with Posts, Pages, and all public Custom Post Types, including Products and Portfolio items.
* Schedule Now Button: One-click scheduling of all draft posts from the settings page.
* Smart Stacking: Automatically starts scheduling after your last scheduled post to prevent overlaps.
* Weekend Skipping: Option to skip Saturdays and Sundays for more organic scheduling.
* Time Jitter: Add random time variations to make scheduling look more natural.
* Bulk Actions: Easy-to-use bulk action in WordPress admin lists.
* Scheduled Date Column: View scheduled publication dates directly in post lists.

== Installation ==

1. Upload the `draft-drip-scheduler` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to Settings > Drip Scheduler to configure your preferences.

== Frequently Asked Questions ==

= Does this work with Custom Post Types? =

Yes. The plugin automatically detects all public post types and adds the bulk action to each one.

= What happens if I schedule posts that would fall on weekends? =

If Skip Weekends is enabled, the plugin will automatically push those dates forward to the next Monday.

= Can I schedule posts that are not drafts? =

No. The plugin only schedules posts with draft status. This prevents accidentally rescheduling already published or scheduled posts.

= How does the time jitter work? =

If you set jitter to 30 minutes, each scheduled time will be randomly adjusted by between -30 and +30 minutes. This makes your publishing schedule look more organic and less automated.

= What should I do if drafts publish immediately? =

Check the Timezone & Date Information section on the settings page, verify the Earliest Safe Schedule Time is in the future, increase Minimum Future Minutes, and confirm your WordPress timezone setting. See the project troubleshooting guide for more detail.

== Changelog ==

= 1.1.2 =

* Fixed bug where posts would publish immediately instead of being scheduled when using bulk scheduling from the app home.
* Added the `edit_date => true` parameter to `wp_update_post()` calls so WordPress respects the post date values.
* Fix applies to both the Settings page Schedule Now feature and the Posts list bulk action.

= 1.1.1 =

* Fixed critical bug where posts would publish immediately on first install.
* Improved baseline date calculation to always start from current time plus buffer.
* Added debug logging to post meta for troubleshooting.
* Fixed timezone handling in baseline calculation.
* Baseline now uses current GMT time plus minimum minutes instead of tomorrow.

= 1.1.0 =

* Added timezone override setting for scheduling calculations.
* Added minimum future minutes setting to prevent immediate publishing.
* Implemented multi-layer future date validation with safety buffers.
* Added timezone and date information panel in settings.
* Improved date validation using multiple time sources.
* Enhanced reliability to prevent posts from publishing immediately.
* Removed external API dependency.

= 1.0.0 =

* Initial release.
* Bulk scheduling for all public post types.
* Weekend skipping option.
* Time jitter feature.
* Settings page.
* Smart baseline detection.
* Schedule Now button for one-click scheduling.
* Scheduled Date column in post lists.

== Upgrade Notice ==

= 1.1.2 =

Recommended update for anyone using bulk scheduling. This release fixes cases where drafts could publish immediately instead of being scheduled.

= 1.1.1 =

Recommended update for first-time installs and sites affected by immediate publishing.

= 1.1.0 =

Adds timezone and future-date safeguards for more reliable scheduling.
