<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Command;

use Symfony\Component\Console\Output\OutputInterface;
use WHMCS\Module\Blazing\Export\Common\Traits\DebuggerTrait;
use WHMCS\Module\Blazing\Export\GetResponse\Compiler\CompilerMergeDecorator;
use WHMCS\Module\Blazing\Export\GetResponse\Model\ExportedClient;
use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Helper;
use WHMCS\User\Client;
use WHMCS\Database\Capsule;
use WHMCS\Module\Blazing\Export\GetResponse\ClientContextFactory;
use WHMCS\Module\Blazing\Export\GetResponse\ClientDataExporter;
use WHMCS\Module\Blazing\Export\GetResponse\CompilerBuilder;
use WHMCS\Module\Blazing\Export\GetResponse\Client\Client as GetResponse;

class ExportCommand
{
    use DebuggerTrait;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $group;

    private $forceInit;

    private $requiredTags = [
        'start_month1'
    ];

    /**
     * @var GetResponse
     */
    private $grClient;
    /**
     * @var bool
     */
    private $add;

    /**
     * ExportCommand constructor.
     *
     * @param int  $limit
     * @param int  $group
     * @param bool $add
     * @param bool $forceInit
     */
    public function __construct(
        $limit = 0,
        $group = 0,
        $add = true,
        $forceInit = false
    ) {
        $this->limit = $limit;
        $this->group = $group;
        $this->forceInit = $forceInit;
        $this->add = $add;
    }

    public function export()
    {
        $connection = Helper::conn();
        $config = $connection->table('tbladdonmodules')
            ->select('setting', 'value')
            ->where('module', 'getresponse_export')
            ->pluck('value', 'setting');

        if (!isset($config['apiKey'], $config['campaignId'])) {
            throw new \LogicException(
                'Plugin GetResponse export is not configured.'
            );
        }

        $this->debug('Initialization...');

        $table = $connection->table('getresponseexport_exported_clients');

        if ($table->count() === 0 && !$this->forceInit) {
            $this->debug(
                'No exported users and init procedure is not initiated, exiting...'
            );
            return;
        }

        $grClient = $this->grClient = new GetResponse($config['apiKey']);
        $tags = $grClient->getTags();
        $customFields = $grClient->getCustomFields();
        if (!$customFields->isSuccess() || !$tags->isSuccess()) {
            throw new \RuntimeException(
                'Failed to retrieve custom field config.'
            );
        }

        $tags = $this->prepareTags($tags->getResult());
        $customFieldMap = array_column(
            $customFields->getResult(), 'customFieldId', 'name'
        );

        $compiler = (new CompilerBuilder($customFieldMap, $tags))->build();
        $compiler = new CompilerMergeDecorator(
            $compiler,
            $this->grClient,
            function () use ($connection) {
                return $connection->table('getresponseexport_exported_clients');
            }
        );

        $exporter = new ClientDataExporter(
            $compiler,
            $connection,
            new ClientContextFactory($config),
            $grClient
        );
        $exporter->pullPipesFrom($this);

        if ($this->add) {
            $this->debug('Loading users list...');

            $exportedUsers = $table->select('client_id');
            $clients = Client::whereNotIn('id', $exportedUsers)->where(
                'emailoptout', 0
            );
            if ($this->limit) {
                $clients->limit($this->limit);
            }
            if ($this->group) {
                $clients->where('groupId', $this->group);
            }

            if ($clients->count()) {
                $exporter->export($clients->cursor());
            } else {
                $this->debug('No users found');
            }
        }

        $importUsers = $exporter->table()
            ->select('client_id')
            ->whereNull('getresponse_id')
            ->where('status', '<', ExportedClient::STATUS_DECLINED)
            ->lists('client_id');

        $clients = Client::whereIn('id', $importUsers)
            ->select('email', 'id')
            ->pluck('email', 'id')
            ->all();

        $imported = $exporter->pullIds($clients);

        $this->debug('Loading users list...');
        $updateClients = $exporter->table()
            ->select('getresponse_id', 'client_id')
            ->whereNotNull('getresponse_id')
            ->where(
                function ($query) use ($imported) {
                    $query->orWhereIn('client_id', $imported);
                    $query->orWhere('status', 1);
                }
            )
            ->get();

        if (count($updateClients)) {
            $exporter->update($updateClients);
        } else {
            $this->debug('No users found');
        }
    }

    private function prepareTags($tags)
    {
        $tags = array_column($tags, 'name', 'tagId');
        $unmaintainedTags = array_diff($this->requiredTags, $tags);
        foreach ($unmaintainedTags as $tag) {
            $response = $this->grClient->addTag(
                [
                    'name' => $tag
                ]
            );
            $tags[$tag] = $response->offsetGet('tagId');
        }
        return array_flip($tags);
    }
}
