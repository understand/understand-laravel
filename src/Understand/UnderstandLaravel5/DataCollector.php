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
     * @var int
     */
    protected $limit = 50;

    /**
     * @param $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

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
        if (isset($this->data[$key]) && count($this->data[$key]) > ($this->limit - 1))
        {
            array_shift($this->data[$key]);
        }

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
