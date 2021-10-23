<?php
/** 
 * config.inc.php
 * <br/> konfigurasi untuk basis data Institusi, FEEDER PDDIKTI dan lainnya
 * <br/> profil  https://id.linkedin.com/in/basitadhi
 * <br/> buat    2015-10-30
 * <br/> rev     2021-04-23
 * <br/> sifat   open source
 * @author Basit Adhi Prabowo, S.T. <basit@unisayogya.ac.id>
 * @access public
 */

/**
 * WARNING!!!
 * TIdak Boleh Ada Function Pada config.inc.php
 */

define("MODE_SANDBOX", 0);
define("MODE_LIVE", 1);

define("PDDIKTI_FLAG_UNSYNC", 0);
define("PDDIKTI_FLAG_SYNC", 1);
define("PDDIKTI_FLAG_SYNC_UNMATCH", 2);

define("EXECUTION_TIME_LIMIT", 18000); //dalam detik

/* setting basis data institusi */
$institusi["db"]["username"]            = "yourdb_username";
$institusi["db"]["password"]            = "yourdb_password";
$institusi["db"]["port"]                = 3306;
$institusi["db"]["host"]                = "yourdb_host";
$institusi["db"]["database"]            = "yourdb_name";

/* beri komentar jika ssl tidak digunakan */
$institusi["db"]["ssl"]["client-key"]   = "C:/ssl/client-key.pem";
$institusi["db"]["ssl"]["client-cert"]  = "C:/ssl/client-cert.pem";
$institusi["db"]["ssl"]["ca-cert"]      = "C:/ssl/ca.pem";

/* setting webservice PDDIKTI */
$pddikti["ws"]["mode"]          = MODE_LIVE;
$pddikti["ws"]["port"]          = 8082;
$http = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') . '://';
$pddikti["ws"]["host"]          = $http."feeder.unisayogya.ac.id";
$pddikti["ws"]["url"]           = $pddikti["ws"]["host"].":".$pddikti["ws"]["port"]."/ws/".(($pddikti["ws"]["mode"]==MODE_SANDBOX)?"sandbox":"live").".php?wsdl";
$pddikti["ws"]["expire"]        = 1800; /* dalam detik */
/* berapa baris data yang diambil dalam satu waktu */
/* tips: */
/* 1. Jangan dibuat terlalu sedikit, karena akan memunculkan banyak proses koneksi ke webservice yang akan memperlambat proses */
/* 2. Jangan dibuat terlalu banyak, karena akan mengakibatkan error berkaitan dengan batasan memory yang diperbolehkan digunakan pada server */
$pddikti["ws"]["limit"]         = 1000; 

/* setting login PDDIKTI */
$pddikti["login"]["username"]   = "your_feederusername";
$pddikti["login"]["password"]   = "your_feederpassword";

/**
 * WARNING!!!
 * TIdak Boleh Ada Function Pada config.inc.php
 */
