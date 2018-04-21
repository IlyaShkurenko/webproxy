<?php

namespace Proxy\Assignment\Port\IPv4;

class ProxyPort extends Port
{

    // Proxy attributes
    protected $dead;
    protected $active;
    protected $ip;

    public static function fromArray($data)
    {
        /**
         * @var static $object
         */
        $object = parent::fromArray($data);

        if (isset($data[ 'dead' ])) {
            $object->setDead($data[ 'dead' ]);
        }

        if (isset($data[ 'active' ])) {
            $object->setActive($data[ 'active' ]);
        }

        if (isset($data[ 'ip' ])) {
            $object->setId($data[ 'ip' ]);
        }

        return $object;
    }

    /**
     * Is dead
     *
     * @return mixed
     */
    public function isDead()
    {
        return $this->dead;
    }

    /**
     * Set dead
     *
     * @param mixed $dead
     * @return $this
     */
    public function setDead($dead)
    {
        $this->dead = !!$dead;

        return $this;
    }

    /**
     * Get active
     *
     * @return mixed
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Set active
     *
     * @param mixed $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = !!$active;

        return $this;
    }

    /**
     * Get ip
     *
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set ip
     *
     * @param mixed $ip
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }
}
