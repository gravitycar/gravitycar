<?php
/**
MySQLi.inc (class for interfacing with MySQL DB via PHP's mysqli extension).

This class is the db connection class for using PHP's mysqli extension instead
of the deprecated mysql extension. It uses the object-oriented methods in
mysqli to perform the same tasks as the mysql version. It will still use 
gcMySQLQuery and gcMySQLWhereClause classes for building queries dynamically.
It also supports the test-only and debug modes. All applications should use this
class for anything to do with mysql. No application should ever directly call
a mysqli function.
**/

define("MYSQLI_DATE_FORMAT", "");

class gcMySQLiDB
{
   private static $instance;
   /*
   private $hostname = "localhost";
   private $username = "mike";
   private $password = "bageldog";
   private $databaseName = "gravitycar";
   */
   private $hostname = "dog.he.net";
   private $username = "gravityc";
   private $password = "bagel\$44";
   private $databaseName = "gravityc";
   public $msgCeneter = null;
   public static $connection = null;
   private $debug = false;
   private $testMode = false;
   private $logFile = "mysqllog";
   private $results = array();
   private $storeResults = true;
   
   /**
   :Function: Singleton
   
   :Description:
   Returns a reference to the same instance of this class whenever it's called.
   If no instance of this class has been created, an instance is created and
   stored in a static variable.
   
   :Parameters:
   None
   
   :Return Value:
   gcMySQL - the instance of the MySQL object.
   
   :Notes:
   The gcMySQLiDB class also contains a reference to the msg center, so error
   messages will be recorded there.
   **/
   public static function Singleton()
   {
      if (!IsSet(self::$instance))
      {
         
         $c = __CLASS__;
         self::$instance = new $c;
         self::$instance->msgCenter = gcMessageCenter::Singleton();
         
         if (strpos($_SERVER['SERVER_NAME'], "gravitycar.com") !== false)
         {
            self::$instance->hostname = "localhost";
            self::$instance->username = "gravityc";
            self::$instance->password = "bagel\$44";
            self::$instance->databaseName = "gravityc";
         }
      }
      return self::$instance;
   }

   
   /**
   :Function: setTestMode()
   
   :Description:
   This function turns the gcMySQLDB object's testMode property from true (or 
   "on") to false (or "off"). When test mode is on, sql queries are output to
   the browser and then run.
   
   :Parameters:
   mixed $mode - if true, sets the testMode to true, otherwise to false.
   
   :Return Value:
   None.
   
   :Notes:
   Queries are still executed when testMode is on.
   **/
   function setTestMode($mode=true)
   {
      if (!is_bool($mode))
      {
         $mode = true;
      }
      $this->testMode = $mode;
   }
   
   
   /**
   :Function: setDebug()
   
   :Description:
   This function turns the gcMySQLDB object's debug property from true (or 
   "on") to false (or "off"). When debug is set to true, sql queries are output
   to the browser and NOT run.
   
   :Parameters:
   mixed $mode - if true, sets the debug to true, otherwise to false.
   
   :Return Value:
   None.
   
   :Notes:
   Queries are NOT run when debug is on.
   **/
   function setDebug($mode=true)
   {
      if (!is_bool($mode))
      {
         $mode = true;
      }
      $this->debug = $mode;
   }

   
   /**
   :Function: getSelectQuery()
   
   :Description:
   This function takes several arguments and builds a SELECT statement query 
   based on them.
   
   :Parameters:
   string $table - the name of the table. At this time, joins are not supported.
   array $columns - an array of column names to select. If the array is missing
      or empty, "*" (all columns) is the default.
   
   :Return Value:
   string - a valid sql statement.
   
   :Notes:
   Support for where clauses is limited: You can only do "AND" conjunctions for
   your where clauses, like "where firstName = "George" AND lastName = "Bush".
   For more complex where clauses, pass in an empty array to this method and 
   add your where clauses using gcMySQLQuery->addWhereClause().
   **/
   function getSelectQuery($table, $columns="*", $where="", $orderBy="")
   {
      $query =  new gcMySQLQuery("SELECT", $table, $columns);
      $query->setConnection($this->connection);
      if ($where != "")
      {
         if (!is_array($where))
         {
            $where = array($where);
            //$this->msgCenter->addDebug("Query for $table passed a non-array for 'WHERE' clause.");
         }
         else
         {
            foreach ($where as $columnName => $value)
            {
               $query->addWhereClause($columnName, $value);
            }
         }
      }
      
      if ($orderBy != "")
      {
         if (!is_array($orderBy))
         {
            $orderBy = array($orderBy);
            //$this->msgCenter->addDebug("Query for $table passed a non-array for 'ORDER BY' clause.");
         }
         $query->setOrderByClauses($orderBy);
      }
      return $query;
   }
   
   
   /**
   :Function: runSelectQuery()
   
   :Description:
   A shortcut convenience method for simple select queries. This function takes
   a table name and an optional column list as args, creates a select query,
   runs that query and returns the resulting resource.
   
   :Parameters:
   string $table - the name of the table. At this time, joins are not supported.
   array $columns - an array of column names to select. If the array is missing
      or empty, "*" (all columns) is the default.
   hash $where - a hash with column names as the keys and values to search for
      as the values. NOTE: for joins, your column names will need to have their
      table names prepended to them.
   array $orderBy - an array of column names and "ASC" or "DESC". Column names
      should have their table name prepended to them.
   
   :Return Value:
   resource - a mysql result.
   
   :Notes:
   Support for where clauses is limited: You can only do "AND" conjunctions for
   your where clauses, like "where firstName = "George" AND lastName = "Bush".
   For more complex where clauses, pass in an empty array to this method and 
   add your where clauses using gcMySQLQuery->addWhereClause().
   **/
   function runSelectQuery($table, $columns="*", $where="", $orderBy="")
   {
      //debug_print_backtrace();
      $query = $this->getSelectQuery($table, $columns, $where, $orderBy);
      return $this->runQuery($query);
   }
   
   
   /**
   :Function: getHash()
   
   :Description:
   Takes a result object from a select query and iterates through that object 
   to return an array of associative arrays which comprises all of the columns
   that resulted from the query.
   
   :Parameters:
   msyqli_result $result (optional) - a mysqli_result object produced by a 
      select query.
   
   :Return Value:
   array - an array of the associative arrays returned by mysqli::fetch_assoc.
   
   :Notes:
   **/
   function getHash(&$result="")
   {
      if ($result === "")
      {
         $result = $this->results[0];
      }
      
      $hash = array();
      if ($result)
      {
         while ($row = $this->getRow($result))
         {
            $hash[] = $row;
         }
         $result->close();
      }
         
      return $hash;
   }
   
   
   /**
   :Function: getRow()
   
   :Description:
   Takes a mysqli_result object and calls its fetch_assoc() method and returns
   the result. You can pass a mysqli_result object into this method or, by
   default, you can call it with no arguments and it will use the most recently
   run query result from the results property.
   
   :Parameters:
   mysqli_result $result (optional) - a mysqli_result object produced by a 
      select query.
      
   :Return Value:
   hash - the associative array returned by mysql_fetch_array().
   
   :Notes:
   **/
   function getRow(&$result=false)
   {
      if ($result === false)
      {
         $result = $this->results[0];
      }
      
      if ($result)
      {
         return $result->fetch_assoc();
      }
      else
      {
         return array();
      }
   }
   
   
   /**
   :Function: getNewRowID()
   
   :Description:
   Returns the row ID of the last inserted row.
   
   :Parameters:
   None
   
   :Return Value:
   int - the ID of the last inserted row.
   
   :Notes:
   **/
   function getNewRowID()
   {
      return $this->connection->insert_id;
   }
   
   
   /**
   :Function: getNumRecords()
   
   :Description:
   Returns the number of records returned from a mysql query.
   
   :Parameters:
   resource $result - a mysql select query result object.
   
   :Return Value:
   int - the number of records or rows returned by a query.
   
   :Notes:
   **/
   function getNumRecords($result=false)
   {
      if ($result === false)
      {
         $result = $this->results[0];
      }
      return $result->num_rows;
   }
   
   
   /**
   :Function: getInsertQuery()
   
   :Description:
   This method takes its arguments and builds a mysql insert query from
   them. It then returns the query object.
   
   :Parameters:
   string $table - the name of the table.
   hash $data - a hash with column names as keys an property values as values.
   
   :Return Value:
   gcMySQLQuery - a query object of type INSERT.
   
   :Notes:
   **/
   function getInsertQuery($table, $data)
   {
      $columns = array_keys($data);
      $query = new gcMySQLQuery("INSERT", $table, $columns, $data);
      $query->setConnection($this->connection);
      return $query;
   }
   
   
   /**
   :Function: getMultipleInsertQuery()
   
   :Description:
   This function is just like getInsertQuery(), except for the $data argument.
   Here, $data must be an array of hashes. Each hash will be concatenated into
   one long multiple insert statement.
   
   :Parameters:
   string $table - the name of the table.
   array $data - an array of hashes. The hashes must all be formated the same
      way, with column names as keys an property values as values.
   
   :Return Value:
   gcMySQLQuery - a query object of type INSERT.
   
   :Notes:
   **/
   function getMultipleInsertQuery($table, $data)
   {
      $columns = array_keys($data[0]);
      $query = new gcMySQLQuery("INSERT", $table, $columns, $data);
      $query->setConnection($this->connection);
      $query->multipleInsert = true;
      return $query;
   }
   
