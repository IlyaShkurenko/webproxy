<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Listener;

use Blazing\Vpn\Client\Vendor\Buzz\Listener\History\Journal;
use Blazing\Vpn\Client\Vendor\Buzz\Message\MessageInterface;
use Blazing\Vpn\Client\Vendor\Buzz\Message\RequestInterface;
class HistoryListener implements ListenerInterface
{
    private $journal;
    private $startTime;
    public function __construct(Journal $journal)
    {
        $this->journal = $journal;
    }
    public function getJournal()
    {
        return $this->journal;
    }
    public function preSend(RequestInterface $request)
    {
        $this->startTime = microtime(true);
    }
    public function postSend(RequestInterface $request, MessageInterface $response)
    {
        $this->journal->record($request, $response, microtime(true) - $this->startTime);
    }
}