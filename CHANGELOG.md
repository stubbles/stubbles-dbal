# Changelog

## 9.0.1 (2019-12-03)

* fixed invalid default parameters for calls to `PDOStatement::fetch()`

## 9.0.0 (2019-11-22)

### BC breaks

* raised minimum required PHP version to 7.3.0
* added more type hints
* `stubbles\db\config\DatabaseConfiguration::getInitialQuery()` now returns an empty string if no initial query configured
* parameter `$type` of `stubbles\db\Statement::bindParam()` and `stubbles\db\Statement::bindValue()` must be an int
* `stubbles\db\DatabaseConnection::getLastInsertId()` now returns a `string` instead of `int`

## 8.0.0 (2016-07-31)

### BC breaks

* raised minimum required PHP version to 7.0.0
* introduced scalar type hints and strict type checking

## 7.0.0 (2016-06-19)

### BC breaks

* Raised minimum required PHP version to 5.6
* Methods which threw `stubbles\lang\exceptions\ConfigurationException` now throw a `\OutOfBoundsException` or `\LogicException`

## 6.0.0 (2015-05-28)

### BC breaks

* removed `stubbles\db\Datebase::map()`, use `stubbles\db\Datebase::fetchAll()->map()->values()` instead, was deprecated since 5.0.0

### Other changes

* upgraded stubbles/core to 6.0.0

## 5.0.1 (2015-05-04)

* fixed warning when iterating over result set: use default column index when none specified

## 5.0.0 (2015-04-01)

### BC breaks

* deprecated `stubbles\db\Datebase::map()`, use `stubbles\db\Datebase::fetchAll()->map()->values()` instead, will be removed with 6.0.0
* `stubbles\db\Datebase::fetchAll()` and `stubbles\db\Datebase::fetchColumn()` now return an instance of `stubbles\lang\Sequence` instead of an array
* changed default fetch mode for PDO from `\PDO::FETCH_BOTH` to `\PDO::FETCH_ASSOC`

## 4.1.0 (2014-09-29)

* upgraded stubbles/core to 5.1.0

## 4.0.0 (2014-08-17)

### BC breaks

* replaced `stubbles\db\config\DatabaseConfigReader` with `stubbles\db\config\DatabaseConfigurations`
* replaced `stubbles\db\ioc\ConnectionProvider` with `stubbles\db\DatabaseConnections`
* replaced `stubbles\db\ioc\DatabaseProvider` with `stubbles\db\Databases`
* removed `stubbles\db\ioc\DatabaseBindingModule`, did not provide any additional value

### Other changes

* upgraded stubbles/core to 5.0.0

## 3.1.0 (2014-08-07)

* added `stubbles\db\Database::query()`
* added `stubbles\db\Database::fetchOne()`

## 3.0.0 (2014-07-31)

### BC breaks

* removed namespace prefix `net`, base namespace is now `stubbles\db` only

### Other changes

* upgraded to stubbles/core 4.x

## 2.4.0 (2014-05-08)

* added `net\stubbles\db\Database::fetchRow()`

## 2.3.0 (2014-02-18)

* changed `net\stubbles\db\Database` methods to work with prepared statements

## 2.2.0 (2014-02-07)

* introduced concept of properties for database config, allows arbitrary values
  * added `net\stubbles\db\DatabaseConnection::property($name, $default)` to access connection config properties

## 2.1.0 (2014-01-31)

* added `net\stubbles\db\Database` to allow easier access to query results
* added `net\stubbles\db\ioc\ConnectionProvider::availableConnections()` to retrieve a list of available database connection ids
* added possibility to store details for a database configuration
* added `net\stubbles\db\DatabaseConnection::dsn()` and `net\stubbles\db\DatabaseConnection::details()` to get more information about the actual connection

## 2.0.0 (2013-11-10)

* Initial release.
