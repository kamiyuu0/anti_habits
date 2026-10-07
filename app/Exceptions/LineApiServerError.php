<?php

namespace App\Exceptions;

use RuntimeException;

/** LINE API の 5xx / 429 エラー (リトライ対象) */
class LineApiServerError extends RuntimeException {}
