<?php

namespace Proxy\Assignment\Port;

class CommonPackageContext
{

    const NEED_UNKNOWN = 0;
    const NEED_NEW = 1;
    const NEED_REPLACE = 2;
    const NEED_DEAD = 2;
    const NEED_ROTATION = 3;

    protected $need = self::NEED_UNKNOWN;

    /**
     * Get need
     *
     * @return mixed
     */
    public function getNeed()
    {
        return $this->need;
    }

    /**
     * Set need
     *
     * @param mixed $need
     * @return $this
     */
    public function setNeed($need)
    {
        $this->need = $need;

        return $this;
    }
}