   /**
   :Function: getUpdateQuery()
   
   :Description:
   This method takes its arguments and builds a mysql update query from
   them. It then returns the query object.
   
   :Parameters:
   string $table - the name of the table.
   hash $data - a hash with column names as keys an property values as values.
   hash $where - a hash with column names as keys and field values as values 
      to be used to create a simple where clause for the update. Multiple
      entries in this hash will be concatenated with " AND ". 
      
   :Return Value:
   gcMySQLQuery - a query object of type INSERT.
   
   :Notes:
   If you want something more sophisticated for your where clause(s), you can
   add additional where clauses on the returned query object. But you MUST send
   at least one where clause for this function strictly as a safety precaution.
   **/
   function getUpdateQuery($table, $data, $where)
   {
      if (!$where)
      {
         $this->msgCenter->addDebug("Cannot create SQL Update with no WHERE criteria!");
         return false;
      }
      
      $columns = array_keys($data);
      $query = new gcMySQLQuery("UPDATE", $table, $columns, $data);
      $query->setConnection($this->connection);
      foreach ($where as $columnName => $columnValue)
      {
         $query->addWhereClause($columnName, $columnValue, "=", $table);
      }
      
      return $query;
   }
   
   
   /**
   :Function: getDeleteQuery()
   
   :Description:
   This function takes a gcMySQLQuery object, converts it to a string, and
   attempts to run that string as a mysql delete query.
   
   :Parameters:
   string $table - the name of the table.
   hash $where - a hash with column names as keys and field values as values 
      to be used to create a simple where clause for the update. Multiple
      entries in this hash will be concatenated with " AND ".
   
   :Return Value:
   gcMySQLQuery - a query object of type INSERT.
   
   :Notes:
   **/
   function getDeleteQuery($table, $where="")
   {
      if (!$where)
      {
         $this->msgCenter->addDebug("Cannot create SQL delete with no WHERE criteria!");
         return false;
      }
      
      $query = new gcMySQLQuery("DELETE", $table, array(), $data);
      $query->setConnection($this->connection);
      
      if (is_array($where))
      {
         foreach ($where as $columnName => $columnValue)
         {
            $query->addWhereClause($columnName, $columnValue, "=", $table);
         }
      }
      return $query;
   }
      
   
   /**
   :Function: getQuery()
   
   :Description:
   Takes a query type as its argument and returns a query object of that type.
   
   :Parameters:
   string $type - the type of query to be returned. Acceptable values:
      insert
      update
      delete
   hash $data - an optional array of data to build the query with.
   
   :Return Value:
   gcMysqlQuery - a mysql query object.
   
   :Notes:
   **/
   function &getQuery($type, $tableName, $data="", $where="")
   {
      if ($data == "")
      {
         $data = array();
      }
      
      if ($where == "")
      {
         $where = array();
      }
      
      switch ($type)
      {
         case "insert":
            $query = $this->getInsertQuery($tableName, $data);
         break;
         
         case "update":
            $query = $this->getUpdateQuery($tableName, $data, $where);
         break;
         
         case "delete":
            $query = $this->getDeleteQuery($tableName, $where);
         break;
      }
      
      return $query;
   }
   
   
   /**
   :Function: getDBConnection()
   
   :Description:
   Connects to the MySQL database and returns the connection link. Displays or
   logs error messages if connection fails. After you run this method, you're
   ready to execute queries.
   
   :Parameters:
   None.
   
   :Return Value:
   resource - a mysql connection link.
   
   :Notes:
   **/
   function &getDBConnection()
   {
      if (!$this->connection)
      {
         $this->connection = mysqli_init();
         if (!$this->connection)
         {
            $this->msgCenter->addDebug("mysqli_init() has failed.");
            return null;
         }
         
         if (!$this->connection->real_connect($this->hostname, $this->username, $this->password, $this->databaseName))
         {
            $this->msgCenter->addDebug("Could not connect to database");
            $this->msgCenter->addDebug("Mysql Error: " . mysqli_connect_errno() . "\n<br>\n" . mysqli_connect_error());
            return null;
         }
      }
      
      return $this->connection;
   }
   
   
   /**
   :Function: runQuery()
   
   :Description:
   This function takes a gcMySQLQuery object, converts it to a string, and
   attempts to run that string as a mysql query.
   
   :Parameters:
   string $query - an SQL statment that has not been "cleaned".
   
   :Return Value:
   mixed - for select statements, a mysql resource to the result table. For
      inserts, updates and deletes etc. it return boolean true on success or
      false on failure.
   
   :Notes:
   **/
   function runQuery($queryObj)
   {
      //debug_print_backtrace();
      if (!$this->connection)
      {
         $connection = $this->getDBConnection();
         
         if (!$connection)
         {
            $this->msgCenter->addError("Could not connect to DB.");
            return false;
         }
      }
      
      if (!$queryObj->connection)
      {
         $queryObj->setConnection($connection);
      }
      
      // queryToString() will run mysql escape functions.
      $queryAsString = $queryObj->queryToString();
      $result = false;
      
      if ($this->debug)
      {
         print("<p><b>Did NOT RUN SQL Query:</b><br>$queryAsString</p>");
         return false;
      }
      
      if ($this->testMode != false)
      {
         print("<p><b>SQL Query:</b><br>$queryAsString</p>");
      }
      
      $result = $this->connection->real_query($queryAsString);
      
      if (!$result)
      {
         $errorMsg = $this->connection->errno . ": " . $this->connection->error . "<br>$queryAsString";
         $this->msgCenter->addError($errorMsg);
         $this->msgCenter->addError("DB Query Failed. Please try again later.");
         return false;
      }
      
      if ($this->storeResults)
      {
         array_unshift($this->results, $this->connection->store_result());
      }
      else
      {
         array_unshift($this->results, $this->connection->use_result());
      }
      
      $queryType = $queryObj->type;
      if ($queryType == "SELECT" || $queryType == "DESCRIBE")
      {
         return $this->results[0];
      }
      else
      {
         if ($this->connection->error == "")
         {
            return true;
         }
         else
         {
            return false;
         }
      }
   }
   
   
   /**
   :Function: close()
   
   :Description:
   This function takes a mysqli_result object and closes it, freeing up the
   memory it was using.
   
   :Parameters:
   mysqli_result $result - a mysqli result set object.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function close_result($result="")
   {
      if ($result === "")
      {
         $result = $this->results[0];
      }
      
      if ($result instanceof mysqli_result) 
      {
         $result->free();
      }
   }
   
   
   /**
   :Function: closeAll()
   
   :Description:
   This is a cleanup function to be called at the end of all pages. It makes
   sure all mysql results are freed up.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   Calling stored procedures can produce TWO result sets, and if both result
   sets aren't freed up, you may get errors like "Commands out of sync; you 
   can?t run this command now". This method should address that by looping 
   through all the result sets and closing each one.
   
   NOTE: free() and close() do exactly the same things.
   **/
   function closeAll()
   {
      while ($this->connection->next_result()) 
      {
         $result = $mysqli->use_result();
         if ($result instanceof mysqli_result) 
         {
            $result->free();
         }
      }
   }
}



