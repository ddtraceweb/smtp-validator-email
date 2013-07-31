<?php

namespace SmtpValidatorEmail\Domain;

/**
 * Class DomainBag is a container for domains.
 *
 * @author  David DJIAN <david@traceweb.fr>
 *
 * @package SmtpValidatorEmail\Domain
 */
class DomainBag implements \IteratorAggregate, \Countable
{

    /**
     * @var
     */
    protected $domains;

    /**
     * Constructor.
     *
     * @param array $domains An array of domains
     *
     */
    public function __construct(array $domains = array())
    {
        $this->domains = array();
        foreach ($domains as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns the domains as a string.
     *
     * @return string The domains
     */
    public function __toString()
    {
        if (!$this->domains) {
            return '';
        }

        $max     = max(array_map('strlen', array_keys($this->domains))) + 1;
        $content = '';
        ksort($this->domains);
        foreach ($this->domains as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Returns the domains.
     *
     * @return array An array of domains
     *
     */
    public function all()
    {
        return $this->domains;
    }

    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     *
     */
    public function keys()
    {
        return array_keys($this->domains);
    }

    /**
     * Replaces the current domains by a new set.
     *
     * @param array $domains An array of domains
     *
     */
    public function replace(array $domains = array())
    {
        $this->domains = array();
        $this->add($domains);
    }

    /**
     * Adds new domains the current domains set.
     *
     * @param array $domains An array of domains
     *
     */
    public function add(array $domains)
    {
        foreach ($domains as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns a domain value by name.
     *
     * @param string  $key     The domain name
     * @param mixed   $default The default value
     * @param Boolean $first   Whether to return the first value or all domain values
     *
     * @return string|array The first domain value if $first is true, an array of values otherwise
     *
     */
    public function get($key, $default = null, $first = true)
    {

        if (!array_key_exists($key, $this->domains)) {
            if (null === $default) {
                return $first ? null : array();
            }

            return $first ? $default : array($default);
        }

        if ($first) {
            return count($this->domains[$key]) ? $this->domains[$key][0] : $default;
        }

        return $this->domains[$key];
    }

    /**
     * Sets a domain values.
     *
     * @param string       $key     The key
     * @param string|array $values  The value or an array of values
     * @param Boolean      $replace Whether to replace the actual value or not (true by default)
     *
     */
    public function set($key, $values, $replace = true)
    {
        $values = array_values((array)$values);

        if (true === $replace || !isset($this->domains[$key])) {
            $this->domains[$key] = $values;
        } else {

            $this->domains[$key] = array_merge($this->domains[$key], $values);
        }
    }

    /**
     * Returns true if the Domain is defined.
     *
     * @param string $key The Domain
     *
     * @return Boolean true if the parameter exists, false otherwise
     *
     */
    public function has($key)
    {
        return array_key_exists($key, $this->domains);
    }

    /**
     * Returns true if the given Domain contains the given value.
     *
     * @param string $key   The Domain name
     * @param string $value The Domain value
     *
     * @return Boolean true if the value is contained in the domain, false otherwise
     *
     */
    public function contains($key, $value)
    {
        return in_array($value, $this->get($key, null, false));
    }

    /**
     * Removes a domain.
     *
     * @param string $key The Domain name
     *
     */
    public function remove($key)
    {
        unset($this->domains[$key]);
    }

    /**
     * Returns an iterator for domains.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function count()
    {
        return count($this->domains);
    }

    /**
     * Returns the number of domains.
     *
     * @return int The number of domains
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->domains);
    }

}