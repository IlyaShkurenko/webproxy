<?php

namespace Proxy\Controller\Dashboard;

use ErrorException;
use Exception;
use IPTools\Network;

class IPv6Controller extends AbstractController
{
    public function importBlocksAction()
    {
        if ($this->request->isMethod('post')) {
            if (!$this->getConn()
                ->executeQuery('SELECT id FROM proxy_servers_ipv6 WHERE id = ?', [$this->request->get('server')])
                ->fetchColumn()) {
                throw new ErrorException('Server does not exist - please select from existent ones');
            }

            $blocks = $this->request->get('blocks');
            $blocks = array_map('trim', preg_split('~[\n\r]+~', $blocks));

            $updatedTotal = 0;
            try {
                foreach ($blocks as $i => $block) {
                    // Validate block data
                    list ($blockIp, $netmask) = explode('/', $block);
                    if (!filter_var($blockIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        throw new ErrorException("\"$blockIp\" is not valid IPv6");
                    }
                    if (!$netmask) {
                        throw new ErrorException("No netmask passed");
                    }
                    elseif (!ctype_digit($netmask)) {
                        throw new ErrorException("\"$netmask\" is not valid netmask");
                    }

                    // Prepare to split
                    $network = Network::parse("$blockIp/$netmask");
                    $targetSubnet = 48;
                    $defaultLocationId = 1;
                    $networks = $network->moveTo($targetSubnet);

                    // Import base block
                    $data = $this->getConn()
                        ->executeQuery('SELECT id FROM proxies_ipv6_sources WHERE block = ? AND subnet = ?',
                            [$network->getIp(), $network->getPrefixLength()])
                        ->fetch();
                    if (!empty($data['id'])) {
                        $blockId = $data['id'];
                        // Move to another server
                        if ($data['server_id'] != $this->request->get('server')) {
                            $this->getConn()->update('proxies_ipv6_sources', [
                                'server_id' => $this->request->get('server'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ], ['id' => $data['id']]);
                        }
                    }
                    if (empty($blockId)) {
                        $this->getConn()->insert('proxies_ipv6_sources', [
                            'block' => $network->getIP(),
                            'subnet' => $network->getPrefixLength(),
                            'server_id' => $this->request->get('server'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $blockId = $this->getConn()->lastInsertId();
                    }

                    // Go through the block (split to smaller blocks)
                    // Here sub-blocks are added or retied to another server (if they were in the db)
                    $batchSize = 1000;
                    $batchQueue = [];
                    $updated = 0;
                    foreach ($networks as $nw) {
                        /** @var Network $nw */
                         $batchQueue[] = $nw->getIP()->__toString();

                         // Flush batch
                         if ($batchSize == count($batchQueue)) {
                             $this->upsertBlocks($batchQueue, $blockId, $targetSubnet, $defaultLocationId);
                             $updated += $batchSize;
                             $batchQueue = [];
                         }
                    }
                    if ($batchQueue) {
                        $this->upsertBlocks($batchQueue, $blockId, $targetSubnet, $defaultLocationId);
                        $updated += count($batchQueue);
                    }

                    $updatedTotal += $updated;
                    $this->addFlashSuccess("Block \"$block\" - imported $updated x /$targetSubnet blocks");
                }

                $this->request->request->set('blocks', '');
            }
            catch (Exception $e) {
                /** @noinspection PhpUndefinedVariableInspection */
                $this->addFlashError("\"$block\" is not valid block, " . lcfirst($e->getMessage()) . '. Please correct the data');
            }
        }

        $servers = $this->getConn()->executeQuery("SELECT id, ip, name FROM proxy_servers_ipv6")->fetchAll();

        return $this->renderDefaultTemplate([
            'data' => $this->request->request->all(),
            'servers' => $servers
        ]);
    }

    protected function upsertBlocks(array $blocks, $baseBlockId, $networkPrefix, $locationId = 1)
    {
        $parameters = [
            'sourceId' => $baseBlockId,
            'now' => date('Y-m-d H:i:s')
        ];
        $sqlValues = [];

        foreach ($blocks as $i => $subBlockId) {
            $newParameters = [
                "source_id_$i"   => $baseBlockId,
                "block_$i"       => $subBlockId,
                "subnet_$i"      => $networkPrefix,
                "location_id_$i" => $locationId,
                "created_at_$i"  => date('Y-m-d H:i:s'),
                "updated_at_$i"  => date('Y-m-d H:i:s'),
            ];
            $parameters += $newParameters;
            $sqlValues[] =
                '(' .
                join(',', array_map(function($k) { return ":$k"; }, array_keys($newParameters))) .
                ')';
        }
        $sqlValues = join(',', $sqlValues);

        $sql = "
             INSERT INTO proxies_ipv6 
             (source_id, block, subnet, location_id, created_at, updated_at)
             VALUES $sqlValues 
             ON DUPLICATE KEY
             UPDATE source_id = :sourceId, updated_at = :now";
        $this->getConn()->executeUpdate($sql, $parameters);
    }
}