/**
gcMySQLQuery class (class for building queries).

This class is intended to represents a single query as an object with various
properties. When all of those properties have the necessary values, the
query object can be rendered as text. Changes to the query object's properties
could result in a new string.
**/
class gcMySQLQuery
{
   public $type = "";
   public $tables = array();
   public $columns = array();
   public $data = array();
   public $whereClauses = array();
   public $joins = array();
   public $orderBy = array();
   public $limit = "";
   public $offset = "";
   public $validQueryTypes = array("SELECT", "INSERT", "UPDATE", "DELETE");
   public $connection = false;
   public $encryptedFields = array("password");
   public $multipleInsert = false;
   public $unquotedColumnValues = array();
   
   
   /**
   :Function: gcMySQLQuery()
   
   :Description:
   Constructor for this class.
   
   :Parameters:
   string $type - the type of query, either select, insert, update or delete
   mixed $tables - either an array of table names or a single table name as a 
      string.
   mixed $columns - either an array of column names for the query or a single
      column name as a string.
   hash $data - a hash of data for an update or insert, with column names as
      the keys and new values as the values in the hash.
      
   :Return Value:
   gcMySQLQuery - an instance of this object.
   
   :Notes:
   **/
   function gcMySQLQuery($type, $tables, $columns, $data="")
   {
      $this->msgCenter = gcMessageCenter::Singleton();
      $this->setQueryType($type);
      $this->setTables($tables);
      $this->setColumns($columns);
      $this->setData($data);
   }
   
   
   /**
   :Function: setConnection()
   
   :Description:
   Sets the connection object for this query. Queries only need connection
   objects when they try to run real_escape_string(). That's most queries :)
   
   :Parameters:
   mysqli $conn - a connection to the mysql database.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setConnection(&$conn)
   {
      $this->connection =& $conn;
   }
   
   
   /**
   :Function: setQueryType()
   
   :Description:
   Sets the type of query. The type must be a valid query type.
   
   :Parameters:
   string $type - the type of the query.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setQueryType($type)
   {
      if (in_array(strtoupper($type), $this->validQueryTypes))
      {
         $this->type = $type;
      }
      else
      {
         $this->msgCenter->addError("'$type' is not a valid sql query type.");
      }
   }
   
   
   /**
   :Function: getQueryType()
   
   :Description:
   Returns the type of this query.
   
   :Parameters:
   None
   
   :Return Value:
   string - the type of this query.
   
   :Notes:
   **/
   function getQueryType()
   {
      return $this->queryType;
   }
   
   
   /**
   :Function: setTables()
   
   :Description:
   Sets the list of tables this query will be applied to.
   
   :Parameters:
   mixed - either an array of table names or a single table name as a string.
      If the string is passed in, it will be recast as an array with the table
      name as element 0.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setTables($tables)
   {
      if (!is_array($tables))
      {
         $tables = array($tables);
      }
      
      $this->tables = $tables;
   }
   
   
   /**
   :Function: addTable()
   
   :Description:
   Adds a new table name to the list of table names this query will be run 
   against.
   
   :Parameters:
   string $tableName - the name of the table to add.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function addTable($tableName)
   {
      if (!in_array($tableName, $this->tables))
      {
         $this->tables[] = $tableName;
      }
   }
   
   
   /**
   :Function: tablesToString()
   
   :Description:
   Concatenates all of the table names in $this->tables with a ", ". This 
   provides a list of table names that will be valid for sql.
   
   :Parameters:
   None.
   
   :Return Value:
   string - the list of table names as a comma delimited string.
   
   :Notes:
   **/
   function tablesToString()
   {
      return join(", ", $this->tables);
   }
   
   
   /**
   :Function: setColumns()
   
   :Description:
   Takes an array of column names and stores in array. If passed a single column
   name as a string, the name is recast as an array with element 0 being the
   column name.
   
   Also takes an optional table name. If no table name is provided, the first
   entry in $this->tables will be used.
   
   Column names are stored like this:
   
   $this->columns[$tableName] = $columnName.
   
   So all column names are associated with a table. This allows queries that
   join from multiple table.
   
   :Parameters:
   mixed $columnNames - either an array of column names or a single column name
      as a sting.
   string $tableName - the name of the table the $columnNames belong to.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setColumns($columns, $tableName="")
   {
      if (!is_array($columns))
      {
         $columns = array($columns);
      }
      
      if ($tableName === "")
      {
         if (count($this->tables) > 0)
         {
            $tableName = $this->tables[0];
         }
         else
         {
            $queryType = $this->type;
            $this->msgCenter->addError("You have to set a table for this $queryType query.");
         }
      }
      
      if (in_array($tableName, $this->tables) || $tableName === " ")
      {
         $this->columns[$tableName] = $columns;
      }
      else
      {
         $this->msgCenter->addError("You have to add $tableName to the list of tables.");
      }
   }
   
   
   /**
   :Function: unquoteColumnValue()
   
   :Description:
   Sets a column's value to not be qutoed when the query is assembled. This 
   can be useful for things like calling mysql built-in functions like 
   NOW(), which you might want to insert into a datestamp field. It can also
   be dangerous, so don't let columns that accept user-submitted info be
   unquoted.
   
   :Parameters:
   string $columnName - the name of the column to add to the list of columns
      whose values will not be quoted.
      
   :Return Value:
   None.
   
   :Notes:
   **/
   function unquoteColumnValue($columnName)
   {
      $this->unquotedColumnValues[] = $columnName;
   }
   
   
   
