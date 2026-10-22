<?php

namespace Magrathea2\Exceptions;
use Magrathea2\Exceptions\MagratheaDBException;

/**
* Thrown when the MySQL connection is gone (errno 2006, 2013 or 4031).
* The code is the MySQL errno:
* 	2006 (server has gone away): the statement was probably not executed;
* 	2013 (lost connection during query): the statement may have been executed;
* 	4031 (disconnected by the server for inactivity, MySQL 8.0.24+): the statement was not executed.
* The framework never retries: only the app knows if a statement is safe to repeat.
* The next query on the same Database instance opens a new connection.
*/
class MagratheaDBConnectionLostException extends MagratheaDBException {
}
