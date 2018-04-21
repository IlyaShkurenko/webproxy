<?php
/**
 * Created by PhpStorm.
 * User: Illia
 * Date: 05.02.2018
 * Time: 22:31
 */
namespace WHMCS\Module\Blazing\Proxy\Seller\Util;

use /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUndefinedNamespaceInspection */
    Illuminate\Database\Capsule\Manager;

use
    /** @noinspection PhpUndefinedNamespaceInspection */
    /** @noinspection PhpUndefinedClassInspection */
    Illuminate\Database\Connection;

use
    /** @noinspection PhpUndefinedNamespaceInspection */
    /** @noinspection PhpUndefinedClassInspection */
    Illuminate\Database\Query\Grammars\MySqlGrammar;

class IsolatedConnection extends Connection
{
    public function __construct() {
        $conn = Manager::connection();

        parent::__construct($conn->getPdo(), $conn->getDatabaseName(), $conn->getTablePrefix(), $conn->config);

        $this->setQueryGrammar(new MySqlGrammar());
    }
}