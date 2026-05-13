<?php

namespace HtmlToPdfApi\Exceptions;

/**
 * 401 — the API key is missing, malformed, or rejected by the API.
 * Don't retry; surface the error and let the operator rotate the key.
 */
class AuthenticationException extends HtmlToPdfException {}
