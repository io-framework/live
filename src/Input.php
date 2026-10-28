<?php

namespace Io\Live;

use Attribute;

/**
 * A public property the page sets when it places the component: `$this->live('app-add-to-cart', ['product' => 3])`.
 * The value comes back from the browser when it connects, so treat it like a query parameter, never as proof
 * of who the user is. Properties without #[Input] cannot be set from outside.
 *
 * `#[Input(query: true)]` takes the page's query parameter of the same name when the tag does not set it
 * (`/search?q=pretzel` for `public string $q`), `query: 'status'` one of another name: converted to the
 * property's type as a form's text is ("2" for an int, "on" for a bool), left at the default when it does not fit.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Input
{
    public function __construct(public bool|string $query = false)
    {
    }
}
