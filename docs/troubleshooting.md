# Troubleshooting Drafts Publishing Immediately

If drafts publish immediately instead of being scheduled, the scheduled time WordPress receives is probably too close to the current time or being interpreted in the wrong timezone.

## Quick Checks

1. Open **Settings > Drip Scheduler**.
2. Check the **Timezone & Date Information** section.
3. Verify **Earliest Safe Schedule Time** is in the future.
4. Increase **Minimum Future Minutes**. Try `120` or `180` minutes if the default is too close.
5. Set **Timezone Override** if the WordPress timezone is incorrect.
6. Confirm the site timezone under **Settings > General** is correct.

## Why This Happens

WordPress schedules posts based on `post_date` and `post_date_gmt`. If those values are in the past, too close to the current server time, or calculated with the wrong timezone, WordPress may publish the post immediately.

Draft Drip Scheduler adds safety buffers and validates future dates before calling `wp_update_post()`, but server timezone settings and WordPress timezone settings can still affect the final result.

## Recommended Settings

Use these settings when troubleshooting:

* **Minimum Future Minutes:** Start with `120`.
* **Timezone Override:** Leave empty if WordPress timezone is correct. Otherwise use a PHP timezone string such as `America/New_York`, `Europe/London`, or `UTC`.
* **Default Start Time:** Use a time at least a few hours ahead while testing.

After adjusting settings, try scheduling one or two test drafts before scheduling a larger batch.
