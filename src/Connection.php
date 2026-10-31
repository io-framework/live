<?php

namespace Io\Live;

use InvalidArgumentException;
use Io\Http\{Redirect, Request};

/**
 * The browser connection of a circuit, for a component or a #[Scoped] service that injects it: the request the
 * WebSocket came in with (its cookies, and $request->principal as the app's middlewares left it), and a way to send
 * the browser to another page. After a reconnect, $request is the new connection's. $page is the page the
 * component is on, for a link back to it.
 */
#[Scoped]
final class Connection
{
    /** The longest page address the browser may report; a longer one keeps the last */
    private const MAX_PAGE = 2048;

    private ?string $navigation = null;

    /**
     * The page's path and query as the browser's address shows it ("/films?tag[]=action"), for "sign in and come
     * back" links: `'/login?next=' . rawurlencode($this->connection->page)`. During the page's first render it is
     * the page request's; once connected the browser reports it, and again whenever #[Input(query: ...)]
     * properties change the address. Always a path on this site; "/" until the browser said where it is.
     */
    public private(set) string $page = '/';

    /**
     * @internal The circuit creates it
     *
     * @param Request $request The request the WebSocket came in with, or the page's during its first render
     * @param string|null $page Where the browser is, as it reported it; null takes $request's own path and query
     */
    public function __construct(public private(set) Request $request, ?string $page = null)
    {
        if ($page === null) {
            $this->page = $request->url();
            return;
        }
        $this->moved($page);
    }

    /**
     * After this action, the browser loads $url: a path on this site ("/admin"). It counts even if the action
     * throws afterwards, so a check can send a logged-out user to the login and stop the action. Not during the
     * page's first render, only from actions and timers.
     */
    public function navigate(string $url): void
    {
        // A path of our own, never another site: "//evil.test" or "javascript:" would leave it, and so would
        // "/\t/evil.test", since browsers drop tabs and line breaks from a URL
        if (!Redirect::isLocal($url)) {
            throw new InvalidArgumentException("navigate() takes a path on this site, not '$url'");
        }
        $this->navigation = $url;
    }

    /** @internal The circuit came back on a new connection, $page as its browser reported it. */
    public function reconnected(Request $request, mixed $page = null): void
    {
        $this->request = $request;
        $this->moved($page);
    }

    /**
     * @internal The browser reports its address: a path on this site, else it is ignored. It comes from the
     * browser, so it is input like a query parameter.
     */
    public function moved(mixed $page): void
    {
        if (is_string($page) && strlen($page) <= self::MAX_PAGE && Redirect::isLocal($page)) {
            $this->page = $page;
        }
    }

    /** @internal What navigate() asked for, once. */
    public function takeNavigation(): ?string
    {
        [$url, $this->navigation] = [$this->navigation, null];
        return $url;
    }
}
