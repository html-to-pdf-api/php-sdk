<?php

namespace HtmlToPdfApi\Exceptions;

use RuntimeException;

/**
 * Base class for every SDK-thrown exception. Catch this to handle any
 * SDK error uniformly; catch a subclass to handle a specific failure mode.
 */
class HtmlToPdfException extends RuntimeException {}
