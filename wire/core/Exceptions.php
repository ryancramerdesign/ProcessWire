<?php

/**
 * ProcessWire Exceptions
 *
 * Exceptions that aren't specific to a particular class. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 */

/**
 * Generic ProcessWire exception
 *
 */
class WireException extends Exception {}

/**
 * Triggered when access to a resource is not allowed
 *
 */
class WirePermissionException extends WireException {}

/**
 * Triggered when a requested item does not exist and generates a fatal error
 *
 */
class Wire404Exception extends WireException {}

/**
 * WireDatabaseException is the exception thrown by the Database class
 *
 * If you use this class without ProcessWire, change 'extends WireException' below to be just 'extends Exception'
 *
 */
class WireDatabaseException extends WireException {}
