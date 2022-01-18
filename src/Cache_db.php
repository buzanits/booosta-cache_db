<?php
namespace booosta\cache_db;

use \booosta\Framework as b;
b::init_module('cache_db');

class Cache_db extends \booosta\cache\Cache
{
  use moduletrait_cache_db;

  protected $table;

  public function __construct($table = 'cache')
  {
    parent::__construct();
    $this->table = $table;
  }

  public function after_instanciation()
  {
    parent::after_instanciation();
    $this->store = $this->makeInstance("\\booosta\\cache_db\\Cachestore_DB", $this->table);
  }
}


class Cachestore_DB extends \booosta\cache\Cachestore
{
  protected $table;
  protected $valuefield = 'content';
  protected $keyfield = 'ckey';
  protected $timefield = 'dtime';

  public function __construct($table = 'cache')
  {
    parent::__construct();
    $this->table = $table;
  }

  public function getobj($key)
  {
    $key = md5($key);
    $obj = $this->getDataobject($this->table, "`$this->keyfield`='$key'");
    if(is_object($obj)) return $obj->get($this->valuefield);
    return false;
  }

  public function storeobj($key, $data)
  {
    $key = md5($key);
    $obj = $this->getDataobject($this->table, "`$this->keyfield`='$key'", true);
    $obj->set($this->keyfield, $key);
    $obj->set($this->valuefield, $data);
    $obj->set($this->timefield, time());
    return $obj->save();
  }

  public function get_timestamp($key)
  {
    $key = md5($key);
    $obj = $this->getDataobject($this->table, "`$this->keyfield`='$key'");
    if(is_object($obj)) return $obj->get($this->timefield);
    return 0;
  }

  public function invalidate($key)
  {
    $key = md5($key);
    $obj = $this->getDataobject($this->table, "`$this->keyfield`='$key'");
    if(is_object($obj)) return $obj->delete();
    return false;
  }

  public function clear()
  {
    return $this->DB->query("truncate table $this->table");
  }

  public function cleanup()
  {
    $keys = $this->DB->query_value_set("select `$this->keyfield` from `$this->table`");
    foreach($keys as $key)
      if($this->is_invalid($key)) $this->DB->query("delete from `$this->table` where `$this->keyfield`=?", $key);
  }
}
