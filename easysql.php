<?php
include_once ("config.php");

if(
    (
    !isset(config::$esqli)|| //if esqli var not set
    config::$esqli===false //or it is false use classic mysql, UNLESS
    )&& function_exists('mysql_connect') && // mysql is missing (e.g. php7) or
    !class_exists("esql") //esql class already found
    ) {
  class esql{
    //easySQL Mini-API
    
    //flag to set status of eSQLi to disabled.
    public static $esqlienabled=false;
    
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
      self::$db[$link]=mysql_connect($host,$user,$pass,1) or $error=style::error(lang::$sqlerror."(".$link.")") and die ($error); //new link patrameter needed for same behavior becuase forced in MySQLi
      mysql_select_db($db,self::$db[$link]) or $error=style::error('cannot select db '.$db.' on link '.$link) and die ($error);
      mysql_set_charset('utf8',self::$db[$link]);
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
        $q=mysql_query($q,self::$db[$link]);
        if($q===false) { //if query result is empty
          if($debug) {
            echo mysql_error();
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
      return mysql_num_rows($res);
    }
    
    //affected rows (edit/update/insert)
    public static function affrows($link){
      return mysql_affected_rows ($link);
    }
    
    //mysql(i)_info parser for update
    public static function updinfo($link,$data='') {
      $list=mysql_info (self::$db["dates"]);
      $list=str_replace('Rows matched','match',$list);
      preg_match_all ('/(\S[^:]+): (\d+)/', $list, $matches);
      $info = array_combine ($matches[1], $matches[2]);
      //var_dump($info);
      if(!$data) {
        return $info;
      }
      elseif($data="match") {
        return $info["match"];
      }
    }
    
    //fetch row
    public static function frow($res) {
      return mysql_fetch_row($res); 
    }
    
    //fetch one row as array
    public static function farray($res,$style=MYSQLI_BOTH) {
      return mysql_fetch_array($res,$style);
    }
    
    public static function fall ($result, $result_type = MYSQL_BOTH) {
      if (!is_resource($result) || get_resource_type($result) != 'mysql result') {
        trigger_error(__FUNCTION__ . '(): supplied argument is not a valid MySQL result resource', E_USER_WARNING);
        return false;
      }
      if (!in_array($result_type, array(MYSQL_ASSOC, MYSQL_BOTH, MYSQL_NUM), true)) {
        trigger_error(__FUNCTION__ . '(): result type should be MYSQL_NUM, MYSQL_ASSOC, or MYSQL_BOTH', E_USER_WARNING);
        return false;
      }
      $rows = array();
      while ($row = mysql_fetch_array($result, $result_type)) {
        $rows[] = $row;
      }
      return $rows;
    }
    
    //pinpoint result with row and column
    public static function dbres($res,$row=0,$col=0) {
      return mysql_result($res,$row,$col);
      //1st linebreak for sync with esqli.php
      //2nd
    }
    
    //inserted ID
    public static function iid($link) {
      return mysqli_insert_id(self::$db[$link]);
    }
    
    //escape stuff (connection mandatory for compatibility with esqli)
    public static function escape($val,$con) {
      if($con)
        return mysql_real_escape_string($val,self::$db[$con]);
      else
        return false;
    }
    
    //set seek pointer (e.g. for fetch row) to new position
    public static function seek($res,$row) {
      mysql_data_seek($res,$row);
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
else
  include ("esqli.php");
?>