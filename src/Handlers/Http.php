<?php

namespace Bravist\Cnvex\Handlers;

use Bravist\Cnvex\SignatureManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Bravist\Cnvex\Handlers\Util\Util;

class Http extends Util
{
    public $signer;

    public $client;

    public $request;

    public $response;

    public function __construct(
        SignatureManager $signer,
        Client $client,
        array $config
    ) {
        $this->signer = $signer;
        $this->client = $client;
        $this->setConfig($config);
    }

    public function setConfig(array $config)
    {
        foreach ($config as $key => $value) {
            $method = 'set'.ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * HTTP post request
     * @param  array  $parameters
     * @return string
     */
    public function post(array $parameters = [])
    {
        if ($parameters) {
            if (isset($parameters['merchOrderNo'])) {
                $this->setMerchOrderNo($parameters['merchOrderNo']);
            } else {
                $this->setMerchOrderNo();
            }
            $parameters = array_merge($this->configureDefaults(), array_filter($parameters));
        }
        $parameters['sign'] = $this->signer->signer()->sign($parameters);
        try {
            $response = $this->client->post($this->getApiHost(), [
                'form_params' => $parameters
            ]);
        } catch (RequestException $e) {
            throw $e;
        }
        $res = json_decode((string) $response->getBody());
        $this->setRequest($parameters);
        $this->setResponse((string) $response->getBody());
        if ($this->getDebug() && $this->getLogger()) {
            $this->getLogger()->debug('===Host:===');
            $this->getLogger()->debug($this->getApiHost());
            $this->getLogger()->debug('===Parameters:===');
            $this->getLogger()->debug($parameters);
            $this->getLogger()->debug('===Response:===');
            $this->getLogger()->debug((string) $response->getBody());
        }
        if ($res->resultCode != 'EXECUTE_SUCCESS' &&
             $res->resultCode != 'EXECUTE_PROCESSING') {
            throw new \Exception('Server Request Error: '. $res->resultMessage);
        }
        return $res;
    }

    protected function setRequest($request)
    {
        $this->request = json_encode($request);
        return $this;
    }

    protected function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get return url
     * @param  array  $parameters
     * @return string
     */
    public function getReturnUrl(array $parameters = [])
    {
        if ($parameters) {
            if (isset($parameters['merchOrderNo'])) {
                $this->setMerchOrderNo($parameters['merchOrderNo']);
            }
            $parameters = array_merge($this->configureDefaults(), array_filter($parameters));
        }
        $parameters['sign'] = $this->signer->signer()->sign($parameters);
        if ($this->getDebug() && $this->getLogger()) {
            $this->getLogger()->debug('===RedirectUrl:===');
            $this->getLogger()->debug($this->getApiHost() . '?' . http_build_query($parameters));
        }
        return $this->getApiHost() . '?' . http_build_query($parameters);
    }
}
