<?php

namespace Io\Live;

use RuntimeException;

/** A function called with Js::invoke() failed in the browser, or no answer came: no connection, or none in time. */
final class JsException extends RuntimeException
{
}
