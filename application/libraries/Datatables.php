<?php
class Datatables {
    private $header;
    private $selection;
    private $data;
    private $resultHandler;
    private $keyword;
    private $search_option;
    private $all_data;
    private $filterred_data;
    private $reCount = false;
    private $isFilterred = false;
    private $enableCache = false;
    /**
     * @var CI_DB_query_builder
     * 
     */
    public $query;
    
    /**
     * Tambahkan kolom yang akan di select dari database
     * @param String $select String nama field database
     * @return Void
     */

    public function __construct() {
        $this->keyword = isset($_GET['search']) && isset($_GET['search']['value']) ? $_GET['search']['value'] : null;
    }
    public function addSelect(string $select = '*'){
        $this->selection = $select;
        return $this;
    }
    
    /**
     * @param CI_DB_query_builder $query Query sql database
     * @return Void
     */
    
    function setQuery($query){
        $searchable = [];
        $query->select($this->selection);
        foreach($this->header as $key => $value){
            if(!isset($value['searchable']) || $value['searchable'] == false) continue;

            if(is_string($value['searchable']))
                $searchable[] = $value['searchable'];
            else
                $searchable[] = $key;
        }
        $option = $this->search_option;
        $keyword = $this->keyword;

        if(empty($option)){
            $option = array(
                'spesifik' => false,
                'liketipe' => 'after',
            );
        }
        $all_data_q = clone $query;
        $this->all_data = $all_data_q->get()->num_rows();
        if(!empty($keyword)){
            $i = 0;
            foreach($searchable as $v){
                if($option['spesifik']){
                    if($i = 0) $query->where($v, $keyword, FALSE);
                    else $query->or_where($v, $keyword, FALSE);
                }else{
                    if($i = 0) $query->like($v, $keyword, $option['liketipe'], FALSE);
                    else $query->or_like($v, $keyword, $option['liketipe'], FALSE);
                }
                $i++;
            }
        }

        $this->query = $query;
        $filterred_data_q = clone $query;
        $this->filterred_data = $filterred_data_q->get()->num_rows();
        return $this;
    }

    private function _get_data(){
        $temp = [];
        
        $data = $this->data;
        if(empty($data)) $data = [];

        foreach($data as $v){
            $is_data_obj = is_object($v);
            $tmp = [];
            foreach($this->header as $key => $value){
                if(isset($value['field']))
                    $tmp[$key] = $is_data_obj ? $v->{$value['field']} : $v[$value['field']];
                else
                    $tmp[$key] = $is_data_obj ? $v->{$key} : $v[$key];
            }
            $temp[] = $is_data_obj ? (object) $tmp : (array) $tmp;
        }

        return $temp;
        
    }

    /**
     * @param String $tipe Array or Object
     */
    function getData( string $tipe = 'object', $enableCache = false){
        if($enableCache)
            $this->query->cache_on();
        $this->data = $this->query->get()->result($tipe);
        if($enableCache)
            $this->query->cache_off();

        $data = $this->_get_data();
        if(!empty($this->resultHandler)){
            $callback = $this->resultHandler;
            $data = $callback($this->data, $data, $this->header, $this->query);
        }

        if($this->reCount){
            $this->all_data = count($data);
            if(empty($keyword))
                $this->filterred_data = count($data);
        }

        $this->reset();
        return (object) array(
            'draw' => $_GET['draw'], // Ini dari datatablenya    
            'recordsTotal' => $this->all_data,    
            'recordsFiltered'=> $this->filterred_data,    
            'data'=> $data
        );
    }

    function setData($data){
        $this->data = $data;
        return $this;
    }

    function setHeader(array $header){
        $this->header = $header;
        return $this;
    }

    /**
     * @param Function $callback function($dataQuery,$dataMap,$header,$query) to handle results
     * @param Array $dataQuer Data from Database
     * @param Array $dataMap Data after compile
     * @param Array $header header for response
     * @param CI_DB_query_builder $query Query sql database
     * @return Function
     */

    function set_resultHandler($callback){
        $this->resultHandler = $callback;
        return function(){
           $this->reCount = true;
        };
    }
    
    /**
     * @param Array $option option for filter/searching, -spesifik = true/false (true use where and false use like, default false), -liketipe = after/before/both (default after)
     */
    function set_search_option($option){
        $this->search_option = $option;
        return $this;
    }

    private function reset(){
        $this->header = null;
        $this->selection = null;
        $this->data = null;
        $this->resultHandler = null;
        $this->keyword = null;
        $this->search_option = null;
        $this->reCount = false;
        $this->isFilterred = false;
        $this->enableCache = false;
    }
}