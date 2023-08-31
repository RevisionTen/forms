# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.2.1] - 2023-08-31
### Changed
- Invalid form requests now set a `_invalidForm`-attribute on the main request, which can be used to support turbo forms.

## [3.2.0] - 2023-02-07
### Changed
- *BREAKING* `RevisionTen\Forms\Interfaces\ItemInterface`: Changed `onValidate` and `onSubmit` method signatures to pass `$data` by reference.

## [3.1.2] - 2022-11-11
### Changed
- Fix deprecations

## [3.1.1] - 2022-11-11
### Changed
- Fix deprecations

## [3.1.0] - 2022-11-11
### Changed
- Allow PHP 8.1

## [3.0.9] - 2022-09-30
### Changed
- Add support for conditional form-item forms

## [3.0.8] - 2022-09-01
### Changed
- Enable form submission saving by default

## [3.0.7] - 2022-08-25
### Fixed
- Fixed FormRead relationship class name in FormSubmission

## [3.0.6] - 2022-07-29
### Changed
- Improved form templates

## [3.0.5] - 2022-07-29
### Changed
- Removed globally registered form theme, set form theme explicitely in form template

## [3.0.4] - 2022-07-12
### Changed
- Allow html in form labels

## [3.0.3] - 2022-06-07
### Added
- Added support for twig form submission data vars in success messages

## [3.0.2] - 2022-01-19
### Fixed
- Added missing form constraints

## [3.0.1] - 2021-12-13
### Fixed
- Fixed sender empty name causing type error

## [3.0.0] - 2021-06-07
### Changed
- Bundle now requires version 3 of the CQRS and CMS bundle and Symfony ^5.3

## [2.0.9] - 2020-11-17
### Changed
- Updated ItemInterface, deprecated `getItem` method, use `buildItem` instead

## [2.0.8] - 2020-11-16
### Changed
- Added `getEmail` method to ItemInterface

## [2.0.7] - 2020-08-27
### Changed
- Compatibility fix for PHP 7.1

## [2.0.6] - 2020-08-20
### Changed
- Bugfix

## [2.0.5] - 2020-05-26
### Changed
- Improved templates and made select fields with emails as values secure

## [2.0.4] - 2020-05-05
### Added
- Added option to scroll to form success message after submitting (you can edit the scroll offset by overwriting the form default template)
- **Update your database schema**
### Changed
- **Added scroll javascript to `Templates/Frontend/form.html.twig`**
- Bugfixes

## [2.0.3] - 2019-11-08
### Changed
- Code cleanup
- Added method to verify form before submitting it

## [2.0.2] - 2019-11-07
### Changed
- Return form object in service

## [2.0.1] - 2019-11-07
### Changed
- Fixed form validation

## [2.0.0] - 2019-08-22
### Changed
- Upgraded CQRS classes

## [1.1.6] - 2019-08-13
### Changed
- Fixed onValidate return value

## [1.1.5] - 2019-07-08
### Changed
- Added option to disable CSRF protection

## [1.1.4] - 2019-05-29
### Added
- Added form submission tracking (**database update required**)
- Added TrackingController
- Added form submission entity list

## [1.1.3] - 2019-05-22
### Changed
- Added `aria-label` to form items to improve accessibility

## [1.1.2] - 2019-04-17
### Changed
- Bugfixes
- Changed **Frontend/form.html.twig**

## [1.1.1] - 2019-02-27
### Changed
- Bugfixes

## [1.1.0] - 2019-02-14
### Added
- Added bootstrap custom forms classes to form items
- Added option to disable form item label

## [1.0.6] - 2019-02-05
### Changed
- Fixed template override in form options
- Refactored FormController

## [1.0.5] - 2019-02-04
### Changed
- Fixed controller deprecation

## [1.0.4] - 2019-02-01
### Changed
- Fixed form read model

## [1.0.3] - 2019-01-16
### Added
- Added form submission saving (**database update required**)
- Added form submission download

## [1.0.2] - 2018-11-30
### Changed
- Whitespace fixed

## [1.0.1] - 2018-11-30
### Changed
- Fixed numeric choice values

## [1.0.0] - 2018-11-21
### Changed
- Code refactored and cleaned

## [0.0.1] - 2018-11-16
### Added
- Dev release for projects that use a symfony version older than 3.4.0
- Added version constant to FormsBundle
