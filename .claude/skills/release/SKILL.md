---
name: release
description: Create a new release of the plugin. Bumps version, updates changelogs, merges develop to main, creates GitHub release, and syncs back.
disable-model-invocation: true
allowed-tools: Read, Bash, Write, Edit, Grep, Glob
argument-hint: [patch|minor|major]
---

# Release Process

Create a new release of the pp-glossary plugin. The argument `$ARGUMENTS` specifies the version bump type: `patch`, `minor`, or `major`. If no argument is given, ask the user which type of bump they want.

## Step 0: Preflight checks

- Ensure we are on the `develop` branch. If not, switch to it.
- Run `git pull origin develop` to ensure we're up to date.
- Run `git status` to ensure the working tree is clean. If not, stop and ask the user to commit or stash changes.
- Determine the current version from `pp-glossary.php` (the `Version:` header).
- Calculate the new version based on the bump type (`$ARGUMENTS`):
  - `patch`: increment the patch number (e.g., 1.3.0 -> 1.3.1)
  - `minor`: increment the minor number, reset patch (e.g., 1.3.1 -> 1.4.0)
  - `major`: increment the major number, reset minor and patch (e.g., 1.3.0 -> 2.0.0)
- Show the user the current version, the new version, and ask for confirmation before proceeding.

## Step 1: Gather changelog entries

- Run `git log --oneline develop --not main` to see what commits are on develop that aren't on main.
- Also check recent merged PRs with `gh pr list --state merged --base develop --limit 20`.
- Draft changelog entries grouped into categories: **New**, **Enhancements**, **Bugfixes** (omit empty categories).
- Show the proposed changelog to the user and ask for approval or edits.

## Step 2: Bump version numbers

Update the version in these locations (all in one commit):

1. `pp-glossary.php` line with `* Version: X.Y.Z` - update to new version
2. `pp-glossary.php` line with `define( 'PP_GLOSSARY_VERSION', 'X.Y.Z' )` - update to new version
3. `readme.txt` line with `Stable tag: X.Y.Z` - update to new version

## Step 3: Update changelogs

Using the approved changelog entries from Step 1:

1. **README.md**: Add new version section under `## Changelog` (before the previous version). Use `### X.Y.Z` format.
2. **readme.txt**: Add new version section under `== Changelog ==` (before the previous version). Use `= X.Y.Z =` format.

## Step 4: Run quality checks

Run all three quality checks and ensure they pass:

```bash
composer check-cs
composer lint
composer phpstan
```

If any fail, fix the issues before proceeding. All three must pass.

## Step 5: Commit and push

- Stage all changed files: `pp-glossary.php`, `readme.txt`, `README.md`
- Commit with message: `Bump version to X.Y.Z and update changelogs for release`
- Push to `origin develop`

## Step 6: Merge develop into main

```bash
git checkout main
git pull origin main
git merge develop --no-ff -m "Merge branch 'develop' for vX.Y.Z release"
git push origin main
```

## Step 7: Create GitHub release

Create a GitHub release using the `gh` CLI:

- Tag: `X.Y.Z` (no `v` prefix - matches existing convention)
- Target: `main`
- Title: `X.Y.Z`
- Body: The changelog entries from Step 1, formatted in markdown

## Step 8: Sync main back into develop

```bash
git checkout develop
git merge main -m "Merge main back into develop to sync after vX.Y.Z release"
git push origin develop
```

## Step 9: Confirm

Show a summary of what was done:
- Old version -> New version
- Link to the GitHub release
- Confirm both `main` and `develop` are in sync
