<?php namespace Nova\Queue\Connectors;

use IronMQ;
use Nova\Http\Request;
use Nova\Queue\IronQueue;
use Nova\Encryption\Encrypter;

class IronConnector implements ConnectorInterface {

    /**
     * The encrypter instance.
     *
     * @var \Nova\Encryption\Encrypter
     */
    protected $crypt;

    /**
     * The current request instance.
     *
     * @var \Nova\Http\Request
     */
    protected $request;

    /**
     * Create a new Iron connector instance.
     *
     * @param  \Nova\Encryption\Encrypter  $crypt
     * @param  \Nova\Http\Request  $request
     * @return void
     */
    public function __construct(Encrypter $crypt, Request $request)
    {
        $this->crypt = $crypt;
        $this->request = $request;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Nova\Queue\QueueInterface
     */
    public function connect(array $config)
    {
        $ironConfig = array('token' => $config['token'], 'project_id' => $config['project']);

        if (isset($config['host'])) $ironConfig['host'] = $config['host'];

        $iron = new IronMQ($ironConfig);

        if (isset($config['ssl_verifypeer']))
        {
            $iron->ssl_verifypeer = $config['ssl_verifypeer'];
        }

        return new IronQueue($iron, $this->request, $config['queue'], $config['encrypt']);
    }

}
