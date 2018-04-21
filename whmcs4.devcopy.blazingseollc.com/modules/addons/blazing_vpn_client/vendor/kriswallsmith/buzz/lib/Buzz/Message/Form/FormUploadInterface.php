<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Message\Form;

use Blazing\Vpn\Client\Vendor\Buzz\Message\MessageInterface;
interface FormUploadInterface extends MessageInterface
{
    public function setName($name);
    public function getFile();
    public function getFilename();
    public function getContentType();
}