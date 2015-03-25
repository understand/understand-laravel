<?php namespace Understand\UnderstandLaravel5;

class TokenProvider
{

    /**
     * Current token
     *
     * @var type
     */
    protected $token;

    /**
     * Generate and set new token
     */
    public function __construct()
    {
        $this->generate();
    }

    /**
     * Return token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Generate new token
     *
     * @return string
     */
    public function generate()
    {
        $this->token = $this->getUuid();
    }

    /**
     * Return unique string
     *
     * @return string
     */
    protected function getUuid()
    {
        // http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
        // by Andrew Moore (http://www.php.net/manual/en/function.uniqid.php#94959)
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

}
