<?php
include ("config.php");
if( !class_exists("esql") ) {
  /*
  linebreaks for sync with easysql.php
  
  
  
  
  */
  class esql{
    //easySQLi (eSQLi) Mini-API
    
    //flag to set status of eSQLi to enabled.
    public static $esqlienabled=true;
    
    //array for keeping connections
    private static $db=array();
    
    //connect and select db
    public static function dbc($link,$db=NULL,$user=NULL,$pass=NULL,$host=NULL,$debug=false){ //connect
      if($db===NULL) {
        $db=config::$dbname;
      }
      if($user===NULL) {
        $user=config::$dbuser;
      }
      if($host===NULL) {
        $host=config::$dbhost;
      }
      if($pass===NULL) {
        $pass=config::$dbpass;
      }
      self::$db[$link]=mysqli_connect($host,$user,$pass) or $error=style::error(lang::$sqlerror."(".$link.")") and die ($error); //new link not needed because already enforced
      mysqli_select_db(self::$db[$link],$db) or $error=style::error('cannot select db '.$db.' on link '.$link) and die ($error);
      mysqli_set_charset(self::$db[$link],'utf8');
      if($debug) {
        echo "opened ".$link."<br>";
      }
    }
    
    //query
    public static function dbq($link,$action, $col/*leave empty for delete, set param for update,cols für insert*/,
                                $table,$filter=""/*filter includes sort and group nofilter is 1 -> values bei insert*/,
                                $debug=false){
      $q="";
      if($col != "*"&&$action!="update") {
        $cols=explode(",",$col);
        foreach($cols as &$c) {
          if(preg_match('/^(count|min|max)\(.+\)$/',$c)){
             if($debug) echo "function escape";
            $c=preg_replace('/(count|min|max)\(([^*]+)\)/','$1(`$2`)',$c);
          }
          else {
            if(preg_match('/^[A-Za-z_$]+$/',$c)) {
              $c=trim($c,"`");
              $c="`".$c."`";
            }
          }
        }
        $col=implode(",",$cols);
      }
      //trim, prefix and re-escape
      $table=str_replace("`","",$table);
      $tbls=explode(",",$table);
      foreach($tbls as &$t) {
        $t=config::$prefix.$t;
        $t="`".$t."`";
      }
       $table=implode(",",$tbls);
      //arrange SQL command based on action
      if($action=="select"){
        if($filter)
          $q="select ".$col." from ".$table." where ".$filter;
        else
          $q="select ".$col." from ".$table;
      }
      if($action=="update"&&$filter)
        $q="update ".$table." set ".$col." where ".$filter;
      if($action=="delete"&&$filter)
        $q="delete from ".$table." where ".$filter;
      if($action=="insert")
        $q='insert into '.$table.' '.'('.$col.') values ('.$filter.')';
      if($debug) 
        echo $q."<br>"; // -> debug
      if($q) {
        $q=mysqli_query(self::$db[$link],$q);
        if($q===false) { //if query result is empty
          if($debug) {
            echo mysqli_error(self::$db[$link]);
            echo "<br>";
            var_dump(self::$db);
            echo "<br>";
            die ("cannot ".$action." in db (".$link.")");
          }
          else{
            echo(style::w("Datenbakfehler. ".$action." in handle ".$link));
          }
        }
      }
      return $q;
    }
    
    //num rows
    public static function num($res) {
      return mysqli_num_rows($res);
    }
    
    //affected rows (edit/update/insert)
    public static function affrows($link){
      return mysqli_affected_rows (self::$db[$link]);
    }
    
    //mysql(i)_info parser for update
    public static function updinfo($link,$data='') {
      $list=mysqli_info (self::$db[$link]);
      $list=str_replace('Rows matched','match',$list);
      preg_match_all ('/(\S[^:]+): (\d+)/', $list, $matches);
      $info = array_combine ($matches[1], $matches[2]);
      var_dump($info);
      if(!$data) {
        return $info;
      }
      elseif($data="match") {
        return $info["match"];
      }
    }
    
    //fetch row
    public static function frow($res) {
      return mysqli_fetch_row($res);
    }
    
    //fetch one row as array
    public static function farray($res,$style=MYSQLI_BOTH) {
      return mysqli_fetch_array($res,$style);
    }
    
    //fetch all rows in one array
    public static function fall($res,$style=MYSQLI_NUM) {
      return mysqli_fetch_all ($res,$style);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    //pinpoint result with row and column
    public static function dbres($res,$row=0,$col=0) {
      mysqli_data_seek($res,$row);
      $datarow=mysqli_fetch_array($res);
      return $datarow[$col];
    }
    
    //inserted ID
    public static function iid($link) {
      return mysqli_insert_id(self::$db[$link]);
    }
    
    //escape stuff
    public static function escape($val,$con) {
      if($con)
        return mysqli_real_escape_string(self::$db[$con],$val);
      else
        return false;
    }
    
    //set seek pointer (e.g. for fetch row) to new position
    public static function seek($res,$row) {
      mysqli_data_seek($res,$row);
    }
    
    //close the connection
    public static function dbclose($link="",$debug=false) {
      if($link) {
        if(mysqli_ping(self::$db[$link])) {
          mysqli_close(self::$db[$link]);
          if($debug) {
            echo "closed ".$link."<br>";
          }
        }
      }
    }
  }
}
//linebreaks to sync
//with easySQLn (normal mysql_* implementation)
?>
