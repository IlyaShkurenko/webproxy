<?php

namespace Proxy\Model;

class ExportedPorts
{
    // User
    protected $userId;
    protected $login;
    protected $apiKey;

    // Package
    protected $packageId;
    protected $ext;

    // IP
    protected $serverId;
    protected $serverIp;

    protected $blocks = [];
}