   /**
   :Function: quoteValue()
   
   :Description:
   Determines whether or not to quote a value for a column when assembling the
   sql statement.
   
   :Parameters:
   string $columnName - the name of the column to add to the list of columns
      whose values will not be quoted.
      
   :Return Value:
   boolean - true if the value is NOT in the list of unquoted columns. False
      if the value should NOT have quotes around it in the sql statement.
   
   :Notes:
   **/
   function quoteValue($columnName)
   {
      return !in_array($columnName, $this->unquotedColumnValues);
   }
   
   
   /**
   :Function: addColumn()
   
   :Description:
   Adds a column to the list of columns for a particular table.
   
   :Parameters:
   string $columnName - the name of the column to add.
   string $tableName - the name of the table to that $columnName belongs to.
      Default value is the first table in the tables array.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function addColumn($columnName, $tableName="")
   {
      if (!$tableName)
      {
         $tableName = $this->tables[0];
      }
      $this->columns[$tableName][] = $columnName;
   }
   
   
   /**
   :Function: addColumns()
   
   :Description:
   Adds an array of column names to the list of columns for a particular table.
   
   :Parameters:
   array $columns - an array of column names in a particular table.
   string $tableName - the name of the table to that $columns belong to.
      Default value is the first table in the tables array.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function addColumns($columns, $tableName="")
   {
      if (!$tableName)
      {
         $tableName = $this->tables[0];
      }
      
      if (!is_array($this->columns[$tableName]))
      {
         $this->columns[$tableName] = array();
      }
      
      $this->columns[$tableName] = array_unique(array_merge($this->columns[$tableName], $columns));
   }
   
   
   /**
   :Function: columnsToString()
   
   :Description:
   Returns a concatented list of all columns prepended by their table names so
   queries that join across multiple tables will know which columns go with
   which tables.
   
   :Parameters:
   None
   
   :Return Value:
   string - a concatenated, comma-delimited list of table names.
   
   :Notes:
   **/
   function columnsToString()
   {
      $allColumns = array();
      foreach ($this->columns as $tableName => $columnList)
      {
         foreach ($columnList as $columnName)
         {
            if ($columnName == "*")
            {
               if (count($columnList) > 1)
               {
                  continue;
               }
            }
            
            if (trim($tableName) != "")
            {
               $allColumns[] = $tableName . "." . $columnName;
            }
            else
            {
               $allColumns[] = $columnName;
            }
         }
      }
      
      return join(", ", $allColumns);
   }
   
   
   /**
   :Function: setData()
   
   :Description:
   Sets the data property of this query with a hash of column names and values
   to use.
   
   :Parameters:
   hash - a hash of column names and values to assign to those fields.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setData($dataHash)
   {
      $this->data = $dataHash;
   }
   
   
   /**
   :Function: dataToString()
   
   :Description:
   Concatenates all of the data into a comma delimited string. It also takes
   every non-numeric value and runs mysql_real_escape_string() on that value,
   and then encloses it in single quotes. Typically, these values (if they are
   user submitted) will have already been run through importFromPost() and so
   they'll have their tags stripped and most punctuation changed to html
   entities. It's up to applications to make sure the necessary validation is
   done, but this method makes sure that any nasty sql injection strings are
   not run.
   
   :Parameters:
   None
   
   :Return Value:
   string - concatenated, comma-delimited string of all data values.
   
   :Notes:
   See dataToSetStmt() for creating set statements.
   **/
   function dataToString()
   {
      $dataArray = array();
      foreach ($this->data as $columnName=>$value)
      {
         if (is_numeric($value))
         {
            $dataArray[] = $value;
         }
         else
         {
            if (in_array($columnName, $this->encryptedFields))
            {
               $dataArray[] = "password('" . $this->connection->real_escape_string($value) . "')";
            }
            else if ($this->quoteValue($columnName))
            {
               $dataArray[] = "'" . $this->connection->real_escape_string($value) . "'";
            }
            else
            {
               $dataArray[] = $this->connection->real_escape_string($value);
            }
         }
      }
      $dataString = join(", ", $dataArray);
      return $dataString;
   }
   
   
   /**
   :Function: dataToSetStmt()
   
   :Description:
   Concatenates all of the column names and data into a comma delimited string.
   It also takes
   every non-numeric value and runs mysql_real_escape_string() on that value,
   and then encloses it in single quotes. Typically, these values (if they are
   user submitted) will have already been run through importFromPost() and so
   they'll have their tags stripped and most punctuation changed to html
   entities. It's up to applications to make sure the necessary validation is
   done, but this method makes sure that any nasty sql injection strings are
   not run.
   
   :Parameters:
   None
   
   :Return Value:
   string - concatenated, comma-delimited string of all data values and column
      names.
   
   :Notes:
   **/
   function dataToSetStmt()
   {
      $dataArray = array();
      foreach ($this->data as $columnName=>$value)
      {
         if (is_numeric($value))
         {
            $dataArray[] = "$columnName = $value";
         }
         else
         {
            if (in_array($columnName, $this->encryptedFields))
            {
               $dataArray[] = "$columnName = password('" . $this->connection->real_escape_string($value) . "')";
            }
            else
            {
               $dataArray[] = "$columnName = '" . $this->connection->real_escape_string($value) . "'";
            }
         }
      }
      $dataString = " SET " . join(", ", $dataArray);
      return $dataString;
   }
   
   
   /**
   :Function: setOrderByClauses()
   
   :Description:
   Takes an array of column names like this:
   
   array('table1.column1 DESC', 'table2.column2 ASC') 
   
   and stores that array as a property of this query object.
   
   :Parameters:
   array $columnNames - an array of column names and "ASC" or "DESC" to order
      the query results by. Column names should have their table name prepeneded
      to them.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setOrderByClauses($columns)
   {
      if (func_num_args() == 1)
      {
         if (!is_array($columns))
         {
            $columns = array($columns);
         }
      }
      else if (func_num_args() > 1)
      {
         $args = func_get_args();
         $columns = array();
         foreach ($args as $arg)
         {
            $columns[] = $arg;
         }
      }
      $this->orderBy = $columns;
   }
   
   
   /**
   :Function: addOrderByClause()
   
   :Description:
   Adds an order by clauses to this query's list of order by claues. The clause
   should be formatted like this:
   'table1.column1 DESC' 
   
   :Parameters:
   string $columnName - a table column name with "ASC" or "DESC" to order
      the query results by. Column names should have their table name prepeneded
      to them.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function addOrderByClause($column)
   {
      $this->orderBy[] = $column;
   }
   
   
   /**
   :Function: orderByToString()
   
   :Description:
   Takes all of the order by column names and concatenates them into an order by
   clause for this query.
   
   :Parameters:
   None.
   
   :Return Value:
   string - the order by clauses concatenated together.
   
   :Notes:
   **/   
   function orderByToString()
   {
      if ($this->orderBy)
      {
         return " ORDER BY " . join(", ", $this->orderBy);
      }
      else
      {
         return "";
      }
   }
   
   
   /**
   :Function: addWhereClause()
   
   :Description:
   Takes a column name and a value and creates a where clause. It can also
   take optional args, $operator (which can be any valid sql operator symbol, 
   such as < or 'LIKE') and a table name to prepend to the column name.
   
   :Parameters:
   string $columnName - the name of the column you want to compare against in
      the where clause.
   mixed $value - a scalar value to check in the where clause.
   string $operator - an operator for comparison in the where clause.
   string $table - the table name to prepend to the column name. If not given,
      the first table in the list of tables will be used.
   
   :Return Value:
   reference gcMySQLWhereClause - a where clause object.
   
   :Notes:
   **/   
   function &addWhereClause($columnName, $value, $operator="=", $table="")
   {
      if ($table === "")
      {
         $table = $this->tables[0];
      }
      
      $clauseObj =& new gcMySQLWhereClause($columnName, $operator, $value, $table);
      
      $clauseObj->setConnection($this->connection);
      $this->whereClauses[] = $clauseObj;
      return $clauseObj;
   }
   
   
   /**
   :Function: addInnerJoin()
   
   :Description:
   This method takes two column names and an opertor and adds a where clause
   to our query that performs an inner join (a join between two tables based on
   foreign keys shared by those two tables.
   
   NOTE: the two column names need to have their table name pre-pended to them
   or your join may not work properly.
   
   You can also pass in a different operator, like ">" or "LIKE". 
   
   :Parameters:
   string $column1 - The first column name in your join clause, with the pre-
      pended table name.
   string $column2 - the second column name in your join clause, with the pre-
      pended table name.
   string $operator - the comparision operator for your join.
   
   :Return Value:
   reference gcMySQLWhereClause - a where clause object.
   
   :Notes:
   This function will set the quoteValues property of the resulting where clause
   to FALSE, which means no escaping is done. So DON'T use this method with 
   user-supplied column names! Table column names should never be exposed to the
   user, but be aware that if your column names aren't coming from a trusted 
   source they won't be scrubbed like other data.
   **/
   function &addInnerJoin($column1, $column2, $operator="=")
   {
      $clauseObj =& new gcMySQLWhereClause($column1, $operator, $column2);
      $clauseObj->noQuoteValues();
      $this->whereClauses[] =& $clauseObj;
      return $clauseObj;
   }
   
   
   function &addJoin($type, $table, $criteria="")
   {
      $clauseObj =& new gcMySQLJoinClause($type, $table, $criteria);
      $this->joins[] =& $clauseObj;
      return $clauseObj;
   }
   
   
   /**
   :Function: setWhereClauses()
   
   :Description:
   Takes an array and loops through that array to populate the whereClauses
   property of this object.
   
   The passed in array can be an array of where clause objects. These will just
   be appeneded to the whereClauses property.
   
   The passed in array can also be an array of arrays of strings. The nested 
   arrays need to be formatted like the args for addWhereClause():
   $columnName, $value, $operator, $tableName.
   
   :Parameters:
   array $clauses - either an array of whereClause objects or an array of arrays
      of strings.
   
   :Return Value:
   None.
   
   :Notes:
   This function over-writes the whereClauses property of this object.
   **/
   function setWhereClauses($clauses)
   {
      $this->whereClauses = array();
      foreach ($clauses as $clause)
      {
         if (is_a($clause, "gcMySQLWhereClause"))
         {
            $this->whereClauses[] = $clause;
         }
         else if (is_array($clause))
         {
            $this->whereClauses[] = $this->addWhereClause($clause[0], $clause[1], $clause[2], $clause[3]);
         }
         else
         {
            $this->msgCenter->addError("Invalid format for where clause: $clause");
         }
      }
   }
   
   
   /**
   :Function: clearWhereClauses()
   
   :Description:
   Deletes all where clauses by replacing the whereClauses property with an
   empty array.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function clearWhereClauses()
   {
      $this->whereClauses = array();
   }
   
   
   /**
   :Function: addLmitClause()
   
   :Description:
   Adds a limit clause set to the passed in value.
   
   :Parameters:
   int $limit - the number of rows the query should be limted to.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function addLimitClause($limit)
   {
      $this->limit = $limit;
   }
   
   
   function limitToString()
   {
      if ($this->limit != "")
      {
         return " LIMIT " . $this->limit . " ";
      }
   }
   
   
   /**
   :Function: addOffsetClause()
   
   :Description:
   Adds an offset clause to the query, indicating where in the result set
   the query should start returning rows from.
   
   :Parameters:
   int $offset - the number of rows the query should skip.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function addOffsetClause($offset)
   {
      $this->offset = $offset;
   }
   
   
   function offsetToString()
   {
      if ($this->offset != "")
      {
         return " OFFSET " . $this->offset . " ";
      }
      else
      {
         return "";
      }
   }
   
   
   /**
   :Function: getWhereClauseByIndex()
   
   :Description:
   Returns a particular where clause by its index in the array of whereClauses.
   
   :Parameters:
   int $index - the index of the where clause you want.
   
   :Return Value:
   reference gcMySQLWhereClause - a where clause object.
   
   :Notes:
   **/
   function &getWhereClauseByIndex($index)
   {
      return $this->whereClauses[$index];
   }
      
   
   /**
   :Function: selectQueryToString()
   
   :Description:
   If this query is a select query, this method will return a string
   representation of this query, including its where clauses.
   
   :Parameters:
   None
   
   :Return Value:
   string - the entire query as a string.
   
   :Notes:
   **/
   function selectQueryToString()
   {
      $query = strtoupper($this->type) . " ";
      $query .= $this->columnsToString();
      $query .= " FROM " . $this->tablesToString();
      $query .= $this->assembleJoins();
      $query .= $this->assembleWhereClauses();
      $query .= $this->orderByToString();
      $query .= $this->limitToString();
      $query .= $this->offsetToString();
      return $query;
   }
   
   
   /**
   :Function: insertQueryToString()
   
   :Description:
   If this query is an insert query, this method will return a string
   representation of this query.
   
   :Parameters:
   None
   
   :Return Value:
   string - the entire query as a string.
   
   :Notes:
   **/
   function insertQueryToString()
   {
      $query = strtoupper($this->type) . " INTO ";
      $query .= $this->tablesToString();
      $query .= " (" . $this->columnsToString() . ") ";
      $query .= " VALUES";
      if ($this->multipleInsert)
      {
         $allRows = $this->data;
         $allRowsAsString = array();
         foreach ($allRows as $row)
         {
            $this->data = $row;
            $allRowsAsString[] = "(" . $this->dataToString() . ")";
         }
         $query .= join(", ", $allRowsAsString);
      }
      else
      {
         $query .= " (" . $this->dataToString() . ")";
      }
      return $query;
   }
   
   
   /**
   :Function: updateQueryToString()
   
   :Description:
   If this query is an update query, this method will return a string
   representation of this query, including its where clauses.
   
   :Parameters:
   None
   
   :Return Value:
   string - the entire query as a string.
   
   :Notes:
   **/
   function updateQueryToString()
   {
      $query = strtoupper($this->type) . " ";
      $query .= $this->tablesToString();
      $query .= " " . $this->dataToSetStmt();
      $query .= $this->assembleWhereClauses();
      return $query;
   }
   
   
   /**
   :Function: deleteQueryToString()
   
   :Description:
   If this query is a delete query, this method will return a string
   representation of this query, including its where clauses.
   
   :Parameters:
   None
   
   :Return Value:
   string - the entire query as a string.
   
   :Notes:
   **/
   function deleteQueryToString()
   {
      $query = strtoupper($this->type) . " FROM ";
      $query .= $this->tablesToString();
      $query .= $this->assembleWhereClauses();
      return $query;
   }
   
   
   /**
   :Function: queryToString()
   
   :Description:
   Returns this query object as a string to be passed to mysql.
   
   :Parameters:
   None
   
   :Return Value:
   string - this query object as an sql query.
   
   :Notes:
   **/
   function queryToString()
   {
      switch (strtoupper($this->type))
      {
         case "SELECT":
            $string = $this->selectQueryToString();
         break;
      
         case "INSERT":
            $string = $this->insertQueryToString();
         break;
      
         case "UPDATE":
            $string = $this->updateQueryToString();
         break;
      
         case "DELETE":
            $string = $this->deleteQueryToString();
         break;
      }
      
      return $string;
   }
   
   
   /**
   :Function: assembleWhereClauses()
   
   :Description:
   Loops through each where clause this object has and creates string 
   representations of those clauses. The final product should be a complete
   where clause.
   
   :Parameters:
   None
   
   :Return Value:
   string - all of the where clauses of this query as a string.
   
   :Notes:
   **/
   function assembleWhereClauses()
   {
      $assembledClauses = " ";
      
      if ($this->whereClauses)
      {
         $assembledClauses .= " WHERE ";
         
         $clauseCount = count($this->whereClauses);
         
         for ($i = 0; $i < $clauseCount; $i++)
         {
            $clause = $this->whereClauses[$i];
            $assembledClauses .= $clause->buildClause();
            
            if ($i < $clauseCount - 1)
            {
               $assembledClauses .= $clause->getConjunction();
            }
         }
      }
      return $assembledClauses;
   }
   
   
   function assembleJoins()
   {
      $assembledClauses = "";
      if ($this->joins)
      {
         foreach ($this->joins as $join)
         {
            $assembledClauses .= $join->buildClause();
         }
      }
      return $assembledClauses;
   }
}

