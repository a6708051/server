<?php
namespace server\app\test;
use server;

require '../../Autoload.php';
server\Autoload::register();

class TestClient
{
    private $client;

    public function __construct() {
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP);
    }

    public function connect() {
        if( !$this->client->connect("127.0.0.1", 9508 , 1) ) {
            echo "Connect Error";
        }

        $this->client->send('test msg send!');
    }
}

$obj = new GatewayTest();
$obj->connect();
