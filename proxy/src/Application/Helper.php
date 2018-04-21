<?php

namespace Application;

use Camel\CaseTransformer;
use Camel\Format\SpinalCase;
use Camel\Format\StudlyCaps;
use Doctrine\DBAL\Driver\Statement;
use Silex\Application;

class Helper
{
    public static function getClassBasename($className)
    {
        if (is_object($className)) {
            $className = get_class(($className));
        }

        $tmp = explode('\\', $className);

        return end($tmp);
    }

    public static function hunamizeClassName($className)
    {
        return (new CaseTransformer(new StudlyCaps(), new SpinalCase()))->transform(self::getClassBasename($className));
    }

    /**
     * Funny name, yeah
     *
     * @param $string
     * @return string
     */
    public static function robotizeHumanClassName($string)
    {
        return (new CaseTransformer(new SpinalCase(), new StudlyCaps()))->transform($string);
    }

    public static function queueStatement(Statement $stmt, $queueSize, callable $queueCallback)
    {
        $fetching = true;
        $countProcessed = 0;
        $countAll = $stmt->rowCount();

        while ($fetching) {
            $rows = [];

            // Fill the queue
            for ($i = 0; $i < $queueSize; $i++) {
                $row = $stmt->fetch();

                // No more rows
                if (!$row) {
                    $fetching = false;
                    break;
                }

                $rows[] = $row;
            }

            // Call the callback, queue size is equal or less
            $queueCallback($rows, $countProcessed, $countAll, !$fetching);

            // Leave them know how many already processed (just to be convenient)
            $countProcessed += count($rows);
        }
    }

    public static function generateLogin($email, $resellerId = false)
    {
        return substr(md5($email . (($resellerId and $resellerId != 1) ? $resellerId : '')), 0, 10);
    }

    public static function sanitizeLogin($login, Application $app)
    {
        $login = trim($login);

        $allowed = $app['config.proxy.auth.login.characters'];
        if (!preg_match("~^[$allowed]+$~", $login)) {
            $dataset[ 'login' ] = strtolower($login);
            $dataset[ 'login' ] = preg_replace("~[^$allowed]~", '_', $login);
        }

        return $login;
    }
}
