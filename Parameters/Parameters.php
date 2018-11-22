<?php

namespace W3com\BoomBundle\Parameters;

class Parameters
{
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';

    /**
     * @var array
     */
    private $select = [];

    /**
     * @var array
     */
    private $orderBy = [];

    /**
     * @var array
     */
    private $filter = [];

    private $ipFilter = [];

    /**
     * @var string
     */
    private $rawFilter = '';

    /**
     * @var string
     */
    private $format = '';

    private $top = 0;

    private $skip = 0;

    /**
     * Column names of the targeted entity.
     *
     * @var array
     */
    private $columns;

    /**
     * Parameters constructor.
     *
     * @param $columns array Column names of the targeted entity
     */
    public function __construct($columns)
    {
        $this->columns = $columns;
    }

    /**
     * @param array|string $select
     *
     * @return $this
     *
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
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function addOrder($column, $order = Parameters::ORDER_ASC)
    {
        if (!array_key_exists($column, $this->columns)) {
            throw new \Exception('Cannot order on unknown column '.$order);
        }
        if (Parameters::ORDER_ASC != $order && Parameters::ORDER_DESC != $order) {
            throw new \Exception("Unknown order $order");
        }
        $this->orderBy[$column] = $order;

        return $this;
    }

    /**
     * @param string $column
     * @param int|string|array $value
     * @param string $operator
     * @param string $logicalOperator
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function addFilter($column, $value, $operator = Clause::EQUALS, $logicalOperator = Clause:: AND)
    {
        // on traduit la column vers un nommage Hana
        if (!array_key_exists($column, $this->columns)) {
            throw new \Exception('Cannot filter on unknown column '.$column);
        }
        $columnHana = (null !== $this->columns[$column]['readColumn']) ? $this->columns[$column]['readColumn'] : $this->columns[$column]['column'];
        $usingQuote = $this->columns[$column]['quotes'];
        $this->filter[] = new Clause($columnHana, $value, $operator, $usingQuote, $logicalOperator);

        // managment of ip filter
        if($this->columns[$column]['ipName'] != null){
            $this->ipFilter[$columnHana] = $value;
        }

        return $this;
    }

    public function addRawFilter($rawFilter)
    {
        $this->rawFilter = $rawFilter;

        return $this;
    }

    /**
     * @param string $format
     *
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

    public function setSkip($skip)
    {
        $this->skip = $skip;

        return $this;
    }

    public function getIPFilter()
    {
        $arr = [];
        if (count($this->columns) > 0){
            foreach($this->columns as $column){
                if($column['ipName'] != null){
                    if(array_key_exists($column['column'],$this->ipFilter)){
                        $quotes = $column['quotes'] ? "'" : "";
                        $arr[] = $column['ipName']."=".$quotes.$this->ipFilter[$column['column']].$quotes;
                    } else {
                        $arr[] = $column['ipName']."='*'";
                    }
                }
            }
        }
        return "(".implode($arr,',').")";
    }

    /**
     * Returns the URL parameters string.
     *
     * @return string
     */
    public function getParameters()
    {
        $params = [];
        // gestion du select
        if (count($this->select) > 0) {
            $select = [];
            foreach ($this->select as $item) {
                //$select[] = (null !== $this->columns[$item]['readColumn']) ? $this->columns[$item]['readColumn'] : $this->columns[$item]['column'];
                if ($this->columns[$item]['readColumn'] !== null){
                    $select[] = $this->columns[$item]['readColumn'];
                } elseif ($this->columns[$item]['column'] !== null){
                    $select[] = $this->columns[$item]['column'];
                } elseif ($this->columns[$item]['complexColumn'] !== null){
                    $select[] = $this->columns[$item]['complexColumn'];
                }
            }
            $params[] = '$select='.implode(',', $select);
        }

        // gestion du filter
        if (count($this->filter) > 0 || '' != $this->rawFilter) {
            $filterAr = [];
            if (count($this->filter) > 0) {
                $i = 0;
                foreach ($this->filter as $clause) {
                    $logicalOperator = ($i > 0) ? $clause->getLogicalOperator() : '';
                    $filterAr[] = $logicalOperator.$clause->render();
                    ++$i;
                }
            }
            if ('' != $this->rawFilter) {
                $filterAr[] = $this->rawFilter;
            }

            $params[] = '$filter='.implode('', $filterAr);
        }

        // gestion du orderBy
        if (count($this->orderBy) > 0) {
            $orderBy = [];
            foreach ($this->orderBy as $col => $order) {
                $colName = (null != $this->columns[$col]['readColumn']) ? $this->columns[$col]['readColumn'] : $this->columns[$col]['column'];
                $orderBy[] = $colName.' '.$order;
            }
            $params[] = '$orderby='.implode(',', $orderBy);
        }
        //gestion du format
        if ('' != $this->format) {
            $params[] = '$format='.$this->format;
        }
        // gestion du top
        if ($this->top > 0) {
            $params[] = '$top='.$this->top;
        }

        // gestion du skip
        if ($this->skip > 0) {
            $params[] = '$skip='.$this->skip;
        }

        // génération de l'url
        if (0 == count($params)) {
            return '';
        } else {
            return str_replace(' ', '%20', '?'.implode('&', $params));
        }
    }
}
