# Releasing Draft Drip Scheduler

Use this checklist for each public release.

## 1. Prepare the Version

1. Bump `Version:` in `draft-drip-scheduler.php`.
2. Bump `DDS_VERSION` in `draft-drip-scheduler.php`.
3. Update `Stable tag:` in `readme.txt`.
4. Update the changelog in both `README.md` and `readme.txt`.
5. Run local checks:

```bash
composer install
composer run lint
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

## 2. Tag and Push

Create and push an annotated version tag:

```bash
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push --follow-tags
```

The release workflow builds a clean `draft-drip-scheduler.zip` and attaches it to the GitHub Release for the tag.

## 3. Create the GitHub Release

If the release does not already exist, create it with generated notes:

```bash
gh release create vX.Y.Z --generate-notes
```

If the release already exists, the workflow will upload or update `draft-drip-scheduler.zip`.

## 4. WordPress.org SVN Publishing

If this plugin is submitted to WordPress.org, publish the same release through the plugin SVN repository:

1. Copy release files to `/trunk`.
2. Copy the same release files to `/tags/X.Y.Z`.
3. Confirm `readme.txt` has the correct `Stable tag`.
4. Commit the SVN release.

Keep GitHub release artifacts and WordPress.org SVN tags aligned so users receive the same plugin code from either channel.
