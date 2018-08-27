# Changelog
All notable changes to Neat Database components will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Fixed
- Table identifier quoting in queries built using the Query builder.

## [1.1.0] - 2018-08-27
### Added
- Query builder fetch method.
- Connection will throw a QueryException on query errors.

### Fixed
- PDO instance without exceptions enabled results in internal errors.

## [1.0.1] - 2018-06-06
### Added
- Changelog.
- Documentation on retrieving the inserted id.
- Documentation on escaping and quoting values and identifiers.
- Quoting boolean values (returns '0' or '1').

## [1.0.0] - 2018-04-07
### Added
- Database connection.
- Live and fetched query result.
- Query builder.
- Unit tests.
