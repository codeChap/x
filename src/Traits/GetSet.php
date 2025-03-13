<?php

namespace codechap\x\Traits;

trait GetSet {
    /**
     * Get a specific property from the service
     *
     * @param string $name The property name to get
     * @return mixed The value of the property
     */
    public function get(string $name)
    {
        if (!property_exists($this, $name)) {
            throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $name));
        }
        return $this->$name;
    }

    /**
     * Set a specific property for the service
     *
     * @param string $name The property name to set
     * @param mixed $value The value to set
     * @return self Returns the current instance
     */
    public function set(string $name, $value): self
    {
        if (!property_exists($this, $name)) {
            throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $name));
        }
        $this->$name = trim($value);
        return $this;
    }
}