class gcMySQLJoinClause
{
   public $type;
   public $table;
   public $criteria = array();
   
   function gcMySQLJoinClause($type, $table, $criteria="")
   {
      $this->type = $type;
      $this->table = $table;
      if ($criteria)
      {
         if (is_string($criteria))
         {
            $this->addCriterium($criterium);
         }
         
         if (is_array($criteria))
         {
            foreach ($criteria as $criterium)
            {
               $this->addCriterium($criterium);
            }
         }
      }
   }
   
   
   function addCriterium($criterium)
   {
      $this->criteria[] = $criterium;
   }
   
   
   function buildClause()
   {
      return " $this->type $this->table ON " . join(" AND ", $this->criteria);
   }
}


/**
class gcMySQLWhereClause (class for making where clauses)

Where clauses in sql seemed complicated and important enough to warrent their
own class. Ultimately, this class should allow for compound where clauses across
any number of tables. Right now, only relatively simple where statements are
supported.
**/
class gcMySQLWhereClause
{
   public $columnName = "";
   public $operator = "=";
   public $value = "";
   public $tableName = "";
   public $conjunction = " AND ";
   private $quoteValues = true;
   public $encryptedFields = array("password");
   
   function gcMySQLWhereClause($columnName, $operator, $value, $tableName="")
   {
      $this->columnName = strval($columnName);
      $this->operator = strval($operator);
      $this->value = $value;
      $this->tableName = $tableName;
   }
   
   
   /**
   :Function: setConnection()
   
   :Description:
   Sets the connection object for this query. Queries only need connection
   objects when they try to run real_escape_string(). That's most queries :)
   
   :Parameters:
   mysqli $conn - a connection to the mysql database.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setConnection(&$conn)
   {
      $this->connection =& $conn;
   }
   
   
   function noQuoteValues()
   {
      $this->quoteValues = false;
   }
   
   
   function quoteValues()
   {
      $this->quoteValues = true;
   }
   
   
   function changeConjunction($conjunction)
   {
      $this->conjunction = strtoupper($conjunction);
   }
   
   
   function getConjunction()
   {
      return $this->conjunction;
   }
   
   
   function changeColumn($columnName)
   {
      $this->columnName = $columnName;
   }
   
   
   function changeOperator($operator)
   {
      $this->$operator;
   }
   
   
   function changeValue($value)
   {
      $this->value = $value;
   }
   
   
   function buildClause()
   {
      $clause = "";
      if ($this->tableName)
      {
         $clause .= $this->tableName . ".";
      }
      
      $clause .= $this->columnName . " " . $this->operator . " ";
      
      if (is_numeric($this->value))
      {
         $clause .= $this->value;
      }
      else
      {
         if ($this->quoteValues)
         {
            if (in_array($this->columnName, $this->encryptedFields))
            {
               $clause .= "password('" . $this->connection->real_escape_string($this->value) . "')";
            }
            else
            {
               $clause .= "'" . $this->connection->real_escape_string($this->value) . "'";
            }
         }
         else
         {
            $clause .= " $this->value";
         }
      }
      return $clause;
   }
}

   
   
   /**
   :Function: 
   
   :Description:
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
?>
