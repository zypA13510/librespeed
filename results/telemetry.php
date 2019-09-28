<?php
include_once('telemetry_settings.php');
require 'idObfuscation.php';

$ip=($_SERVER['REMOTE_ADDR']);
$ispinfo=($_POST["ispinfo"]);
$extra=($_POST["extra"]);
$ua=($_SERVER['HTTP_USER_AGENT']);
$lang=""; if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $lang=($_SERVER['HTTP_ACCEPT_LANGUAGE']);
$dl=($_POST["dl"]);
$ul=($_POST["ul"]);
$ping=($_POST["ping"]);
$jitter=($_POST["jitter"]);
$log=($_POST["log"]);

if($redact_ip_addresses){
    $ip="0.0.0.0";
    $ispinfo=preg_replace('/((2[0-4]|1\d|[1-9])?\d|25[0-5])(\.(?1)){3}/',"0.0.0.0",preg_replace('/(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|((2[0-4]|1\d|[1-9])?\d|25[0-5])(\.(?7)){3})/i',"0.0.0.0",$ispinfo));
    $log=preg_replace('/((2[0-4]|1\d|[1-9])?\d|25[0-5])(\.(?1)){3}/',"0.0.0.0",preg_replace('/(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|((2[0-4]|1\d|[1-9])?\d|25[0-5])(\.(?7)){3})/i',"0.0.0.0",$log));
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if($db_type=="mysql"){
    $conn = new mysqli($MySql_hostname, $MySql_username, $MySql_password, $MySql_databasename) or die("1");
    $stmt = $conn->prepare("INSERT INTO speedtest_users (ip,ispinfo,extra,ua,lang,dl,ul,ping,jitter,log) VALUES (?,?,?,?,?,?,?,?,?,?)") or die("2");
    $stmt->bind_param("ssssssssss",$ip,$ispinfo,$extra,$ua,$lang,$dl,$ul,$ping,$jitter,$log) or die("3");
	$stmt->execute() or die("4");
    $stmt->close() or die("5");
	$id=$conn->insert_id;
	echo "id ".($enable_id_obfuscation?obfuscateId($id):$id);
    $conn->close() or die("6");

}elseif($db_type=="sqlite"){
    $conn = new PDO("sqlite:$Sqlite_db_file") or die("1");
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `speedtest_users` (
        `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		`ispinfo`    text,
		`extra`    text,
        `timestamp`     timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ip`    text NOT NULL,
        `ua`    text NOT NULL,
        `lang`  text NOT NULL,
        `dl`    text,
        `ul`    text,
        `ping`  text,
        `jitter`        text,
        `log`   longtext
        );
    ");
    $stmt = $conn->prepare("INSERT INTO speedtest_users (ip,ispinfo,extra,ua,lang,dl,ul,ping,jitter,log) VALUES (?,?,?,?,?,?,?,?,?,?)") or die("2");
    $stmt->execute(array($ip,$ispinfo,$extra,$ua,$lang,$dl,$ul,$ping,$jitter,$log)) or die("3");
	$id=$conn->lastInsertId();
	echo "id ".($enable_id_obfuscation?obfuscateId($id):$id);
    $conn = null;
}elseif($db_type=="postgresql"){
    // Prepare connection parameters for db connection
    $conn_host = "host=$PostgreSql_hostname";
    $conn_db = "dbname=$PostgreSql_databasename";
    $conn_user = "user=$PostgreSql_username";
    $conn_password = "password=$PostgreSql_password";
    // Create db connection
    $conn = new PDO("pgsql:$conn_host;$conn_db;$conn_user;$conn_password") or die("1");
    $stmt = $conn->prepare("INSERT INTO speedtest_users (ip,ispinfo,extra,ua,lang,dl,ul,ping,jitter,log) VALUES (?,?,?,?,?,?,?,?,?,?)") or die("2");
    $stmt->execute(array($ip,$ispinfo,$extra,$ua,$lang,$dl,$ul,$ping,$jitter,$log)) or die("3");
	$id=$conn->lastInsertId();
	echo "id ".($enable_id_obfuscation?obfuscateId($id):$id);
    $conn = null;
}
else die("-1");
?>
