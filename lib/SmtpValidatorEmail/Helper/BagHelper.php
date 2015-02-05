<?php

namespace SmtpValidatorEmail\Helper;

/**
 * Class BagHelper is a container for container.
 *
 * @author  David DJIAN <david@traceweb.fr>
 *
 * @package SmtpValidatorEmail\Email
 */
class BagHelper implements \IteratorAggregate, \Countable
{

    /**
     * @var
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param array $container An array of container
     *
     */
    public function __construct(array $container = array())
    {
        $this->container = array();
        foreach ($container as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns the container as a string.
     *
     * @return string The container
     */
    public function __toString()
    {
        if (!$this->container) {
            return '';
        }

        $max     = max(array_map('strlen', array_keys($this->container))) + 1;
        $content = '';
        ksort($this->container);
        foreach ($this->container as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Returns the container.
     *
     * @return array An array of container
     *
     */
    public function all()
    {
        return $this->container;
    }

    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     *
     */
    public function keys()
    {
        return array_keys($this->container);
    }

    /**
     * Replaces the current container by a new set.
     *
     * @param array $emails An array of container
     *
     */
    public function replace(array $emails = array())
    {
        $this->container = array();
        $this->add($emails);
    }

    /**
     * Adds new container the current container set.
     *
     * @param array $emails An array of container
     *
     */
    public function add(array $emails)
    {
        if(!empty ($emails) ){
            foreach ($emails as $key => $values) {
                $this->set($key, $values);
            }
        }
    }

    /**
     * Returns a email value by name.
     *
     * @param string  $key     The email name
     * @param mixed   $default The default value
     * @param Boolean $first   Whether to return the first value or all email values
     *
     * @return string|array The first email value if $first is true, an array of values otherwise
     *
     */
    public function get($key, $default = null, $first = true)
    {
        if(!is_string($key) && !is_int($key)){
            throw new \InvalidArgumentException('$key expected to be string or integer, got: '.gettype($key));
        }

        if (!array_key_exists($key, $this->container)) {
            if (null === $default) {
                return $first ? null : array();
            }

            return $first ? $default : array($default);
        }

        if ($first) {
            return count($this->container[$key]) ? $this->container[$key][0] : $default;
        }

        return $this->container[$key];
    }

    /**
     * Sets a email values.
     *
     * @param string|int   $key     The key
     * @param string|array $values  The value or an array of values
     * @param Boolean      $replace Whether to replace the actual value or not (true by default)
     *
     */
    public function set($key, $values, $replace = true)
    {
        if(!is_string($key) && !is_int($key)){
            throw new \InvalidArgumentException('$key expected to be string, got:'.gettype($key));
        }

        if( !is_string($values) && !is_array($values) ){
            throw new \InvalidArgumentException('$key expected to be string or array, got: '.gettype($key));
        }

        $values = array_values((array)$values);

        if (true === $replace || !isset($this->container[$key])) {
            $this->container[$key] = $values;
        } else {
            $this->container[$key] = array_merge($this->container[$key], $values);
        }
    }

    /**
     * Returns true if the email is defined.
     *
     * @param string $key The email
     *
     * @return Boolean true if the parameter exists, false otherwise
     *
     */
    public function has($key)
    {
        return array_key_exists($key, $this->container);
    }

    /**
     * Returns true if the given email contains the given value.
     *
     * @param string $key   The email name
     * @param string $value The email value
     *
     * @return Boolean true if the value is contained in the email, false otherwise
     *
     */
    public function contains($key, $value)
    {
        return in_array($value, $this->get($key, null, false));
    }

    /**
     * Removes a email.
     *
     * @param string $key The email name
     *
     */
    public function remove($key)
    {
        unset($this->container[$key]);
    }

    /**
     * Returns an iterator for container.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function count()
    {
        return count($this->container);
    }

    /**
     * Returns the number of container.
     *
     * @return object The ArrayIterator object of container
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->container);
    }

}