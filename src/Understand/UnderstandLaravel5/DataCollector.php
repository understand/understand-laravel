<?php namespace Understand\UnderstandLaravel5;

class DataCollector
{

    /**
     * Current token
     *
     * @var type
     */
    protected $data = [];

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setInArray($key, $value)
    {
        $this->data[$key][] = $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getByKey($key)
    {
        if (isset($this->data[$key]))
        {
            return $this->data[$key];
        }
    }
}
