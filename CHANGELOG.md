# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
