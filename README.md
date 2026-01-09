# Draft Drip Scheduler

A WordPress plugin that allows you to bulk schedule draft posts to publish sequentially in the future (drip feed) with weekend skipping and time jitter options.

## Description

Draft Drip Scheduler solves the problem of flooding your WordPress site when bulk-creating draft posts. Instead of manually scheduling each post, you can select multiple drafts and automatically schedule them to publish sequentially with intelligent spacing.

### Key Features

- **Universal Post Type Support**: Works with Posts, Pages, and all public Custom Post Types (Products, Portfolio, etc.)
- **Schedule Now Button**: One-click scheduling of all draft posts from the settings page
- **Smart Stacking**: Automatically starts scheduling after your last scheduled post to prevent overlaps
- **Weekend Skipping**: Option to skip Saturdays and Sundays for more organic scheduling
- **Time Jitter**: Add random time variations (±X minutes) to make scheduling look more natural
- **Bulk Actions**: Easy-to-use bulk action in WordPress admin lists
- **Scheduled Date Column**: View scheduled publication dates directly in post lists

## Installation

1. Upload the `draft-drip-scheduler` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Drip Scheduler to configure your preferences

## Configuration

Navigate to **Settings > Drip Scheduler** to configure:

- **Default Start Time**: Time of day for the first post if no future posts exist (24-hour format, e.g., 08:00)
- **Interval (Hours)**: Time between each scheduled post (e.g., 24 for daily posts)
- **Skip Weekends**: Checkbox to avoid scheduling on Saturdays or Sundays
- **Random Jitter (Minutes)**: Randomly adjust schedule time by ±X minutes (set to 0 to disable)

## Usage

### Method 1: Schedule All Drafts (Quick Method)

1. Go to **Settings > Drip Scheduler**
2. Review the draft post counts displayed
3. Click the **"Schedule Now"** button
4. All draft posts across all post types will be scheduled sequentially

### Method 2: Selective Scheduling (Bulk Actions)

1. Go to any post list page (Posts, Pages, or Custom Post Types)
2. Select the draft posts you want to schedule
3. Choose "Auto Schedule / Drip" from the Bulk Actions dropdown
4. Click "Apply"
5. The plugin will automatically schedule all selected drafts sequentially

### Viewing Scheduled Dates

After scheduling, you can see when posts are scheduled to be published:
- A new **"Scheduled Date"** column appears in all post list pages
- Shows the date, time, and relative time (e.g., "In 2 days")
- Only displays for posts with "future" status

### How It Works

1. **Baseline Detection**: The plugin finds the latest scheduled post (post_status='future') for the selected post type
2. **Sequential Scheduling**: Each selected draft is scheduled after the previous one, respecting your interval settings
3. **Smart Features**: Weekend skipping and time jitter are applied automatically based on your settings
4. **Feedback**: You'll see a success message showing how many posts were scheduled and the date range

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## Frequently Asked Questions

### Does this work with Custom Post Types?

Yes! The plugin automatically detects all public post types and adds the bulk action to each one.

### What happens if I schedule posts that would fall on weekends?

If "Skip Weekends" is enabled, the plugin will automatically push those dates forward to the next Monday.

### Can I schedule posts that aren't drafts?

No, the plugin only schedules posts with "draft" status. This prevents accidentally rescheduling already published or scheduled posts.

### How does the time jitter work?

If you set jitter to 30 minutes, each scheduled time will be randomly adjusted by between -30 and +30 minutes. This makes your publishing schedule look more organic and less automated.

## Changelog

### 1.0.0
- Initial release
- Bulk scheduling for all public post types
- Weekend skipping option
- Time jitter feature
- Settings page
- Smart baseline detection
- Schedule Now button for one-click scheduling
- Scheduled Date column in post lists

## Support

For issues, feature requests, or questions, please contact the plugin author.

## License

GPL v2 or later

