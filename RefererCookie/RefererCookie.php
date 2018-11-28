<?php

namespace Medelse\RefererCookieBundle\RefererCookie;

use DateTime;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use UnexpectedValueException;

class RefererCookie
{
    /**
     * Name of cookie where will be saved last  referer.
     *
     * @var string
     */
    private $refererCookieName;

    /**
     * @var array
     */
    private $refererCookie = null;

    /**
     * Lifetime of refererCookie
     *
     * @var int
     */
    private $lifetime;

    /**
     * Path for cookie. Default "/" so not empty like in setcookie PHP function!
     *
     * @var string
     */
    private $path = '/';

    /**
     * Domain for cookie.
     *
     * @var string
     */
    private $domain = '';

    /**
     * If cookie should be secured (same as $secure parameter in setcookie PHP function).
     *
     * @var bool
     */
    private $secure = false;

    /**
     * If cookie should be http only (same as $httponly parameter in setcookie PHP function).
     *
     * @var bool
     */
    private $httpOnly = false;

    /**
     * Track internal referer or only external
     *
     * @var bool
     */
    private $trackInternalReferer = false;

    /**
     * @var array
     * List of domain who match internal referer. wildcard for domain / subdomain is possible
     */
    private $internalDomains = [];

    /**
     * @var array
     * List of domain who match external referer. wildcard for domain / subdomain is possible
     */
    private $externalDomains = [];

    /**
     * Get referer values or just value of referer with specific key. Key can be 'internal' or 'external'
     *
     * @param string|null $key Default null (return all values as array).
     *
     * @return string|array|null Return string value, array or null if not set.
     */
    public function get(string $key = null)
    {
        $this->init();

        if ($key === null) {
            if (!$this->trackInternalReferer) {
                if (array_key_exists('referer_external', $this->refererCookie)) {
                    return $this->refererCookie['referer_external'];
                }
            }
            return $this->refererCookie;
        } else {
            if (mb_strpos($key, 'referer_') !== 0) {
                $key = 'referer_' . $key;
            }
            if (false === array_key_exists($key, $this->refererCookie)) {
                throw new UnexpectedValueException(sprintf('Argument $key has unexpected value "%s". Referer value with key "%s" does not exists.', $key, $key));
            } else {
                return $this->refererCookie[$key];
            }
        }
    }

    /**
     * Initialize. Get values from _GET and _COOKIES and save to refererCookie. Init $this->refererCookie value.
     *
     * @return void
     */
    public function init()
    {
        // if initialized, just return
        if ($this->refererCookie !== null) {
            return;
        }

        $this->initStaticValues();
        // referer from _COOKIE
        $refererCookieFilter = filter_var(
            json_decode(filter_input(INPUT_COOKIE, $this->refererCookieName), true),
            FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY
        );
        if (false === is_array($refererCookieFilter)) {
            $refererCookieFilter = [];
        }
        $refererCookie = $this->removeNullValues($refererCookieFilter);

        $referer = array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : null;

        if (is_null($referer)) {
            $this->refererCookie = array_merge($this->refererCookie, $refererCookie);
            return;
        }

        $refererDomain = parse_url($referer)['host'];

        $isExternal = $isInternal = false;
        foreach ($this->internalDomains as $domain) {
            if (fnmatch($domain, $refererDomain)) {
                $isInternal = true;
                break;
            }
        }
        if (!$isInternal) {
            if (count($this->externalDomains) == 0) {
                $isExternal = true;
            } else {
                foreach ($this->externalDomains as $domain) {
                    if (fnmatch($domain, $refererDomain)) {
                        $isExternal = true;
                        break;
                    }
                }
            }
        }

        if ($isInternal && $this->trackInternalReferer) {
            $refererCookieSave = array_merge($this->refererCookie, $refererCookie, ['referer_internal' => $referer]);
        } elseif ($isExternal) {
            $refererCookieSave = array_merge($this->refererCookie, $refererCookie, ['referer_external' => $referer]);
        } else {
            $this->refererCookie = array_merge($this->refererCookie, $refererCookie);
            return;
        }

        $this->save($refererCookieSave);
    }

    /**
     * onKernelRequest called if autoInit is true
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->init();
    }

    /**
     * Remove refererCookie
     */
    public function clear()
    {
        setcookie($this->refererCookieName, '', -1, $this->path, $this->domain, $this->secure, $this->httpOnly);
    }

    /**
     * Set domain for cookie.
     *
     * @param string $domain
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * If referer match one of this domain, save it as internal referer
     *
     * @param array $internalDomains
     */
    public function setInternalDomains(array $internalDomains)
    {
        $this->internalDomains = $internalDomains;
    }

    /**
     * If referer match one of this domain, save it as external referer
     *
     * @param array $externalDomains
     */
    public function setexternalDomains(array $externalDomains)
    {
        $this->externalDomains = $externalDomains;
    }

    /**
     * Set httponly for cookie.
     *
     * @param bool $httpOnly
     */
    public function setHttpOnly(bool $httpOnly)
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * Set lifetime of referer cookie in seconds
     *
     * @param int $lifetime
     */
    public function setLifetime(int $lifetime)
    {
        if ($lifetime <= 0) {
            throw new UnexpectedValueException(sprintf('Lifetime has unexpected value "%s". Value must be positive.', $lifetime));
        }
        $this->lifetime = $lifetime;
    }

    /**
     * Set name of cookie where will be saved referer params.
     *
     * @param string $refererCookieName
     */
    public function setName(string $refererCookieName)
    {
        if (trim($refererCookieName) == '') {
            throw new UnexpectedValueException(sprintf('Name has unexpected value "%s". Value can\'t be empty.', $refererCookieName));
        }

        $this->refererCookieName = $refererCookieName;
        // cancel previous init
        $this->refererCookie = null;
    }

    /**
     * Set path for cookie.
     *
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Set secure for cookie.
     *
     * @param bool $secure
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;
    }

    /**
     * Set trackInternalReferer.
     *
     * @param bool $trackInternalReferer
     */
    public function setTrackInternalReferer(bool $trackInternalReferer)
    {
        $this->trackInternalReferer = $trackInternalReferer;
    }

    /**
     * Initialize static values to default (or empty) values.
     */
    private function initStaticValues()
    {
        if ($this->trackInternalReferer) {
            $this->refererCookie = [
                'referer_internal' => null,
                'referer_external' => null
            ];
        } else {
            $this->refererCookie = [
                'referer_external' => null
            ];
        }
    }

    /**
     * Remove elements with null values from array.
     *
     * @param array|null $array
     *
     * @return array
     */
    private static function removeNullValues(array $array = null)
    {
        // null (undefined) or false (filter failed)
        if ($array === null || $array === false) {
            return [];
        }

        return array_filter(
            $array,
            function ($value) {
                return $value !== null;
            }
        );
    }

    /**
     * Save refererCookie value into _COOKIE and set actual $this->refererCookie value (call only from init).
     *
     * @param array $refererCookieSave
     */
    private function save(array $refererCookieSave)
    {
        $expire = (new DateTime())->getTimestamp() + $this->lifetime;

        setcookie($this->refererCookieName, json_encode($refererCookieSave), $expire, $this->path, $this->domain, $this->secure, $this->httpOnly);

        $this->refererCookie = $refererCookieSave;
    }
}