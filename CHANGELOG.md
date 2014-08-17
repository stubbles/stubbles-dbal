4.0.0 (2014-08-07)
------------------

  * upgraded stubbles/core to 5.0.0


3.1.0 (2014-08-07)
------------------

  * added `stubbles\db\Database::query()`
  * added `stubbles\db\Database::fetchOne()`


3.0.0 (2014-07-31)
------------------

### BC breaks

  * removed namespace prefix `net`, base namespace is now `stubbles\db` only

### Other changes

  * upgraded to stubbles/core 4.x


2.4.0 (2014-05-08)
------------------

  * added `net\stubbles\db\Database::fetchRow()`


2.3.0 (2014-02-18)
------------------

  * changed `net\stubbles\db\Database` methods to work with prepared statements


2.2.0 (2014-02-07)
------------------

  * introduced concept of properties for database config, allows arbitrary values
     * added `net\stubbles\db\DatabaseConnection::property($name, $default)` to access connection config properties


2.1.0 (2014-01-31)
------------------

  * added `net\stubbles\db\Database` to allow easier access to query results
  * added `net\stubbles\db\ioc\ConnectionProvider::availableConnections()` to retrieve a list of available database connection ids
  * added possibility to store details for a database configuration
  * added `net\stubbles\db\DatabaseConnection::dsn()` and `net\stubbles\db\DatabaseConnection::details()` to get more information about the actual connection


2.0.0 (2013-11-10)
------------------

  * Initial release.
