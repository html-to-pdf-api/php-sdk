<?php

namespace HtmlToPdfApi\Exceptions;

/**
 * 429 or quota-exceeded 4xx — your account has hit its plan limit.
 * Wait for the reset window or upgrade. Avoid blind retries.
 */
class UsageLimitException extends HtmlToPdfException {}
