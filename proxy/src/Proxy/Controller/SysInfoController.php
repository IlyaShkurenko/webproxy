<?php

namespace Proxy\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Application\AbstractApiController;

class SysInfoController extends AbstractApiController
{
    protected $convertResponse = 'json';

    public function getUserByWhmcsId()
    {
        $whmcsUserId = $this->request->request->get('userId');

        if (!$whmcsUserId) {
            return new JsonResponse(['status' => 'error', 'message' => 'No userId passed'], 400);
        }

        $qb = $this->getConn()->createQueryBuilder();
        $user = $qb->from('proxy_users')
            ->select('id')
            ->where('whmcs_id = ' . $qb->createNamedParameter($whmcsUserId))
            ->execute()->fetch();

        return $user ? $user : ['status' => 'error', 'message' => "No user found with id \"$whmcsUserId\""];
    }

    public function getUserByAuthCredentials()
    {
        $key = $this->request->get('key');
        if (!$key) {
            return new Response('No key is passed', 400);
        }

        $users = $this->getConn()->executeQuery(
            "SELECT pu.id, pu.email, pu.api_key, pu.preferred_format
            FROM proxy_users pu
            LEFT JOIN user_ips ui ON ui.user_id = pu.id AND ui.user_type = 'BL'
            LEFT JOIN banditim_amember.am_user am ON pu.amember_id = am.user_id
            WHERE pu.email = :key OR ui.ip = :key OR pu.api_key = :key OR pu.id LIKE :key OR am.login = :key OR SUBSTRING(MD5(pu.email), 1, 10) LIKE :key
            GROUP BY pu.id", ['key' => $key])->fetchAll();

        $response = array_map(function($row) { return join(',', $row); }, $users);

        return $this->getTextPlainResponse($response);
    }

    public function ping()
    {
        return new Response('pong');
    }
}
