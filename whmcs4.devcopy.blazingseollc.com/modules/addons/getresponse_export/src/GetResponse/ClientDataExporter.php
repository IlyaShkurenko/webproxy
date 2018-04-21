<?php

namespace WHMCS\Module\Blazing\Export\GetResponse;

use WHMCS\Module\Blazing\Export\Common\Traits\DebuggerTrait;
use WHMCS\Module\Blazing\Export\GetResponse\Client\Client as GetResponse;
use WHMCS\Module\Blazing\Export\Compiler\Compiler;
use WHMCS\Module\Blazing\Export\GetResponse\Model\ExportedClient;
use WHMCS\User\Client;

class ClientDataExporter
{
    use DebuggerTrait;

    /**
     * @var Compiler
     */
    private $compiler;

    private $contextFactory;

    private $capsule;

    /**
     * @var GetResponse
     */
    private $getResponse;

    public function __construct(
        $compiler,
        $capsule,
        $contextFactory,
        GetResponse $getResponse
    ) {
        $this->compiler = $compiler;
        $this->contextFactory = $contextFactory;
        $this->capsule = $capsule;
        $this->getResponse = $getResponse;
    }

    public function export($clientIterator)
    {
        $this->debug('Exporting new users...');
        foreach ($clientIterator as $i => $client) {
            try {
                $this->debug('Exporting new user ' . $client->id);
                $context = $this->contextFactory->create($client);
                $compiled = $this->compiler->compile($context);
                $result = $this->getResponse->addContact($compiled);
                $success = $result->isSuccess() || $result->getStatus() === 409;
                if ($result->isFatal() or !$success) {
                    $this->debug('Export failure!', [
                        'status' => $result->getStatus(),
                        'result' => $result->getResult(),
                        'compiledData' => $compiled
                    ]);
                }
                $this->table()
                    ->insert(
                        [
                            'client_id'  => $client->id,
                            'status'     => $success ? 0 : 2,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            } catch (\Exception $exception) {
                $this->debug(
                    'Export failure: ' . $exception->getTraceAsString()
                );
            }
        }
    }

    public function table()
    {
        return $this->capsule->table(
            'getresponseexport_exported_clients'
        );
    }

    public function pullIds($clients)
    {
        $this->debug('Pulling user ids...');
        $updated = [];
        foreach ($clients as $id => $email) {
            $this->debug("Pulling GR id for user $id-$email");
            $client = Client::find($id);
            $res = $this->getResponse
                ->getContacts(
                    [
                        'query[email]' => $email
                    ]
                );
            $res = $res[0];
            if (isset($res['contactId'])) {
                $status = ExportedClient::STATUS_ACTUAL;
                if ($client->emailOptOut) {
                    $status = ExportedClient::STATUS_UNSUBSCRIBED;
                    $this->getResponse->deleteContact($res['contactId']);
                }
                $this->debug('Pull successful');
                $this->table()
                    ->where('client_id', $id)
                    ->update(
                        [
                            'getresponse_id' => $res['contactId'],
                            'updated_at'     => date('Y-m-d H:i:s'),
                            'status'         => $status,
                        ]
                    );
                $updated[] = $id;
            } else {
                $this->debug('Pull unsuccessful');
            }
        }
        return $updated;
    }

    public function update($updateClients)
    {
        $count = count($updateClients);
        $this->debug("Updating exported $count users...");
        foreach ($updateClients as $i => $updateClient) {
            $this->debug(
                "Exporting updated user {$updateClient['client_id']}, " . ($i
                    + 1) . "/$count"
            );
            $clientID = $updateClient['client_id'];
            $client = Client::find($clientID);
            if (!$client) {
                $this->debug('Client was not found!');
                continue;
            }
            $context = $this->contextFactory->create($client);
            $contact = $this->compiler->compile($context);

            if ($client->emailOptOut) {
                $status = ExportedClient::STATUS_UNSUBSCRIBED;
                $res = $this->getResponse
                    ->deleteContact($updateClient['getresponse_id']);
            } else {
                $status = ExportedClient::STATUS_ACTUAL;
                $res = $this->getResponse->updateContact(
                    $updateClient['getresponse_id'],
                    $contact
                );
            }
            $update = $this->table()
                ->where('client_id', $clientID);
            if ($res->isSuccess()) {
                $update->update(
                    [
                        'status'     => $status,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            } elseif ($res->isNotFound()) {
                $update->update(
                    [
                        'status'     => ExportedClient::STATUS_UNSUBSCRIBED,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            } else {
                $this->debug('Client update failed');
            }
        }
    }
}
