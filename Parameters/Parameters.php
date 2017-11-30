<?php


namespace W3com\BoomBundle\Parameters;


class Parameters
{
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';

    /**
     * @var array
     */
    private $select = array();

    /**
     * @var array
     */
    private $orderBy = array();

    /**
     * @var array
     */

    private $filter = array();
    /**
     * @var string
     */
    private $format = '';

    private $top = 0;

    /**
     * Column names of the targeted entity
     * @var array
     */
    private $columns;

    /**
     * Parameters constructor.
     * @param $columns array Column names of the targeted entity
     */
    public function __construct($columns)
    {
        $this->columns = $columns;
    }

    /**
     * @param array|string $select
     * @return $this
     * @throws \Exception
     */
    public function addSelect($select)
    {
        if (is_array($select)) {
            foreach ($select as $item) {
                if (!array_key_exists($item, $this->columns)) {
                    throw new \Exception('Cannot select on unknown column '.$item);
                }
            }
            $this->select = array_merge($this->select, $select);
        } else {
            if (!array_key_exists($select, $this->columns)) {
                throw new \Exception('Cannot select on unknown column '.$select);
            }
            $this->select[] = $select;
        }

        return $this;
    }

    /**
     * @param string $column
     * @param string $order
     * @return $this
     * @throws \Exception
     */
    public function addOrder($column, $order = Parameters::ORDER_ASC)
    {
        if (!array_key_exists($column, $this->columns)) {
            throw new \Exception('Cannot order on unknown column '.$order);
        }
        if ($order != Parameters::ORDER_ASC && $order != Parameters::ORDER_DESC) {
            throw new \Exception("Unknown order $order");
        }
        $this->orderBy[$column] = $order;

        return $this;
    }

    /**
     * @param array|string $filter
     * @return $this
     */
    public function addFilter($filter)
    {
        if (is_array($filter)) {
            $this->filter = array_merge($this->filter, $filter);
        } else {
            $this->filter[] = $filter;
        }

        return $this;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    public function setTop($top)
    {
        $this->top = $top;

        return $this;
    }

    /**
     * Returns the URL parameters string
     * @return string
     */
    public function getParameters()
    {
        $params = array();
        // gestion du select
        if (count($this->select) > 0) {
            $select = array();
            foreach ($this->select as $item) {
                $select[] = $this->columns[$item]['column'];
            }
            $params[] = '$select='.implode(',', $select);
        }
        // gestion du filter
        if (count($this->filter) > 0) {
            $filterAr = array();
            foreach ($this->filter as $field => $value) {
                $quotes = $this->columns[$field]['quotes'] ? "'" : '';
                $filterAr[] = $this->columns[$field]['column']." eq $quotes".$value."$quotes";
            }
            $params[] = '$filter='.implode(',', $filterAr);
        }
        // gestion du orderBy
        if (count($this->orderBy) > 0) {
            $orderBy = array();
            foreach ($this->orderBy as $col => $order) {
                $orderBy[] = $this->columns[$col]['column'].' '.$order;
            }
            $params[] = '$orderby='.implode(',', $orderBy);
        }
        //gestion du format
        if ($this->format != '') {
            $params[] = '$format='.$this->format;
        }
        // gestion du top
        if ($this->top > 0) {
            $params[] = '$top='.$this->top;
        }

        // génération de l'url
        if (count($params) == 0) {
            return '';
        } else {
            return str_replace(' ', '%20', '?'.implode('&', $params));
        }
    }
}