# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.2.0 - 2017-11-02

### Added

- [#6](https://github.com/zfcampus/zf-asset-manager/pull/6) adds support for
  PHP 7.1 and 7.2.

### Changed

- [#5](https://github.com/zfcampus/zf-asset-manager/pull/5) changes a number of
  internals:

  - Install and uninstall operations check for `asset_manager` configuration
    directives in a config file before including it, skipping the file if none
    are detected.

  - Install and uninstall operations verify that a config file does not include
    `exit()` or `eval()` statements before attempting to consume it; if they are
    present, the plugin emits a warning.

  - Uninstall operations during a package update are now performed during the
    `pre-package-update` event instead of together with install operations
    during 'post-package-update`. This prevents autoloader issues when classes,
    constants, etc. referenced by the configuration change between versions of a
    package.

  - Install operations are aggregated during the `post-package-install` event,
    and then executed en masse during the `post-autoload-dump` event. This
    prevents autoloader issues due to unknown classes that were reported
    in previous versions.

### Deprecated

- Nothing.

### Removed

- [#6](https://github.com/zfcampus/zf-asset-manager/pull/6) removes support for
  HHVM.

### Fixed

- Nothing.

## 1.1.1 - 2016-08-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/zfcampus/zf-asset-manager/pull/3) fixes the
  `onPostPackageUpdate()` logic to pull both the initial and target package from
  the `UpdateOperation` composed in the `PackageEvent`, and to create
  new `PackageEvent` instances containing appropriate `UninstallOperation` and
  `InstallOperation` instances to pass to the uninstaller and installer.

## 1.1.0 - 2016-08-12

### Added

- [#2](https://github.com/zfcampus/zf-asset-manager/pull/2) adds a
  post-package-update event handler that uninstalls assets for the package, and
  then installs any defined in the new package version. This feature will allow
  seamless updating of assets as they are updated in package dependencies.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.1 - 2016-08-10

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#1](https://github.com/zfcampus/zf-asset-manager/pull/1) fixes how the
  `public/.gitignore` file is populated, ensuring no duplicates are created.

## 1.0.0 - 2016-07-26

Initial stable release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
