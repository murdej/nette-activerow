# Changelog

## Unreleased

### Added
- Support for PHP `BackedEnum` types: columns typed as a `BackedEnum` subclass are now automatically converted from/to the enum's raw value when reading from or writing to the database.
- Support for static `fromDbValue()` method on serializable types: if the class exposes a static `fromDbValue()`, it is called instead of instantiating the object and calling the instance method.

### Fixed
- `DBEntity::getChanged()`: corrected condition that checked whether a serialized column value has changed — `isset($col, $this->src)` was replaced with `isset($this->src[$col])`, preventing a false "no change" result.
- Removed a stale commented-out line in `Converter::convertFrom()` (`// return $value; // $value->getTimestamp();`).
