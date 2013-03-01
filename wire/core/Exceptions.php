<?php

/**
 * ProcessWire Exceptions
 *
 * Exceptions that aren't specific to a particular class. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
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

