<?php namespace Understand\UnderstandLaravel5\Handlers;

abstract class BaseHandler
{

    /**
     * Input token
     *
     * @var string
     */
    protected $inputToken;

    /**
     * API url
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * SSL CA bundle path
     *
     * @var string
     */
    protected $sslBundlePath;

    /**
     * Send data to storage
     *
     * @param string $data
     * @return string
     */
    abstract protected function send($data);

    /**
     * @param string $inputToken
     * @param string $apiUrl
     * @param string $sslBundlePath
     */
    public function __construct($inputToken, $apiUrl, $sslBundlePath = null)
    {
        $this->setInputKey($inputToken);
        $this->setApiUrl($apiUrl);

        $this->sslBundlePath = $sslBundlePath;
    }

    /**
     * Serialize data and send to storage
     *
     * @param array $requestData
     * @return array
     */
    public function handle(array $requestData)
    {
        $json = json_encode($requestData);

        $response = $this->send($json);

        return $this->parseResponse($response);
    }

    /**
     * Set api url
     *
     * @param string $apiUrl
     */
    protected function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * Set input key
     *
     * @param string $inputToken
     */
    protected function setInputKey($inputToken)
    {
        $this->inputToken = $inputToken;
    }

    /**
     * Return endpoint
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return implode('/', [$this->apiUrl, $this->inputToken]);
    }

    /**
     * Parse respnse into array
     *
     * @param string $response
     * @return array
     */
    protected function parseResponse($response)
    {
        return json_decode($response, true);
    }
}
