2.2.0 (2014-02-??)
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
