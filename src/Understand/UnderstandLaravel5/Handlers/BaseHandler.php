<?php namespace Understand\UnderstandLaravel5\Handlers;

use Understand\UnderstandLaravel5\Exceptions\HandlerException;

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
     * Specifies whether logger should throw an exception of issues detected
     *
     * @var bool
     */
    protected $silent = true;

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
     * @param boolean $silent
     * @param string $sslBundlePath
     */
    public function __construct($inputToken, $apiUrl, $silent = true, $sslBundlePath = null)
    {
        $this->setInputKey($inputToken);
        $this->setApiUrl($apiUrl);

        $this->sslBundlePath = $sslBundlePath;
        $this->silent = $silent;
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

        return $this->parseResponse($response, $requestData);
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
     * @param type $response
     * @param string $requestData
     * @return array
     */
    protected function parseResponse($response, $requestData)
    {
        $responseArr = json_decode($response, true);

        if (!$this->silent && empty($responseArr['count']))
        {
            $this->handleError($responseArr, $requestData);
        }

        return $responseArr;
    }

    /**
     * Transform error respopnse into exception
     *
     * @param string $responseArr
     * @param string $requestData
     * @throws HandlerException
     */
    protected function handleError($responseArr, $requestData)
    {
        if (!$responseArr)
        {
            throw new HandlerException('Cannot create connection to ' . $this->apiUrl);
        }

        if (isset($responseArr['error']))
        {
            throw new HandlerException($responseArr['error']);
        }

        throw new HandlerException('Error. ' . ' Request data: ' . json_decode($requestData));
    }

}
