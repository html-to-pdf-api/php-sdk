<?php

namespace HtmlToPdfApi\Exceptions;

/**
 * 5xx — the API server failed. Retrying with backoff is appropriate.
 */
class ServerException extends HtmlToPdfException {}
