<?php

namespace HtmlToPdfApi\Exceptions;

/**
 * Network-level failure — DNS, TCP, TLS, or timeout. The API was never
 * reached, so retrying is usually safe.
 */
class ConnectionException extends HtmlToPdfException {}
