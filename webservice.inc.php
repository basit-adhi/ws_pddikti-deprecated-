<?php
//tes 
/* mode inject */
define("MODE_INJECT_INDIVIDU", 1);
define("MODE_INJECT_MASSAL", 2);
define("MODE_INJECT_GAGAL", 3);
    
require_once ("lib/nusoap.php");
require_once ("lib/class.wsdlcache.php");

/** 
 * webservice.inc.php
 * <br/> kelas webservice: untuk mengambil data dari PDDIKTI atau menyimpan data ke PDDIKTI, disinkronkan dengan database institusi
 * <br/> profil  https://id.linkedin.com/in/basitadhi
 * <br/> buat    2015-10-30
 * <br/> rev     2021-09-05
 * <br/> sifat   open source
 * @author Basit Adhi Prabowo, S.T. <basit@unisayogya.ac.id>
 * @access public
 */
class webservice
{
    /**
     * proxy koneksi webservice PDDIKTI 
     */
    var $proxy;       
    /**
     * setting PDDIKTI 
     */
    var $pddikti;
    /**
     * setting institusi 
     */
    var $institusi;   
    /**
     * waktu saat ini 
     */
    var $sekarang;     
    /**
     * mode debug 
     */
    var $debug;    
    /**
     * status webservice PDDIKTI 
     */
    var $status; 
    /**
     * pemetaan field basis data institusi dengan PDDIKTI (mapping.inc.php)
     */
    var $mapdb;           
    /**
     * basis data institusi 
     */
    var $db;           
    /**
     * jumlah baris pada tabel 
     */
    var $nbaris_tabel;    
    /**
     * apakah perlu memanggil fungsi pddikti_sinkron_guid()? mengingat sudah ada proses sinkronisasi bersamaan dengan data diinjek 
     */
    var $issinkron_injek;
    /**
     * apakah cek penugasan sudah dilakukan? jika sudah, maka tidak perlu melakukan cek lagi untuk injek berikutnya
     */
    var $issudahcekpenugasan;
    /**
     * apakah pencatatan eksekusi ke basis data dilakukan?
     */
    var $log = array();
    /**
     * pencatatan eksekusi tidak dilakukan meskipun islog=true
     */
    var $ignorelog = true;
    var $reuseable_iddb = array();
    var $token_ = "";
    
/*vv koneksi WEBSERVICE PDDIKTI vv*/

    /**
     * konstruktor kelas webservice, dieksekusi ketika objek dibuat
     * @param type $pddikti
     * - setting webservice PDDIKTI (config.ini.php)
     * @param type $institusi
     * - setting basis data institusi (config.ini.php)
     * @param type $debug jika ya, maka akan lebih banyak pesan yang akan ditampilkan
     * - mode debug?
     * @param type $islog jika ya, maka eksekusi akan dicatat ke database
     * - mode debug?
     */
    function webservice($pddikti, $institusi, $debug=false, $islog=false)
    {
        /* mulai buffer output, untuk menghemat memory */
        ob_start();
        /* ubah setting maksimal waktu tunggu eksekusi */
        set_time_limit(EXECUTION_TIME_LIMIT);
        /* awalan */
        $this->pddikti              = $pddikti;
        $this->institusi            = $institusi;
        $this->debug                = $debug;
        $this->log                  = ($islog) ? ["islog" => true, "ideksekusi" => $this->generaterandom(15), "url" => $this->get_url(), "waktumulai" => date('Y-m-d H:i:s')] : ["islog" => false];
        if ($islog)
        {
            echo "ID Eksekusi: ".$this->log["ideksekusi"]."<br/>";
        }
        $this->status               = array("status" => true, "pesankesalahan" => "");
        $this->issudahcekpenugasan  = array();
        /* tampilkan pesan debug apabila dalam mode debug */
        if ($this->debug)
        {
          echo $this->mode();
          $this->print_r_rapi($this->pddikti);
        }
        /* persiapan proses */
        $this->persiapan();
        /* memeriksa status webservice PDDIKTI */
        $this->periksa();
        $this->issinkron_injek = true;
        $this->awalaccordion();
    }

    /**
     * skrip untuk aksi terakhir kelas webservice, letakkan di ujung skrip pemanggil kelas webservice
     */
    function akhirwebservice()
    {
        foreach ($this->reuseable_iddb as $iddb)
        {
            $this->db["conn"][$iddb]->close();
        }
    }

    /**
     * destruktor kelas webservice, dieksekusi ketika objek dihancurkan
     */
    function __destruct()
    {
        /* tampilkan pesan debug apabila dalam mode debug */
        if ($this->debug)
        {
            echo "Bersih-bersih";
        }
        /* mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer */
        $this->kirim_buffer();
        /* bersih-bersih */
        unset($this->proxy);
        unset($this->pddikti);
        unset($this->institusi);
        unset($this->now);
        unset($this->debug);
        unset($this->status);
        unset($this->mapdb);
        unset($this->db);
        /* mengakhiri buffer output */
        ob_end_clean();
    }

    /**
     * pembuka untuk fungsi accordion
     */
    function awalaccordion()
    {
        echo  "";
    }

    /**
     * mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer
     */
    function kirim_buffer()
    {
        ob_flush();
        flush();
        if (!$this->ignorelog && $this->log["islog"] && !empty($this->mapdb))
        {
            $iddb = $this->mysqli_terhubung();
            $this->mysqli_iud($iddb, "INSERT INTO ".$this->mapdb["log"]["table"]."(".$this->mapdb["log"]["field"]["ideksekusi"].",".$this->mapdb["log"]["field"]["url"].",".$this->mapdb["log"]["field"]["waktumulai"].",".$this->mapdb["log"]["field"]["waktuterakhir"].") values ('".$this->log["ideksekusi"]."','".$this->log["url"]."','".$this->log["waktumulai"]."','".date('Y-m-d H:i:s')."') ON DUPLICATE KEY UPDATE ".$this->mapdb["log"]["field"]["waktuterakhir"]."='".date('Y-m-d H:i:s')."'");
            $this->mysqli_bersihkan($iddb);
            $this->mysqli_putus($iddb);
        }
    }

    /**
     *  persiapan proses 
     */
    function persiapan()
    {
        /* koneksi ke webservice PDDIKTI */
        $this->terhubung_proxy();
        /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
        $this->penokenan();
    }

    /**
     * memeriksa apakah server error atau tidak.
     * <br/> sumber: http://www.thecave.info/php-ping-script-to-check-remote-server-or-website/
     * @param type $host
     * - alamat host yang akan di ping
     * @param type $port
     * - port yang akan di ping - OPTIONAL, default: 80
     * @param type $waktutunggu
     * - waktu yang diberikan untuk menandai bahwa server error (dalam detik) - OPSIONAL, default: 6
     * @return boolean
     * - true: terkoneksi, false: error
     */
    function ping($host,$port=80,$waktutunggu=6)
    {
        $errno  = 0;
        $errstr = "";
        $http = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') . '://';
        /* tidak diperbolehkan ada http:// pada host, sehingga perlu dihilangkan (jika ada) */
        $fsock = fsockopen(str_replace($http, "", $host), $port, $errno, $errstr, $waktutunggu);
        /* jika koneksi error */
        if ( ! $fsock )
        {
          fclose($fsock);
          return false;
        }
        /* terkoneksi */
        else
        {
          fclose($fsock);
          return true;
        }
    }

    /**
     *  koneksi ke webservice PDDIKTI
     */
    function terhubung_proxy()
    {
        /* melakukan koneksi webservice PDDIKTI sesuai dengan setting pada config.ini.php */
        $klien = new nusoap_client($this->pddikti["ws"]["url"], true);
        /* apabila server PDDIKTI mati, maka akan muncul pesan error dan tidak bisa melakukan aksi apapun */
        if (!$this->ping($this->pddikti["ws"]["host"], $this->pddikti["ws"]["port"]))
        {
            $this->status = array("status" => false, "pesankesalahan" => "Terjadi kegagalan koneksi. Apakah server FEEDER DIKTI mati?");
        }
        /* simpan informasi koneksi */
        else
        {
            $this->pddikti["proxy"] = $klien->getProxy();
        }
        /* bersih-bersih */
        unset($klien);
    }

    /**
      * menampilkan mode FEEDER PDDIKTI ke browser
      * @global array $pddikti
      * - setting koneksi PDDIKTI
      * @return type
      * - mode
      */
    function mode()
    {
        return "Mode saat ini adalah ".(($this->pddikti["ws"]["mode"]==MODE_SANDBOX)?"sandbox (percobaan)":"live (langsung)")."<br />";
    }

    /**
     *  menjamin keberadaan token, meminta token kembali apabila sudah expire
     */
    function penokenan()
    {
        if ($this->status_periksa())
        {
            session_start();
            /* set waktu saat ini */
            $this->now = date('Y-m-d H:i:s');
            /* jika sesi belum terbentuk atau sudah melewati masa expire */
            if ($_SESSION["expire"] == "" || $_SESSION["expire"] < $this->now)
            {
                /* token digenerate ulang jika sudah melewati masa expire */
                if ($_SESSION["expire"] < $this->now)
                {
                    /* mendapatkan token webservice PDDIKTI */
                    $this->token_       = $this->token();
                    $_SESSION["token"]  = $this->token_;
                }
                /* menentukan waktu untuk generate ulang token, pengaman 2 menit */
                $_SESSION["expire"] = date('Y-m-d H:i:s', strtotime($this->now) + $this->pddikti["ws"]["expire"] - 120);
            }
            else
            {
                $this->token_ = $_SESSION["token"];
            }
            /* tampilkan pesan debug apabila dalam mode debug */
            if ($this->debug) 
            {
                $this->print_r_rapi($_SESSION);
            }
            session_write_close();
         }
    }

    /**
     * mendapatkan token webservice PDDIKTI
     * @return type
     * - token PDDIKTI
     */
    function token()
    {
        /* token */
        return $this->pddikti["proxy"]->getToken($this->pddikti["login"]["username"], $this->pddikti["login"]["password"]);
    }

    /**
     * memeriksa status koneksi FEEDER DIKTI
     * @return type
     * - true: terkoneksi, false: error
     */
    function status_periksa()
    {
        if (!$this->status["status"])
        {
            echo "<br />".$this->status["pesankesalahan"];
        }
        return $this->status["status"];
    }

    /**
     * memeriksa status webservice PDDIKTI
     */
    function periksa()
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* mendapatkan versi dari webservice PDDIKTI (dan pesan error, jika ada) */
            $hasil       = $this->GetVersion();
            /* memberikan status (flag) dan pesan error (message) */
            $this->status = (($hasil["error_code"] == 104)?array("status" => false, "pesankesalahan" => "Webservice sudah expired. Pastikan server tidak di belakang Proxy, selanjutnya ikuti petunjuk yang ada pada manual FEEDER DIKTI"):array("status" => true, "pesankesalahan" => ""));
            /* jika status gagal, maka nilai sesi expire dibuat masa lampau */
            if (!$this->status_periksa())
            {
                session_start();
                $_SESSION["expire"]  = '2010-01-01 00:00:00';
                session_write_close();
            }
            /* bersih-bersih */
            unset($hasil);
            /* mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer */
            $this->kirim_buffer();
        }
    }

    /**
     * set nilai pada mapdb (mapping.inc.php)
     * @param type $mapdb
     * - mapdb pada mapping.inc.php
     */
    function setMapdb($mapdb)
    {
        $this->mapdb  = $mapdb;
        /* tampilkan pesan debug apabila dalam mode debug */
        if ($this->debug)
        {
            $this->print_r_rapi($this->mapdb);
        }
    }

    /**
     * set nilai untuk issinkron_injek
     * @param type $issinkron_injek
     * - apakah perlu memanggil fungsi pddikti_sinkron_guid()? mengingat sudah ada proses sinkronisasi bersamaan dengan data diinjek
     */
    function setIssinkron_injek($issinkron_injek)
    {
        $this->issinkron_injek = $issinkron_injek;
    }

/*^^ koneksi WEBSERVICE PDDIKTI ^^*/

/*vv WEBSERVICE PDDIKTI vv*/

    /**
     * mendapatkan deskripsi tabel dari webservice PDDIKTI (equal: desc [table]) yang terdaftar di dalam ListTable()
     */
    function GetDictionary_SemuaTabel()
    {
        $this->GetChangeLog();
        /* mendapatkan daftar tabel dari webservice PDDIKTI (equal: show [table]) */
        $daftarTabel  = $this->ListTable();
        /* mencetak daftar tabel */
        echo "<h1>Daftar Tabel</h1>";
        /* memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri) */
        $this->cetak_tabel_parsial_mulai();
        /* mencetak header, diambil dari indeks */
        $this->cetak_tabel_parsial_indeks($daftarTabel["result"], 1, false, true);
        /* mencetak daftar tabel */
        foreach ($daftarTabel["result"] as $idx => $data)
        {
            $this->cetak_tabel_parsial($data);
        }
        /* menutup tabel terpisah */
        $this->cetak_tabel_parsial_akhiri();
        /* mencetak deskripsi tabel */
        reset($daftarTabel["result"]);
        foreach ($daftarTabel["result"] as $idx => $data)
        {
            $this->cetak_accordion_mulai("Deskripsi Tabel ".$data["table"]);
            /* ambil data deskripsi per tabel */
            $deskripsiTabel  = $this->GetDictionary($data["table"]);
            /* menyamakan kolom data tabel */
            $deskripsiTabel["result"]  = $this->array_auto_fill($deskripsiTabel["result"]);
            /* memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri) */
            $this->cetak_tabel_parsial_mulai();
            /* mencetak header, diambil dari indeks */
            $this->cetak_tabel_parsial_indeks($deskripsiTabel["result"], 1, false, true);
            /* mencetak deskripsi tabel */
            foreach ($deskripsiTabel["result"] as $data_)
            {
                $this->cetak_tabel_parsial($data_);
            }
            /* menutup tabel terpisah */
            $this->cetak_tabel_parsial_akhiri();
            $this->cetak_accordion_akhiri();
            /**/
            unset($deskripsiTabel);
        }
        /* bersih-bersih */
        unset($daftarTabel);
    }

    /**
     * mendapatkan daftar tabel dari webservice PDDIKTI (equal: show [table])
     * @return type
     * - daftar tabel
     */
    function ListTable()
    {
        /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
        $this->penokenan();
        /* daftar tabel */
        return $this->pddikti["proxy"]->ListTable($this->token_);
    }

    /**
     * mendapatkan deskripsi tabel dari webservice PDDIKTI (equal: desc [table])
     * @param type $tabel
     * - tabel yang akan dilihat deskripsinya
     * @return type
     * - deskripsi tabel
     */
    function GetDictionary($tabel)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* deskripsi tabel */
            return $this->pddikti["proxy"]->GetDictionary($this->token_, $tabel);
        }
    }

    /**
     * mendapatkan satu baris data dari webservice PDDIKTI (equal: select * from [table] where [filter] limit 0, 1). 
     * <br/> catatan: jika tidak muncul, tambahkan alias pada field di filter, misal: p.field atau gunakan nama_tabel.raw
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter data yang akan diambil - OPSIONAL, default: ""
     * @return type
     * - satu baris data
     */
    function GetRecord($tabel, $filter="")
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* satu baris data */
            return $this->pddikti["proxy"]->GetRecord($this->token_, $tabel, $filter);
        }
    }

    /**
     * mendapatkan n baris data dari webservice PDDIKTI (equal: select * from [table] where [filter] limit [offset], [limit]). 
     * <br/> catatan: jika tidak muncul, tambahkan alias pada field di filter, misal: p.field atau gunakan nama_tabel.raw
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter data yang akan diambil - OPSIONAL, default: ""
     * @param type $order
     * - pengurutan data - OPSIONAL, default: ""
     * @param type $batas
     * - banyaknya data yang akan ditampilkan - OPSIONAL, default: 1000000
     * @param type $mulai
     * - dari nomor berapa data akan diambil - OPSIONAL, default: 0
     * @return type
     * - data
     */
    function GetRecordset($tabel, $filter="", $order="", $batas=1000000, $mulai=0)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* data */
            return $this->pddikti["proxy"]->GetRecordset($this->token_, $tabel, $filter, $order, $batas, $mulai);
        }
    }

    /**
     * mendapatkan informasi berapa baris data pada tabel dari webservice PDDIKTI (equal: select sum(1) as jumlah from [table])
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @return type
     * - jumlah baris data
     */
    function GetCountRecordset($tabel)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /*jumlah baris data */
            return $this->pddikti["proxy"]->GetCountRecordset($this->token_, $tabel);
        }
    }

    /**
     * mendapatkan data yang telah dihapus pada suatu tabel dari webservice PDDIKTI (equal: select * from [table] where [filter] order by [order] limit [offset], [limit])
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter data yang akan diambil - OPSIONAL, default: ""
     * @param type $order
     * - pengurutan data - OPSIONAL, default: ""
     * @param type $batas
     * - banyaknya data yang akan ditampilkan - OPSIONAL, default: 1000000
     * @param type $mulai
     * - dari nomor berapa data akan diambil - OPSIONAL, default: 0
     * @return type
     * - data
     */
    function GetDeletedRecordset($tabel, $filter, $order, $batas=1000000, $mulai=0)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* data */
            return $this->pddikti["proxy"]->GetDeletedRecordset($this->token_, $tabel, $filter, $order, $batas, $mulai);
        }
    }

    /**
     * mendapatkan informasi berapa baris data yang telah dihapus pada tabel dari webservice PDDIKTI (equal: equal: select sum(1) as jumlah from [table])
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @return type
     * - jumlah baris data
     */
    function GetCountDeletedRecordset($tabel)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* jumlah baris data */
            return $this->pddikti["proxy"]->GetCountDeletedRecordset($this->token_, $tabel);
        }
    }

    /**
     * menyisipkan satu baris data ke dalam tabel dari webservice PDDIKTI (equal: insert into [table] (<namakolom>) values (<data>))
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $data
     * - data berupa array 1 dimensi yang indeksnya adalah nama kolom dan isinya adalah data. $sisip = array( "namakolom1" => "data1", ... )
     * @return type
     * - status penyisipan data
     */
    function InsertRecord($tabel, $data)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* sisip data */
            return $this->pddikti["proxy"]->InsertRecord($this->token_, $tabel, json_encode($data, JSON_FORCE_OBJECT));
        }
    }

    /**
     * menyisipkan n baris data ke dalam tabel dari webservice PDDIKTI (equal: insert into [table] (<namakolom>) values (<data1>), (<data2>), ...)
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $data
     * - data berupa array 2 dimensi yang indeksnya adalah nama kolom dan isinya adalah data. $sisip = array ( array( "namakolom1" => "data1.1", ... ), ... )
     * @return type
     * - status penyisipan data
     */
    function InsertRecordset($tabel, $data)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* sisip data */
            return $this->pddikti["proxy"]->InsertRecordset($this->token_, $tabel, json_encode($data, JSON_FORCE_OBJECT));
        }
    }

    /**
     * mengubah satu baris data di dalam tabel dari webservice PDDIKTI (equal: update [table] set <namakolomdata:data> where <namakolomfilter:datafilter>)
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $data
     * - data harus berupa array 2 dimensi dengan indeks bernama 'key' dan 'data', yang masing-masing berupa array yang indeksnya adalah nama kolom dan isinya adalah data.
     *   $ubah = array( "key" => array( "namakolomfilter1" => "datafilter1", ... ), "data" => array( "namakolomdata1" => "data1", ... ) )
     * @return type
     * - status ubah data
     */
    function UpdateRecord($tabel, $data)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* ubah data */
            return $this->pddikti["proxy"]->UpdateRecord($this->token_, $tabel, json_encode($data, JSON_FORCE_OBJECT));
        }
    }

    /**
     * mengubah n baris data di dalam tabel dari webservice PDDIKTI (equal: update [table] set <namakolomdata1:data1>, <namakolomdata2:data2>, ... where <namakolomfilter:datafilter>)
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $data
     * - data harus berupa array 3 dimensi dengan sebuah array berupa kumpulan array dengan indeks bernama 'key' dan 'data', yang masing-masing berupa array yang indeksnya adalah nama field dan isinya adalah data.
     *   $ubah = array( "key" => array( "namakolomfilter1" => "datafilter1", ... ), "data" => array( "namakolomdata1.1" => "data1.1", ... ), ... )
     * @return type
     * - status ubah data
     */
    function UpdateRecordset($tabel, $data)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* ubah data */
            return $this->pddikti["proxy"]->UpdateRecordset($this->token_, $tabel, json_encode($data, JSON_FORCE_OBJECT));
        }
    }

    /**
     * menghapus satu baris data secara halus (memberi tanda) di dalam tabel dari webservice PDDIKTI (equal: update <tabel> set soft_delete=1 where <namakolomfilter:datafilter>)
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter berupa array yang indeksnya adalah nama field dan isinya adalah data.
     *   $hapus = array( "namakolomfilter1" => "datafilter1", ... )
     * @return type
     * - status hapus data
     */
    function DeleteRecord($tabel, $filter)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* hapus data */
            return $this->pddikti["proxy"]->DeleteRecord($this->token_, $tabel, json_encode($filter, JSON_FORCE_OBJECT));
        }
    }

    /**
     * menghapus n baris data secara halus (memberi tanda) di dalam tabel dari webservice PDDIKTI (equal: update <tabel> set soft_delete=1 where <namakolomfilter1:datafilter1>; update <tabel> set soft_delete=1 where <namakolomfilter2:datafilter2>;... )
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter berupa array yang indeksnya adalah nama field dan isinya adalah data.
     *   $hapus = array ( array( "namakolomfilter1" => "datafilter1", ... ), ... )
     * @return type
     * - status hapus data
     */
    function DeleteRecordset($tabel, $filter)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* hapus data */
            return $this->pddikti["proxy"]->DeleteRecordset($this->token_, $tabel, json_encode($filter, JSON_FORCE_OBJECT));
        }
    }

    /**
     * mengembalikan satu baris data yang telah dihapus secara halus (memberi tanda) di dalam tabel dari webservice PDDIKTI (equal: update <tabel> set soft_delete=0 where <namakolomfilter:datafilter>)
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter berupa array yang indeksnya adalah nama field dan isinya adalah data.
     *   $hapus = array( "namakolomfilter1" => "datafilter1", ... )
     * @return type
     * - status mengembalikan data
     */
    function RestoreRecord($tabel, $filter)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* mengembalikan data */
            return $this->pddikti["proxy"]->RestoreRecord($this->token_, $tabel, json_encode($filter, JSON_FORCE_OBJECT));
        }
    }

    /**
     * mengembalikan n baris data yang telah dihapus secara halus (memberi tanda) di dalam tabel dari webservice PDDIKTI (equal: update <tabel> set soft_delete=1 where <namakolomfilter1:datafilter1>; update <tabel> set soft_delete=1 where <namakolomfilter2:datafilter2>;... )
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter berupa array yang indeksnya adalah nama field dan isinya adalah data.
     *   $hapus = array ( array( "namakolomfilter1" => "datafilter1", ... ), ... )
     * @return type
     * - status mengembalikan data
     */
    function RestoreRecordset($tabel, $filter)
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            /* menjamin keberadaan token, meminta token kembali apabila sudah expire */
            $this->penokenan();
            /* mengembalikan data */
            return $this->pddikti["proxy"]->RestoreRecordset($this->token_, $tabel, json_encode($filter, JSON_FORCE_OBJECT));
        }
    }

    /**
     * mendapatkan versi dari webservice PDDIKTI (dan pesan error, jika ada)
     * @return type
     * - mengembalikan array berisi data versi, disertai dengan informasi error apabila ada masalah dengan koneksi webservice
     */
    function GetVersion()
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa()) 
        {
            return $this->pddikti["proxy"]->GetVersion($this->token_);
        }
    }

    /**
     * mendapatkan waktu kadaluarsa dari webservice PDDIKTI
     * @return type
     * - mendapatkan status expired
     */
    function GetExpired()
    {
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            return $this->pddikti["proxy"]->GetExpired($this->token_);
        }
    }
    
    /**
     * mencetak daftar perubahan/penambahan dari webservice PDDIKTI
     */
    function GetChangeLog()
    {
        $this->ListWSMethod();
        /* jika koneksi FEEDER DIKTI tidak bermasalah */
        if ($this->status_periksa())
        {
            echo "<h1>Daftar Perubahan Web Service</h1><pre>".$this->pddikti["proxy"]->GetChangeLog($this->token_)["result"]."</pre>";
        }
    }
    
    /**
     * menampilkan daftar method webservice PDDIKTI (di dalam frame)
     */
    function ListWSMethod()
    {
        echo "<iframe width='100%' height='100%' src='".str_replace("?wsdl", "", $this->pddikti["ws"]["url"])."'> </iframe>";
    }
    
/*^^ WEBSERVICE PDDIKTI ^^*/

/*vv manipulasi DB - WEBSERVICE PDDIKTI vv*/

    /**
     * koneksi basis data institusi
     * @return type
     * - id koneksi
     */
    function mysqli_terhubung()
    {
        if (sizeof($this->reuseable_iddb) > 0)
        {
            return array_pop($this->reuseable_iddb);
        }
        else
        {
            /* membuat angka random sebagai id koneksi */
            $iddb = rand();
            /* membuat dan menyimpan koneksi */
            if (array_key_exists("client-key", $this->institusi["db"]["ssl"]) && array_key_exists("client-cert", $this->institusi["db"]["ssl"]) && array_key_exists("ca-cert", $this->institusi["db"]["ssl"]))
            {
                $this->db["conn"][$iddb] = new mysqli($this->institusi["db"]["host"], $this->institusi["db"]["username"], $this->institusi["db"]["password"]);
                $this->db["conn"][$iddb]->init();
                $this->db["conn"][$iddb]->options(MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT, true);
                $this->db["conn"][$iddb]->ssl_set($this->institusi["db"]["ssl"]["client-key"], $this->institusi["db"]["ssl"]["client-cert"], $this->institusi["db"]["ssl"]["ca-cert"], NULL, NULL);
                $this->db["conn"][$iddb]->real_connect($this->institusi["db"]["host"], $this->institusi["db"]["username"], $this->institusi["db"]["password"], $this->institusi["db"]["database"], 3306, NULL, MYSQLI_CLIENT_SSL);
                $oResults = $this->db["conn"][$iddb]->query("SHOW SESSION STATUS LIKE '%ssl_version%'", 1);
                $aResults = $oResults->fetch_array(MYSQLI_NUM);
                $oResults->close();
                echo "<br/>Koneksi ke basis data Institusi dengan Enkripsi (SSL): ".$aResults[1]."</br>";
            }
            else
            {
                $this->db["conn"][$iddb] = new mysqli($this->institusi["db"]["host"], $this->institusi["db"]["username"], $this->institusi["db"]["password"], $this->institusi["db"]["database"]);
                echo "<br/>Koneksi ke basis data Institusi tidak di-Enkripsi<br/>";
            } 
            $this->db["conn"][$iddb]->query("SET SESSION sql_mode='NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $oResults = $this->db["conn"][$iddb]->query("SHOW variables LIKE 'sql_mode'", 1);
            $aResults = $oResults->fetch_array(MYSQLI_NUM);
            $oResults->close();
            if ($this->db["conn"][$iddb]->connect_errno > 0 || $this->db["conn"][$iddb]->connect_error)
            {
                die("Koneksi ke basis data Institusi gagal: Err No ".$this->db["conn"][$iddb]->connect_errno." -- ".$this->db["conn"][$iddb]->connect_error);
            }
            else
            {
                echo "<br/>SQL_MODE: ".$aResults[1]."</br>";
            }
        }
        return $iddb;
    }

    /**
     * memutus koneksi basis data institusi
     * @param type $iddb
     * - id koneksi
     */
    function mysqli_putus($iddb, $final = false)
    {
        if ($final)
        {
            $this->db["conn"][$iddb]->close();
        }
        else
        {
            array_push($this->reuseable_iddb, $iddb);
        }
    }

    /**
     * membersihkan hasil
     * @param type $iddb
     * - id koneksi
     */
    function mysqli_bersihkan($iddb)
    {
        if (array_key_exists("result", $this->db))
        {
            if (array_key_exists($iddb, $this->db["result"]))
            {
                if (is_object($this->db["result"][$iddb]))
                {
                    $this->db["result"][$iddb]->free();
                    $this->db["result"][$iddb]->close();
                    unset($this->db["result"][$iddb]);
                }
            }
        }
        if (array_key_exists("field", $this->db))
        {
            unset($this->db["field"][$iddb]);
        }
    }

    /**
     * mendapatkan data dari basis data
     * @param type $iddb
     * - id koneksi
     * @param string $sql
     * - berisi statemen yang mengandung query: select <kolom> from <tabel>
     * @param type $filter
     * - filter berupa array dengan indeks: where, order by, limit - OPSIONAL, default=array()
     */
    function mysqli_select($iddb, $sql, $filter=array(), $forcedebug=true)
    {
        $this->db["field"][$iddb] = array();
        /* menggabungkan sql dengan filter (jika ada) */
        $sql  .= ((!array_key_exists("where", $filter)) ? "" : ((trim($filter["where"]) == "") ? "" : " where ".$filter["where"])).((!array_key_exists("group by", $filter)) ? "" : ((trim($filter["group by"]) == "") ? "" : " group by ".$filter["group by"])).((!array_key_exists("order by", $filter)) ? "" : ((trim($filter["order by"]) == "") ? "" : " order by ".$filter["order by"])).((!array_key_exists("limit", $filter)) ? "" : ((trim($filter["limit"]) == "") ? "" : " limit ".$filter["limit"]));
        /* eksekusi sql */
        $this->db["result"][$iddb] = $this->db["conn"][$iddb]->query($sql);
        /* jika ada data, maka simpan semua nama kolom */
        if ($this->db["result"][$iddb]->num_rows>0) 
        {
            $finfo  = $this->db["result"][$iddb]->fetch_fields();
            foreach ($finfo as $val)
            {
                $this->db["field"][$iddb][]  = $val->name;
            }
        }
        /* simpan pesan kesalahan */
        $this->db["error"][$iddb]  = $this->db["conn"][$iddb]->error;
        /* tampilkan pesan debug apabila dalam mode debug */
        if ($this->debug || $forcedebug) 
        {
            echo $sql."<br />";
        }
    }

    /**
     * eksekusi insert, update, delete
     * @param type $iddb
     * -id koneksi
     * @param string $sql
     * - berisi statemen yang mengandung query insert, update atau delete
     */
    function mysqli_iud($iddb, $sql)
    {
        /* eksekusi sql */
        $this->db["conn"][$iddb]->query($sql);
        /* simpan pesan kesalahan */
        $this->db["error"][$iddb]  = $this->db["conn"][$iddb]->error;
        $this->db["info"][$iddb]   = $this->db["conn"][$iddb]->info;
        if (!empty($this->db["error"][$iddb]))
        {
            echo "<br/>Query: $sql. Error: ".$this->db["error"][$iddb];
        }
    }

    /**
     * mendapatkan data dari map sesuai dengan tabel yang diinginkan
     * @param type $iddb
     * - id koneksi
     * @param string $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter berupa array dengan indeks: where, order by, limit - OPSIONAL, default=array()
     * @param type $iscek
     * - apakah hanya cek saja? - OPSIONAL, default=false
     * @param type $iscetak
     * - apakah perlu dicetak? - OPSIONAL, default=false
     */
    function mysqli_mapdb_select($iddb, $tabel, $filter=array(), $iscek=false, $iscetak=false)
    {
        /* eksekusi jika sudah terpetakan di mapdb */
        if (! empty($this->mapdb["table"][$tabel]["nama"]))
        {
            /* buat query dari informasi yang ada di mapdb */
            $sql  = "select ".implode(",", array_filter($this->mapdb["field"][$tabel]))." from ".$this->mapdb["table"][$tabel]["nama"].(($iscek) ? " where null" : "");
            /* eksekusi sql */
            $this->mysqli_select($iddb, $sql, $filter);
        }
        /* apakah perlu dicetak? */
        if ($iscetak)
        {
            $kolom  = $this->infokolominstitusi_mapdb($tabel);
            while($row = $this->db["result"][$iddb]->fetch_assoc())
            {
                for ($i=0; $i<$kolom["count"]; $i++)
                {
                    echo $kolom["value"][$i].": ".$row[$kolom["value"][$i]];
                }
                echo "<br />";
            }
            /* bersih-bersih */
            unset($kolom);
        }
    }

    /**
     * mendapatkan informasi kolom institusi yang terpetakan ke tabel PDDIKTI
     * @param type $tabel
     * - nama tabel PDDIKTI
     * @return type
     * - info berupa (1) pemetaan lengkap, PDDIKTI sebagai indeks dan institusi sebagai data, (2) jumlah kolom yang dipetakan, dan (3) pemetaan kolom institusi saja (indeks menggunakan angka, mulai dari 0)
     */
    function infokolominstitusi_mapdb($tabel)
    {
        /* masukkan pemetaan lengkap, PDDIKTI sebagai indeks dan institusi sebagai data  */
        $kolom["map"]       = $this->mapdb["field"][$tabel];
        /* jumlah kolom yang dipetakan */
        $kolom["count"]     = count($kolom["map"]);
        /* pemetaan kolom institusi saja (indeks menggunakan angka, mulai dari 0) */
        foreach($kolom["map"] as $kolom["ws"] => $kolom["institusi"])
        {
            $kolom["value"][] = $kolom["institusi"];
        }
        return $kolom;
    }
    
    function guid_($teks)
    {
        return substr($teks, 0, 36);
    }
    
/*^^ manipulasi DB - WEBSERVICE PDDIKTI ^^*/

/*vv sync DB - WEBSERVICE PDDIKTI vv*/

    function is_tahunakademikaktif($tahunakademik)
    {
        $data = $this->GetRecordset("semester", "id_smt='$tahunakademik'", "", 1, 0);
        return $data["result"][0]["a_periode_aktif"] == 1;
    }

    /**
     * memeriksa kecocokan tabel dan isi dari tabel-tabel pada basis data Institusi dengan PDDIKTI
     * @param type $perkecualian
     * - daftar tabel PDDIKTI yang tidak ikut dicocokkan 
     * @param type $nonref
     * - daftar tabel non referensi yang ikut dicocokkan
     */
    function cek_tabel($perkecualian=array(), $nonref=array())
    {
        $tabel_ref = array();
        /* mendapatkan daftar tabel PDDIKTI */
        $listtable  = $this->ListTable();
        echo "<hr><h1>Check Mapping</h1>";
        echo "<ol><li>Tabel Referensi akan dilihat datanya, selain itu hanya melihat <em>mapping</em> saja</li><li>Apabila ada ketidak-sesuaian antara isi tabel Institusi dan tabel FEEDER PDDIKTI, maka yang harus disesuaikan adalah isi tabel Institusi</li><li>Info selengkapnya dapat dibaca di: <a href='http://pdsi.unisayogya.ac.id/check-mapping-pada-injector-feeder-dikti-perlukah/'>Check Mapping pada Injector FEEDER DIKTI, Perlukah?</a></li></ol>";
        /* mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer */
        $this->kirim_buffer();
        $iddb = $this->mysqli_terhubung();
        /* cek setiap tabel */
        foreach ($listtable["result"] as $tabel)
        {
            $pemetaanpk = true;
            /* reset kolom yang sudah dipakai oleh data institusi ($i) */
            $i          = 0;
            /* mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer */
            $this->kirim_buffer();
            /* jika tabel merupakan tabel referensi/non referensi yang dipetakan, dan tidak dikecualikan, maka isinya harus sesuai antara PDDIKTI dengan Institusi */
            if ((strtolower($tabel["jenis"])=="ref" || in_array($tabel["table"], $nonref)) && !in_array($tabel["table"], $perkecualian))
            { 
                $this->cetak_accordion_mulai("Tabel ".$tabel["table"]);
                /* cek mapping tabel PDDIKTI dengan tabel Institusi, tabel belum dipetakan */
                if (count($this->mapdb["table"][$tabel["table"]]["nama"])==0 || $this->mapdb["table"][$tabel["table"]]["nama"]=="")
                {
                    echo "<br />Warning! Tabel ".$tabel["table"]." belum dipetakan";
                }
                /* tabel sudah dipetakan */
                else
                {
                    echo "<br />Tabel ".$tabel["table"]." dipetakan ke tabel ".$this->mapdb["table"][$tabel["table"]]["nama"]."<br />";
                    /* mendapatkan daftar kolom PDDIKTI dari tabel spesifik */
                    $listfield  = $this->GetDictionary($tabel["table"]);
                    foreach ($listfield["result"] as $idx_field => $kolom)
                    {
                        /* cek mapping kolom PDDIKTI dengan kolom Institusi */
                        echo "<br />".(($this->mapdb["field"][$tabel["table"]][$idx_field]=="") ? "Warning! Field ".$tabel["table"].".".$idx_field." belum dipetakan" : "Field ".$tabel["table"].".".$idx_field." dipetakan ke field ".$this->mapdb["table"][$tabel["table"]]["nama"].".".$this->mapdb["field"][$tabel["table"]][$idx_field]);
                    }
                    /* cek keberadaan tabel Institusi */
                    $this->mysqli_mapdb_select($iddb, $tabel["table"], array(), true);
                    echo "<br />".(($this->db["error"][$iddb]=="") ? "Tabel ".$tabel["table"]." pada Institusi OK" : $this->db["error"][$iddb])."<br />";
                    /* bersih-bersih */
                    $this->mysqli_bersihkan($iddb);
                    unset($listfield);
                }
                /* tidak menampilkan ketika pemetaan pk belum ada */
                if ($this->mapdb["pk"][$tabel["table"]][0] == "" || $this->mapdb["pk"][$tabel["table"]][1] == "")
                {
                    $pemetaanpk = false;
                    echo "<br />Warning! Tabel ".$tabel["table"]." belum dipetakan primary key-nya";
                }
                /* jika tabel sudah dipetakan, dapatkan data dari institusi */
//                else 
                {
                    if ($pemetaanpk && !(count($this->mapdb["table"][$tabel["table"]]["nama"])==0 || $this->mapdb["table"][$tabel["table"]]["nama"]==""))
                    {
                        /* dapatkan data dari institusi */
                        $this->mysqli_mapdb_select($iddb, $tabel["table"], array("order by" => $this->mapdb["pk"][$tabel["table"]][1], "where" => $this->filter_perbaiki_isnull($this->mapdb["table"][$tabel["table"]]["filter"])));
                        $kolom        = $this->infokolominstitusi_mapdb($tabel["table"]);
                        /* header */
                        foreach ($kolom["value"] as $idx => $value)
                        {
                            $tabel_ref["header"][]  = "ins ".$value;
                        }
                        /* data institusi, 
                         * data akan disimpan berdasarkan nilai kunci primernya, 
                         * sehingga akan terlihat apakah dengan primary key yang sama antara data Institusi dan PDDIKTI, memiliki data yang sama
                         */
                        while($row = $this->db["result"][$iddb]->fetch_assoc())
                        {
                            /* ambil data hanya yang sudah dipetakan saja */
                            for ($i=0; $i<$kolom["count"]; $i++)
                            {
                                /* jika kolom adalah kolom primary key */
                                if ($kolom["value"][$i] == $this->mapdb["pk"][$tabel["table"]][1]) 
                                {
                                    /* gunakan nilai dari PK sebagai indeks */
                                    $indeks = trim($row[$this->ignore_alias($kolom["value"][$i])]);
                                    /* tangkap data */
                                    $tabel_ref["data"][$indeks][$i] = $row[$this->ignore_alias($kolom["value"][$i])];
                                }
                                /* kolom selain PK */
                                else
                                {
                                    /* tangkap data, jika terdapat data dobel, maka digabungkan */
                                    $tabel_ref["data"][$indeks][$i] .= $row[$this->ignore_alias($kolom["value"][$i])]."; ";
                                }
                            }
                        }
                        /* bersih-bersih */
                        $this->mysqli_bersihkan($iddb);
                        unset($kolom);
                    }
                    /* dapatkan data dari PDDIKTI */                
                    /* mendapakan 1 baris saja untuk mendapatkan nama field */
                    $hasil  = $this->GetRecordset($tabel["table"], "", "", 1, 0);
                    /* header */
                    foreach ($hasil["result"][0] as $idx => $value)
                    {
                        $tabel_ref["header"][]  = "pdd ".$idx;
                    }
                    /* tampilkan pesan debug apabila dalam mode debug */
                    if ($this->debug) 
                    {
                        $this->print_r_rapi($hasil);
                    }
//                    if ($pemetaanpk)
                    {
                        //antisipasi error pada WS GetCountRecordset
                        $brs    = $this->GetCountRecordset($tabel["table"])["result"];
                        $baris  = (empty($brs)) ? 10000 : $brs; 
                        /* data diambil per $pddikti["ws"]["limit"] baris agar tidak kehabisan memory */
                        for ($awal=0; $awal < $baris; $awal+=$this->pddikti["ws"]["limit"])
                        {
                            /* ambil data secara parsial, sesuai dengan limit */
                            $hasil = $this->GetRecordset($tabel["table"], "", "", $this->pddikti["ws"]["limit"], $awal);
                            foreach ($hasil["result"] as $idx => $row)
                            {
                                /* kolom dari data PDDIKTI ($j) dimulai dari sebelah kolom yang sudah dipakai oleh data institusi ($i) */
                                $j      = $i;
                                /* gunakan nilai dari PK sebagai indeks */
                                $pk     = trim($row[$this->mapdb["pk"][$tabel["table"]][0]]);
                                $indeks = (empty($pk)) ? $idx : $pk;
                                /* data */
                                foreach ($row as $kolom => $value)
                                {
                                    /* tangkap data */
                                    $tabel_ref["data"][$indeks][$j]  = $value;
                                    $j++;
                                }
                            }
                        }
                    }
                    /* cetak data institusi dan PDDIKTI */
                    $this->cetak_tabel($tabel_ref["header"], $tabel_ref["data"]);
                    /* bersih-bersih */
                    unset($hasil);
                    unset($tabel_ref);
                }
                $this->cetak_accordion_akhiri();
            }
        }
        /* bersih-bersih */
        unset($listtable);
        $this->mysqli_putus($iddb);
    }

    /**
     * mencetak n baris data dari webservice PDDIKTI
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter data yang akan diambil - OPSIONAL, default: ""
     * @param type $order
     * - pengurutan data - OPSIONAL, default: ""
     * @param type $batas
     * - banyaknya data yang akan ditampilkan - OPSIONAL, default: 1000000
     * @param type $mulai
     * - dari nomor berapa data akan diambil - OPSIONAL, default: 0
     */
    function cetak_recordset($tabel, $filter="", $order="", $batas=1000000, $mulai=0)
    {
        $row  = array();
        /* memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri) */
        $this->cetak_tabel_parsial_mulai();
        /* mendapakan 1 baris saja untuk mendapatkan nama field */
        $datanamafield = $this->GetRecordset($tabel, "", "", 1, 0);
        /* header */
        foreach ($datanamafield["result"][0] as $idx => $value)
        {
            $row[]  = $idx;
            $value  = $value; //clean up
        }
        /* mengisi tabel secara terpisah */
        $this->cetak_tabel_parsial($row, true, 1, 1, false, true);
        /* data */
        $data = $this->GetRecordset($tabel, $filter, $order, $batas, $mulai);
        /* mengisi tabel secara terpisah */
        $this->cetak_tabel_parsial($data["result"]);
        /* mengakhiri membuat tabel secara terpisah */
        $this->cetak_tabel_parsial_akhiri();
        /* bersih-bersih */
        unset($row);
        unset($data);
    }

    /**
     * mensinkronkan GUID pada tabel-tabel basis data Institusi dari basis data Feeder PDDIKTI, 
     * <br/> yaitu mencari baris yang GUID-nya masih kosong (null) pada tabel Institusi kemudian diisi GUID dari PDDIKTI pada tabel yang bersesuaian
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $filter
     * - filter data yang akan diambil (PDDIKTI) - OPSIONAL, default: ""
     * @param type $filterIns
     * - filter data yang akan diambil (Institusi) - OPSIONAL, default: ""
     * @param type $error
     * - jumlah error dari proses sebalumnya - OPSIONAL, default: 0
     */
    function pddikti_sinkron_guid($tabel, $filter="", $filterIns = "", $error = 0)
    {
        foreach ($this->mapdb["guid"][$tabel] as $mapdb_guid)
        {
            $this->pddikti_sinkron_guid_tunggal ($tabel, $mapdb_guid, $filter, $filterIns, $error);
        }
        //$this->filtertahunakademik($inject["tahunakademik"], "=", $inject["istahunakademikkrs"], $tahunakademikkrs, $tahunakademiksebelum)
    }

    /**
     * mensinkronkan GUID pada tabel-tabel basis data Institusi dari basis data Feeder PDDIKTI, 
     * <br/> yaitu mencari baris yang GUID-nya masih kosong (null) pada tabel Institusi kemudian diisi GUID dari PDDIKTI pada tabel yang bersesuaian
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $inject
     * - konfigurasi injeksi
     * @param type $tahunakademikkrs
     * - tahun akademik krs
     * @param type $tahunakademiksebelum
     * - tahun akademik krs sebelumnya
     * @param type $filter
     * - filter data yang akan diambil (PDDIKTI) - OPSIONAL, default: ""
     * @param type $error
     * - jumlah error dari proses sebalumnya - OPSIONAL, default: 0
     */
    function pddikti_sinkron_guid_filterinjek($tabel, $inject, $tahunakademikkrs, $tahunakademiksebelum, $filter="", $error=0)
    {
        foreach ($this->mapdb["guid"][$tabel] as $idx => $mapdb_guid)
        {
            $this->pddikti_sinkron_guid_tunggal ($tabel, $mapdb_guid, $filter, $this->filtertahunakademik($inject["tahunakademik"], "=", $inject["istahunakademikkrs"], $tahunakademikkrs, $tahunakademiksebelum, $mapdb_guid["tahunakademikinjectdipakai"]), $error);
        }
    }

    /**
     * memecah proses pddikti_sinkron_guid
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     * @param type $mapdb_guid
     * - konfigurasi sinkronisasi
     * @param type $filter
     * - filter data yang akan diambil (PDDIKTI) - OPSIONAL, default: ""
     * @param type $filterIns
     * - filter data yang akan diambil (Institusi) - OPSIONAL, default: ""
     * @param type $error
     * - jumlah error dari proses sebalumnya - OPSIONAL, default: 0
     */
    private function pddikti_sinkron_guid_tunggal($tabel, $mapdb_guid_, $filter="", $filterIns = "", $error=0)
    {
        /* ambil nama tabel, karena nama tabel bisa lebih dari satu kata, di mana kata pertama adalah nama tabel, sedangkan kata berikutnya adalah keterangan */
        $tabel_asli = (stripos($tabel, " ") === false)?$tabel:explode(" ", $tabel)[0];
        if (array_key_exists("guid", $mapdb_guid_))
        {
            $mapdb_guid_ = array($mapdb_guid_);
        }
        foreach ($mapdb_guid_ as $mapdb_guid)
        {
            $this->cetak_accordion_mulai("Sync ".$tabel.((!array_key_exists("filter", $mapdb_guid)) ? "" : ", Filter: ".$mapdb_guid["filter"]).(($filterIns == "") ? "" : " and ".$filterIns));
            /* cek, apakah kolom yang harus ada digunakan pada filter? jika tidak, maka proses dihentikan */
            if ($mapdb_guid["prerequisite"] != "" && $this->is_exist($filter, $mapdb_guid["prerequisite"], true) == false)
            {
                $this->cetak_accordion_akhiri();
                echo "Proses sinkronisasi dihentikan karena kebutuhan filter ".$mapdb_guid["prerequisite"]." tidak disediakan. Hal ini untuk menjamin kebenaran data. Mohon hubungi Administrator atau Programmer.";
            }
            /* lolos cek kebutuhan minimal */
            else
            {
                /* cari yang guidnya kosong diinstitusi */
                $iddb = $this->mysqli_terhubung();
                $this->mysqli_select($iddb, "select ".$this->mapdb["pk"][$tabel][1].",".$mapdb_guid["variable"][1].((!array_key_exists("fieldtambahanerror", $mapdb_guid)) ? "" : (($mapdb_guid["fieldtambahanerror"] != "") ? ",".$mapdb_guid["fieldtambahanerror"] : ""))." from ".$mapdb_guid["table"]["check"], array("where" => $this->filter_perbaiki_isnull("isnull(".$mapdb_guid["guid"][1].")".((!array_key_exists("filter", $mapdb_guid)) ? "" : (($mapdb_guid["filter"] != "") ? " and ".$mapdb_guid["filter"] : "")).(($filterIns == "") ? "" : " and ".$filterIns)), "order by" => $mapdb_guid["order by"], "group by" => $mapdb_guid["group by"]));
                /* tidak ada data */
                if ($this->db["result"][$iddb]->num_rows == 0)
                {
                    $this->cetak_accordion_akhiri();
                    echo "<br />Tidak ada data yang akan diproses<br />"; 
                }
                /* ada data dan jumlah data tidak sama dengan error sebelumnya */
                else if ($this->db["result"][$iddb]->num_rows != $error)
                {
                    echo "<br />Terdapat ".$this->db["result"][$iddb]->num_rows." data yang akan diproses<br />";
                    /* proses sinkronisasi jika terdapat GUID yang masih kosong (null) */
                    if ($this->db["result"][$iddb]->num_rows > 0)
                    {
                        /**/
                        $fieldtambahan = array();
                        foreach(explode(",", $this->mapdb["guid"][explode(" ", $tabel)[0]][0]["infotambahanerror"]) as $value)
                        {
                            $fieldtambahan[] = $this->ignore_alias($value);
                        }
                        $proses = 0;
                        $error  = 0;
                        /* ambil variabel dari mapdb */
                        $v0     = explode(",", $mapdb_guid["variable"][0]); //variabel pddikti
                        $v1     = explode(",", $mapdb_guid["variable"][1]); //variabel institusi
                        /* memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri) */
                        $this->cetak_tabel_parsial_mulai();
                        /* cetak header */
                        $this->cetak_tabel_parsial(array_merge($this->db["field"][$iddb], array("no sync"), array("keterangan")), true, 1, 1, false, true);
                        /* data institusi yang GUID masih kosong (null) */
                        while($row = $this->db["result"][$iddb]->fetch_array(MYSQLI_BOTH))
                        {
                            $proses++;
                            /* catat no urut proses */
                            $row["syn"] = $proses;
                            /* cari info guid di feeder pddikti */
                            $v  = array();
                            reset($v0);
                            /* membuat string parameter, misalnya: trim(npsn)='053033' */
                            foreach ($v0 as $idx => $value)
                            {
                                  /* rule nama kolom PDDIKTI:
                                   * dengan raw.  -> nama kolom akan ditampilkan tanpa alias, contoh: raw.kolom1 akan ditampilkan kolom1
                                   * tanpa  raw.  -> diberikan fungsi trim pada nama kolom,   contoh: kolom1     akan ditampilkan trim(kolom1)
                                   * dengan alias -> nama kolom akan ditampilkan apa adanya,  contoh: p.kolom1   akan ditampilkan p.kolom1
                                   */
                                  /*
                                   * penggunaan upper dan lower akan disamakan antara PDDIKTI dan Institusi
                                   */
                                  $awalan     = ((substr_count($v1[$idx], "upper")>0)?" upper(":((substr_count($v1[$idx], "lower")>0)?" lower(":""));
                                  $akhiran    = ((substr_count($v1[$idx], "upper")>0) || (substr_count($v1[$idx], "lower")>0)?")":"");
                                  $v[]        = $awalan.((!(substr_count($value, ".")>0 || substr_count($value, "raw.")>0)) ? "trim(".$value.")".$akhiran."='".$row[$idx+1]."'" : ((substr_count($value, "raw.")>0)?substr($value, 4):$value).$akhiran."='".$row[$idx+1]."'");
                            }
                            /* mendapatkan data di PDDIKTI sesuai dengan parameter di atas */
                            $rec = $this->GetRecordset($tabel_asli, (($filter)?$filter." and ":"").implode(" and ", $v));
                            /* data pada PDDIKTI kosong, berarti ada data yang ada di Institusi tetapi belum masuk ke PDDIKTI, tingkatkan jumlah kesalahan dan tampilkan pesan kesalahan */
                            if (!is_array($rec["result"]))
                            {
                                  $error++;
                                  $row["ket"] = "filter ".(($filter)?$filter." and ":"").implode(" and ", $v)." tidak ditemukan di tabel $tabel_asli FEEDER PDDIKTI";
                                  $this->cetak_tabel_parsial($row);
                            }
                            /* data yang ditemukan pada PDDIKTI lebih dari satu, 
                             * membutuhkan penanganan manual 
                             */
                            elseif (count($rec["result"]) > 1) 
                            {
                                /* forcedouble untuk tabel PDDIKTI (mapping.inc.php) ini tidak diisi, tingkatkan jumlah kesalahan dan tampilkan pesan kesalahan (tabel di dalam tabel) */
                                if (!is_array($mapdb_guid["forcedouble"]))
                                {
                                    $error++;
                                    $row["ket"] = "filter ".implode(" dan ", $v)." ditemukan lebih dari satu di tabel $tabel_asli FEEDER PDDIKTI"
                                                  .$this->cetak_tabel_parsial_mulai(2, 2)
                                                  .$this->cetak_tabel_parsial($rec["result"], false, 2, 2)
                                                  .$this->cetak_tabel_parsial_akhiri(2, 2);
                                }
                                /* forcedouble untuk tabel PDDIKTI (mapping.inc.php) ini tidak diisi, penanganan otomatis */
                                else
                                {
                                    /* tampilkan pemberitahuan (tabel di dalam tabel) */
                                    $row["ket"] = "notice! filter ".implode(" dan ", $v)." ditemukan lebih dari satu di tabel $tabel_asli FEEDER PDDIKTI, tetapi sudah dijadikan satu."
                                                  .$this->cetak_tabel_parsial_mulai(2, 2)
                                                  .$this->cetak_tabel_parsial($rec["result"], false, 2, 2)
                                                  .$this->cetak_tabel_parsial_akhiri(2, 2);
                                    /* $upd: siapkan 1 data diantara data yang dobel sebagai data baru, sehingga data tidak dobel lagi */
                                    $upd        = $rec["result"][count($rec["result"]) - 1][$mapdb_guid["forcedouble"]["field"]];
                                    /* samakan data dobel pada tabel Institusi */
                                    $this->mysqli_iud($iddb, "update ".$mapdb_guid["table"]["update"]." set ".$mapdb_guid["guid"][1]."='".$upd."' where ".$this->mapdb["pk"][$tabel][1]."='".$row[0]."' and (".$mapdb_guid["guid"][1]."<>".$upd." or isnull(".$mapdb_guid["guid"][1]."))");
                                    /* iterasi pada data dobel PDDIKTI */
                                    foreach ($rec["result"] as $idx => $val)
                                    {
                                        /* jika data layak untuk diupdate (lihat $upd di atas) */
                                        if ($val[$mapdb_guid["forcedouble"]["field"]] != $upd)
                                        {
                                            /* menggabungkan data pada kolom yang terdefinisi pada forcedouble (mapping.inc.php) dengan 1 data ($upd) */
                                            $dataupdate = array(array("key"=>array($mapdb_guid["forcedouble"]["field"] => $val[$mapdb_guid["forcedouble"]["field"]]), "data"=>array($mapdb_guid["forcedouble"]["field"]=>$upd)));
                                            $hasil = $this->UpdateRecordset($mapdb_guid["forcedouble"]["table"], $dataupdate);
                                            /* tampilkan pesan kesalahan jika proses penggabungan gagal */
                                            if ($hasil["result"]["error_desc"] != "" || $hasil["error_desc"] != "") 
                                            {
                                                $row["ket"] .= "<br />Error: ".$this->ifnull($hasil["result"]["error_desc"], $hasil["error_desc"])." UpdateRecordset(".$mapdb_guid["forcedouble"]["table"].", ".$this->cetak_array($dataupdate, true).")";
                                            }
                                            /* hapus data dobel, sisa dari penggabungan */
                                            $datadelete = array(array($mapdb_guid["forcedouble"]["field"] => $val[$mapdb_guid["forcedouble"]["field"]]));
                                            $this->DeleteRecordset($mapdb_guid["forcedouble"]["table"], $datadelete);
                                            /* bersih-bersih */
                                            unset($dataupdate, $datadelete);
                                        }
                                    }  
                                }
                                $this->cetak_tabel_parsial($row);
                            }
                            /* ditemukan 1 guid yang cocok, perbaharui guid di tabel Institusi */
                            else 
                            {
                                /* ambil nama kolom di PDDIKTI di mana guid berada */
                                $g    = explode(",", $mapdb_guid["guid"][0]);
                                /* guid pertama ada isinya */
                                if ($rec["result"][0][$g[0]] != "")
                                {
                                    reset($g);
                                    $upd  = array();
                                    /* buat guid yang diambil dari PDDIKTI, jika lebih dari satu kolom maka akan digabungkan, DEPRECATED*/
//                                    foreach ($g as $idx => $val)
//                                    {
//                                        $upd[]  = "'".$rec["result"][0][$val]."'";
//                                    }
//                                    $upd  = "concat(".implode(",", $upd).")";
                                    foreach ($g as $idx => $val)
                                    {
                                        $upd    = "'".$rec["result"][0][$val]."'";
                                        break;
                                    }
                                    /* tambahan error */
                                    /* siapkan data tambahan */
                                    $tambahan = array();
                                    reset($fieldtambahan);
                                    foreach ($fieldtambahan as $value)
                                    {
                                      $tambahan[$value] = $row[$value];
                                    }
                                    /* eksekusi pembaharuan guid ke tabel Institusi */
                                    $this->mysqli_iud($iddb, "update ".$mapdb_guid["table"]["update"]." set ".$mapdb_guid["guid"][1]."=".$upd." where ".$this->mapdb["pk"][$tabel][1]."='".$row[0]."' and (".$mapdb_guid["guid"][1]."<>".$upd." or isnull(".$mapdb_guid["guid"][1]."))");                                    
                                    echo "<br />Sync >>".$proses.">> table institusi ".$mapdb_guid["table"]["update"]." dengan data ".$mapdb_guid["guid"][1]."=".$upd." dimana ".$this->mapdb["pk"][$tabel][1]."='".$row[0]."' (jika GUIDnya berbeda) [".$this->db["info"][$iddb]."]";
                                    print_r($tambahan);
                                    /* tampilkan pesan kesalahan jika proses pembaharuan gagal */
                                    if ($this->db["error"][$iddb] != "") 
                                    {
                                        $error++;
                                        echo "Error! ".$this->db["error"][$iddb];
                                    }
                                }
                                /* guid pertama kosong, tampilkan pesan kesalahan */
                                else
                                {
                                    $error++;
                                    $row["ket"] = "filter ".implode(" dan ", $v)." tidak ditemukan di tabel $tabel_asli FEEDER PDDIKTI";
                                    $this->cetak_tabel_parsial($row);
                                }
                                /* kirim data ke browser setiap 50 proses */
                                if ($proses % 50 == 0)
                                {
                                    echo "<br />Sync ".$tabel." ($proses dari ".$this->db["result"][$iddb]->num_rows.")...<br />";
                                    $this->kirim_buffer();
                                }
                                /* bersih-bersih */
                                unset($g);
                            }
                            /* bersih-bersih */
                            unset($rec);
                        } //-->end while
                        echo "<br />Sync ".$tabel." ($proses dari ".$this->db["result"][$iddb]->num_rows.")...<br />";
                        /* mengakhiri membuat tabel secara terpisah */
                        $this->cetak_tabel_parsial_akhiri();
                        $this->cetak_accordion_akhiri();
                        /* informasikan status sync */
                        if ($error == 0)
                        {
                            echo "$proses proses sync berhasil";
                        }
                        else
                        {
                            echo "$error kesalahan ditemukan pada $proses proses sync";
                        }
                        /* bersih-bersih */
                        unset($row);
                        unset($v0, $v1, $v);
                    }
                    /* tidak terdapat GUID yang masih kosong (null) */
                    else
                    {
                        echo "Tidak ada proses sync. Mungkin semua data sudah sinkron";
                    }
                }
                else
                {
                    $this->cetak_accordion_akhiri();
                    echo "Tidak perlu sync, karena jumlah error sama dengan proses sebelumnya";
                }
                /* bersih-bersih */
                $this->mysqli_bersihkan($iddb);
                $this->mysqli_putus($iddb);
            }
            echo $mapdb_guid["troubleshoot"];
        }
    }

    /**
     * mendapatkan tahunakademik sebelumnya, misal: 20152 -> 20151, 20161 -> 20152
     * @param type $tahunakademik
     * - tahun akademik
     * @return type
     * - tahun akademik sebelum
     */
    function tahunakademiksebelum($tahunakademik)
    {
        return (($tahunakademik % 2 == 0) ? $tahunakademik-1 : ((floor($tahunakademik/10)-1)*10)+2);
    }

    /**
     * lawan dari injek. memasukkan data dari PDDIKTI ke Institusi, metode: insert where not exists
     * @param type $tahunakademik_
     * - tahun akademik yang digunakan untuk KRS
     * @param type $tabel
     * - tabel (PDDIKTI) di mana data disimpan
     */
    function pddikti_ekstrak($tahunakademik_, $tabel)
    {
        /* jika istahunakademikkrs bernilai false, maka sesuaikan tahunakademikkrs */
        $tahunakademikkrs = (($this->mapdb["extract"][$tabel]["istahunakademikkrs"]) ? $tahunakademik_ : $this->tahunakademiksebelum($tahunakademik_));
        $this->cetak_accordion_mulai("EXTRACT Data $tabel $tahunakademikkrs");
        /* siapkan variabel */
        $v0     = explode(",", $this->mapdb["extract"][$tabel]["uniquefield"][0]); //feeder pddikti
        $v1     = explode(",", $this->mapdb["extract"][$tabel]["uniquefield"][1]); //institusi
        $iddb   = $this->mysqli_terhubung();
        $proses = 0;
        /* sesuaikan keyword dengan data, dapatkan data dari feeder pddikti */
        $filter = str_replace("[tahun]", substr($tahunakademikkrs, 0, 4), str_replace("[tahunakademik]", $tahunakademikkrs, $this->mapdb["extract"][$tabel]["filtertahunakademik"]));
        $n      = $this->GetCountRecordset($tabel, $filter);
        
        for ($i=0; $i<$n["result"]; $i += 1000)
        {
            $data   = $this->GetRecordset($tabel, $filter, "", 1000, $i);
            /* ambil semua data dari PDDIKTI */
            foreach ($data["result"] as $row)
            {
                $v  = array();
                reset($v1);
                /* membuat string parameter*/
                foreach ($v1 as $idx => $value)
                {
                  $v[]  = $value."='".$row[$v0[$idx]]."'";
                }
                /* menyiapkan data yang akan disisipkan */
                $columnlist   = array();
                $datalist     = array();
                foreach ($this->mapdb["field"][$tabel] as $idx=>$value)
                {
                    if ($value != "null")
                    {
                        $columnlist[] = $value;
                        $datalist[]   = "'".addslashes($row[$idx])."'";
                    }
                }
                /* eksekusi penyisipan data */
                $this->mysqli_iud($iddb, "insert into ".$this->mapdb["extract"][$tabel]["table"]." (".implode(",", $columnlist).") select ".implode(",", $datalist)." from ".$this->mapdb["extract"][$tabel]["table"]." where not exists (select 1 from ".$this->mapdb["extract"][$tabel]["table"]." where ".implode(" and ", $v).") limit 0,1");
                echo "<br /><b>--Extract >>".++$proses.">> </b> Sisipkan jika belum ada data pada tabel institusi ".$this->mapdb["extract"][$tabel]["table"]." dengan data ".implode(",", $datalist)." [".$this->db["info"][$iddb]."]";
                /* kirim data ke browser setiap 50 proses */
                if ($proses % 50 == 0)
                {
                    echo "<br />Extract $tabel ($proses dari ".$n["result"].")...<br />";
                    $this->kirim_buffer();
                }
            }
        }
        echo "<br />Extract $tabel ($proses dari ".$n["result"].")...<br />Proses telah selesai!";
        $this->cetak_accordion_akhiri();
        /* mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer */
        $this->kirim_buffer();

        /* bersih-bersih */
        unset($row);
        unset($v, $v0, $v1, $columnlist, $datalist);
        $this->mysqli_bersihkan($iddb);
        $this->mysqli_putus($iddb);
    }
    
    /**
     * mencetak log eksekusi
     */
    function cetak_log()
    {
        echo "Daftar 10 eksekusi terakhir: <br/>";
        $iddb = $this->mysqli_terhubung();
        /* dapatkan daftar pengajar yang tidak ada di dalam penugasan pada tahunakademik bersangkutan */
        $que  = "select * from ".$this->mapdb["log"]["table"]." order by ".$this->mapdb["log"]["field"]["idlog"]." desc limit 0,10";
        $this->mysqli_select($iddb, $que);
        /* memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri) */
        $this->cetak_tabel_parsial_mulai();
        echo "<thead>";
        $this->cetak_tabel_parsial($this->db["field"][$iddb], true, 1, 1, false, true);
        echo "</thead>";
        /* tampilkan data dosen yang belum dimasukkan di penugasan */
        while ($row = $this->db["result"][$iddb]->fetch_assoc()) 
        {
            $this->cetak_tabel_parsial($row);
        }
        /* mengakhiri membuat tabel secara terpisah */
        $this->cetak_tabel_parsial_akhiri();
        /* bersih bersih */
        $this->mysqli_bersihkan($iddb);
        $this->mysqli_putus($iddb);
    }

    /**
     * cek apakah semua dosen mengajar sudah dimasukkan ke Penugasan di Feeder
     * @param type $tahunakademik_
     * - tahun akademik
     * @return type
     * - true: semua dosen sudah dimasukkan ke penugasan, false: belum semua dosen sudah dimasukkan ke penugasan
     */
    function cek_penugasan($tahunakademik_)
    {
        /* mengupdate data NIDN/NUPN di tabel Institusi */
        $this->update_nidn();
        /* memasukkan data dosen_pt dari PDDIKTI ke Institusi */
        $this->pddikti_ekstrak($tahunakademik_, "dosen_pt");
        $this->cetak_accordion_mulai("Cek Penugasan Untuk Transaksi T.A. $tahunakademik_");
        echo "<h1>Daftar pengajar yang tidak ada di dalam penugasan tahun ".substr($tahunakademik_, 0, 4)."</h1>";
        $iddb = $this->mysqli_terhubung();
        /* dapatkan daftar pengajar yang tidak ada di dalam penugasan pada tahunakademik bersangkutan */
        $que  = "select distinct guiddosen, namalengkap from ((select guiddosen, namalengkap from ak_jadwalkuliah jk join ak_timteaching tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_penawaranmatakuliah pm on pm.kdpenawaran=tt.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed where tt.kdtahunakademik=".$tahunakademik_." and tt.isignore=0 and pm.isignore=0 and jk.kdtahunakademik=".$tahunakademik_." and pm.kdtahunakademik=".$tahunakademik_." and isrealisasi in (1,4)) union all (select guiddosen, namalengkap from ak_jadwalkuliah_lab jk join ak_timteaching_lab tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_kelompok kl on kl.kdkelompok=tt.kdkelompok join ak_penawaranmatakuliah pm on pm.kdpenawaran=kl.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed where tt.kdtahunakademik=".$tahunakademik_." and tt.isignore=0 and pm.isignore=0 and jk.kdtahunakademik=".$tahunakademik_." and pm.kdtahunakademik=".$tahunakademik_." and isrealisasi in (1,4))) cek_dosen where guiddosen not in (select id_sdm from ak_penugasan where tahun=left(".$tahunakademik_.", 4))";
        $this->mysqli_select($iddb, $que);
        /* memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri) */
        $this->cetak_tabel_parsial_mulai();
        $this->cetak_tabel_parsial($this->db["field"][$iddb], true);
        /* tampilkan data dosen yang belum dimasukkan di penugasan */
        while ($row = $this->db["result"][$iddb]->fetch_assoc()) 
        {
            $this->cetak_tabel_parsial($row);
        }
        /* apakah semua masuk tabel penugasan institusi? */
        $issemuapenugasan = ($this->db["result"][$iddb]->num_rows == 0);
        /* mengakhiri membuat tabel secara terpisah */
        $this->cetak_tabel_parsial_akhiri();
        /* cetak tabel untuk cek perubahan UUID/GUID jika ada yang belum ditugaskan */
        if (!$issemuapenugasan)
        {
            echo "<hr/><h1>Daftar penugasan untuk transaksi tahun ".substr($tahunakademik_, 0, 4)."</h1>";
            $this->print_r_rapi($this->cetak_recordset("dosen_pt", str_replace("[tahun]", substr($tahunakademik_, 0, 4), str_replace("[tahunakademik]", $tahunakademik_, $this->mapdb["extract"]["dosen_pt"]["filtertahunakademik"])), "", 1000));
        }
        $this->cetak_accordion_akhiri();
        /* bersih bersih */
        $this->mysqli_bersihkan($iddb);
        $this->mysqli_putus($iddb);
        return $issemuapenugasan;
    }

    /**
     * mengupdate data NIDN/NUPN di tabel Institusi
     */
    function update_nidn()
    {
        $this->cetak_accordion_mulai("Update NIDN");
        echo "[Merah: Belum terpetakan, Biru: GUID tidak sama, coba: Cek NIDN, Nama Lengkap, Tempat Lahir dan Tanggal Lahir; kemudian jalankan <a href='".$this->get_url_nonget()."?a=7&t=".filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT)."' target='blank_'>".$this->get_url_nonget()."?a=7&t=".filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT)."</a>]<br/>";
        $proses = 0;
        $n      = $this->GetCountRecordset($this->mapdb["updatenidn"]["table"][0]);
        /* dapatkan data penugasan di PDDIKTI */
        $data = $this->GetRecordset($this->mapdb["updatenidn"]["table"][0]);
        /* update data penugasan di Institusi */
        $iddb   = $this->mysqli_terhubung();
        $error  = array();
        foreach ($data["result"] as $row)
        {
            /* eksekusi update */
            $this->mysqli_iud($iddb, "update ".$this->mapdb["updatenidn"]["table"][1]." set ".$this->mapdb["updatenidn"]["nidn"][1]."='".$row[$this->ignore_alias($this->mapdb["updatenidn"]["nidn"][0])]."' where ".$this->mapdb["updatenidn"]["nidn"][1]."<>'".$row[$this->ignore_alias($this->mapdb["updatenidn"]["nidn"][0])]."' and ".str_replace(":guid",$row[$this->ignore_alias($this->mapdb["updatenidn"]["guid"])],$this->mapdb["updatenidn"]["filter"]));
            /* dapatkan guid dari dosen */
            $this->mysqli_select($iddb, str_replace(":nidn",$row[$this->ignore_alias($this->mapdb["updatenidn"]["nidn"][0])],$this->mapdb["updatenidn"]["kroscek"]), array(), false);
            $guiddosenpddikti   = $row[$this->ignore_alias($this->mapdb["updatenidn"]["guid"])];
            $guiddosenins       = $this->db["result"][$iddb]->fetch_assoc()[$this->ignore_alias($this->mapdb["guid"]["dosen"][0]["guid"][1])];
            if ((strpos($this->db["info"][$iddb], "Rows matched: 0") !== false) || $guiddosenpddikti != $guiddosenins)
            {
                $error[] = "<b style='color:red'>Ubah >> ".$row[$this->mapdb["updatenidn"]["info"]]." ".$guiddosenpddikti." > </b>".$this->mapdb["updatenidn"]["nidn"][1]."='".$row[$this->ignore_alias($this->mapdb["updatenidn"]["nidn"][0])]."', GUID di Institusi: ".(($guiddosenpddikti != $guiddosenins)?"<b style='color:blue'>".$guiddosenins."</b>":$guiddosenins)." [".$this->db["info"][$iddb]."]<br/>";
            }
            else
            {
                echo "<b>Ubah >> ".$row[$this->mapdb["updatenidn"]["info"]]." ".$guiddosenpddikti." > </b>".$this->mapdb["updatenidn"]["nidn"][1]."='".$row[$this->ignore_alias($this->mapdb["updatenidn"]["nidn"][0])]."' (jika GUIDnya berbeda), GUID di Institusi: ".$guiddosenins." [".$this->db["info"][$iddb]."]<br/>";
            }
            /* kirim data ke browser setiap 50 proses */
            if (++$proses % 50 == 0)
            {
                echo "<br />Update NIDN ($proses dari ".$n["result"].")...<br />";
                $this->kirim_buffer();
            }
            /* bersih-bersih */
            $this->mysqli_bersihkan($iddb);
        }
        foreach ($error as $row)
        {
            echo $row;
        }
        echo "<br />Update NIDN ($proses dari ".$n["result"].")...<br />Proses telah selesai!";
        $this->cetak_accordion_akhiri();
        /* mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer */
        $this->kirim_buffer();
        /* bersih-bersih */
        unset($data);
        $this->mysqli_putus($iddb);
    }

    /**
     * membuat string filter akademik
     * @param type $tahunakademik
     * - kolom tahun akademik (mapping.inc.php)
     * @param type $tandatahunakademik
     * - tanda tahun akademik
     * @param type $istahunakademikkrs
     * - apakah tahun akademik krs
     * @param type $tahunakademikkrs
     * - tahun akademik krs
     * @param type $tahunakademiksebelum
     * - tahun akademik krs sebelumnya
     * @param type $banyakparameter
     * - banyaknya parameter yang ingin digunakan, -1 atau 0 berarti semua digunakan - OPSIONAL, default=-1
     * @return type
     * - string filter akademik
     */
    function filtertahunakademik($tahunakademik, $tandatahunakademik, $istahunakademikkrs, $tahunakademikkrs, $tahunakademiksebelum, $banyakparameter=-1)
    {
        $parameterdigunakan   = 0;
        /* dapatkan daftar kolom tahun akademik */
        $array_tahunakademik  = (stripos($tahunakademik, ",") === false)?$tahunakademik:explode(",", $tahunakademik);
        $array_filter         = array();
        /* jika kolom tahun akademik lebih dari satu */
        if (is_array($array_tahunakademik))
        {
          /* gabungkan kolom tahun akademik dengan tahun akademik untuk semua kolom */
          foreach ($array_tahunakademik as $value_tahunakademik)
          {
              $array_filter[]   = $value_tahunakademik.$tandatahunakademik.(($istahunakademikkrs) ? $tahunakademikkrs : $tahunakademiksebelum);
              $parameterdigunakan++;
              /* jika parameter yang digunakan sudah sesuai dengan banyak parameter yang diinginkan maka sudah cukup*/
              if ($parameterdigunakan == $banyakparameter) 
              {
                  break;
              }
          }
          return implode(" and ", $array_filter);
        }
        /* jika kolom tahun akademik hanya satu */
        else
        {
            return $tahunakademik.$tandatahunakademik.(($istahunakademikkrs) ? $tahunakademikkrs : $tahunakademiksebelum);
        }
    }

    /**
      * mengubah isnull(kolom) menjadi (isnull(kolom) or lcase(trim(kolom))="null" or length(trim(kolom))=0) pada filter query
      * @param type $filter
      * - filter atau query
      * @return type
      * - (isnull(kolom) or lcase(trim(kolom))="null" or length(trim(kolom))=0) pada filter query
      */
     function filter_perbaiki_isnull($filter)
     {
          if (substr_count($filter, "isnull(") > 0)
          {
              $perbaikan = "";
              foreach(explode("isnull(", $filter) as $idx1=>$diperbaiki1)
              {
                  if (strlen(trim($diperbaiki1)) > 0)
                  {
                     if ($idx1 == 0)
                     {
                         $perbaikan = $diperbaiki1;
                     }
                     else
                     {
                         $perbaikan2 = array();
                         foreach (explode(")", $diperbaiki1) as $idx2=>$diperbaiki2)
                         {
                              $perbaikan2[] = ($idx2==0) ? "(isnull($diperbaiki2) or lcase(trim($diperbaiki2))=\"null\" or length(trim($diperbaiki2))=0" : $diperbaiki2;
                         }
                         $perbaikan .= implode(")", $perbaikan2);
                     }
                  }
              }
              return $perbaikan;
          }
          return $filter;
     }

    /**
     * memasukkan data dari tabel Institusi ke PDDIKTI
     * @param type $tahunakademikkrs
     * - tahun akademik krs
     * @param type $tabelinjectindividual
     * - nama tabel PDDIKTI, jika diisi, maka hanya tabel ini saja yang diinjek - OPSIONAL, default: ""
     * @param type $modeinjek
     * - mode inject atau inject_perbaiki_usang - OPSIONAL, default: "inject"
     * @param type $ignoreguid
     * - jika bernilai true, maka semua data akan dibaca, tetapi jika bernilai false, maka hanya data yang belum sinkron saja yang akan dibaca - OPSIONAL, default: "false"
     */
    function pddikti_injek($tahunakademikkrs, $tabelinjectindividual="", $modeinjek="inject", $ignoreguid=false)
    {
      echo "<hr /><h2>INJECT Data $tahunakademikkrs, mode $modeinjek</h2>";
      if (!$ignoreguid) 
      {
          echo "<br /><b>Perhatian!!! Semua data akan diperbaiki, baik yang sudah sinkron maupun yang belum</b><br/><br/>";
      }
      /* hitung tahun akademik sebelum */
      $tahunakademiksebelum = $this->tahunakademiksebelum($tahunakademikkrs);
      /* cek keaktifan tahunakademik */
      $is_aktif[$tahunakademikkrs]      = $this->is_tahunakademikaktif($tahunakademikkrs);
      $is_aktif[$tahunakademiksebelum]  = $this->is_tahunakademikaktif($tahunakademiksebelum);
      $iddb = $this->mysqli_terhubung();
      foreach ($this->mapdb[$modeinjek] as $tabel => $inject)
      {
        $fieldtambahan = array();
        foreach(explode(",", $this->mapdb["guid"][$tabel][0]["infotambahanerror"]) as $value)
        {
            $fieldtambahan[] = $this->ignore_alias($value);
        }
        /* eksekusi: 
         * jika tabelinjectindividual tidak kosong maka eksekusi sesuai tabelinjectindividual saja
         * atau jika sebaliknya maka eksekusi yang ignoreinject=false
         */
        if ($tabelinjectindividual!="" && $tabelinjectindividual==$tabel)
        {
          $mode = MODE_INJECT_INDIVIDU;
        }
        else if ($tabelinjectindividual=="" && $inject["ignoreinject"]==false)
        {
          $mode = MODE_INJECT_MASSAL;
        }
        else
        {
          $mode = MODE_INJECT_GAGAL;
        }
        /* jika tidak gagal (setting pada mapping.inc.php benar) */
        if($mode == MODE_INJECT_INDIVIDU || $mode == MODE_INJECT_MASSAL)
        {
          if (!$this->issudahcekpenugasan[(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)])
          {
            /* cek data penugasan terlebih dahulu, jika belum semua dosen ditugaskan, maka proses injek untuk ajar_dosen dibatalkan */
            if(!$this->cek_penugasan((($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)) && $tabelinjectindividual == "ajar_dosen")
            {
                echo "<br /><H2>Belum semua Dosen didaftarkan pada Penugasan, proses INJECT PDDIKTI tetap dilanjutkan. Namun, Silakan daftarkan semua nama Dosen di atas<br />Coba cari terlebih dahulu dosen di tabel di atas by nama, siapa tahu UUID/GUID nya berubah.</H2>";
//                exit();
            }
            $this->issudahcekpenugasan[(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)] = true;
          }
          $proses = 0;
          $tabel_asli = (stripos($tabel, " ") === false)?$tabel:explode(" ", $tabel)[0];
          /* sync guid sebelum inject, siapa tahu sudah masuk sebelumnya 
           * boleh diaktifkan, atau dikomentari, tergantung kebutuhan
           */
          /*
          if ($mode == MODE_INJECT_MASSAL)
          {
            if (array_key_exists("tahunakademik", $inject))
              $this->pddikti_sinkron_guid($tabel, "", $inject["tahunakademik"]."=".(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum));
            else
              $this->pddikti_sinkron_guid($tabel);
          }*/
          $this->cetak_accordion_mulai("Inject $tabel ".(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)." - Metode ".$inject["type"]);
          /* cek apakah perlu dilanjutkan (tahun akademiknya aktif atau tidak terpengaruh tahun akademik) atau tidak */
//          $prosesinjek = (array_key_exists("tahunakademik", $inject)) ? $is_aktif[(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)] : true;
          $prosesinjek = true;
          if ($prosesinjek)
          {
              /* siapkan kolom-kolom yang digunakan */
              $param  = array();
      /* insert */
              if ($inject["type"]=="insert")
              {
                /* dapatkan kolom untuk didapatkan dari basis data institusi, kecuali PK */
                foreach ($this->mapdb["field"][$tabel] as $pddikti => $institusi)
                {
                  if ($pddikti != $this->mapdb["pk"][$tabel][0]) 
                  {
                      $param[] = $institusi;
                  }
                }
              }
      /* update */
              else if ($inject["type"]=="update")
              {
                /* dapatkan kolom untuk didapatkan dari basis data institusi */
                foreach ($inject["fieldwhere"] as $idx => $pddikti)
                {
                  $param[] = $this->mapdb["field"][$tabel][$this->ignore_alias($pddikti)];
                }
                foreach ($inject["fieldupdate"] as $idx => $pddikti)
                {
                  $param[] = $this->mapdb["field"][$tabel][$this->ignore_alias($pddikti)];
                }
              }
              /* ambil data untuk yang jenisfilternya terisi */
              if (array_key_exists("jenisfilter", $inject))
              {
                /* jenisfilter = internalfilter */
                if ($inject["jenisfilter"] == "internalfilter")
                {
                  /* jika tahunakademiknya terisi, ganti string [internalfilter] */
                  if (array_key_exists("tahunakademik", $inject))
                  {
                      $que  = str_replace("[internalfilter]", $this->filtertahunakademik($inject["tahunakademik"], $inject["tandatahunakademik"], $inject["istahunakademikkrs"], $tahunakademikkrs, $tahunakademiksebelum)." and ".(($ignoreguid)?"1=1":"isnull(".$this->mapdb["field"][$tabel][$this->ignore_alias($this->mapdb["pk"][$tabel][0])].")").((!array_key_exists("filter", $inject)) ? "" : (($inject["filter"] != "") ? " and ".$inject["filter"] : "")), $inject["table"]);
                  }
                  /* jika tahunakademiknya tidak terisi, ganti string [internalfilter] */
                  else
                  {
                      $que  = str_replace("[internalfilter]", (($ignoreguid)?"1=1":"isnull(".$this->mapdb["field"][$tabel][$this->ignore_alias($this->mapdb["pk"][$tabel][0])].")").((!array_key_exists("filter", $inject)) ? "" : (($inject["filter"] != "") ? " and ".$inject["filter"] : "")), $inject["table"]);
                  }
                  /* eksekusi */
                  $this->mysqli_select($iddb, $this->filter_perbaiki_isnull($que));
                }
              }
              /* ambil data untuk yang jenisfilternya tidak terisi */
              else
              {
                /* jika tahunakademiknya terisi */
                if (array_key_exists("tahunakademik", $inject))
                {
                    $this->mysqli_select($iddb, "select ".$this->mapdb["pk"][$tabel][1].",".implode(",", $param).((!array_key_exists("infotambahanerror", $inject)) ? "" : (($inject["infotambahanerror"] != "") ? ",".$inject["infotambahanerror"] : ""))." from ".$inject["table"], array("where" => $this->filter_perbaiki_isnull($this->filtertahunakademik($inject["tahunakademik"], $inject["tandatahunakademik"], $inject["istahunakademikkrs"], $tahunakademikkrs, $tahunakademiksebelum)." and ".(($ignoreguid)?"1=1":"isnull(".$this->mapdb["field"][$tabel][$this->ignore_alias($this->mapdb["pk"][$tabel][0])].")").((!array_key_exists("filter", $inject)) ? "" : (($inject["filter"] != "") ? " and ".$inject["filter"] : "")))));
                }
                /* jika tahunakademiknya tidak terisi */
                else
                {
                    $this->mysqli_select($iddb, "select ".$this->mapdb["pk"][$tabel][1].",".implode(",", $param).((!array_key_exists("infotambahanerror", $inject)) ? "" : (($inject["infotambahanerror"] != "") ? ",".$inject["infotambahanerror"] : ""))." from ".$inject["table"], array("where" => $this->filter_perbaiki_isnull((($ignoreguid)?"1=1":"isnull(".$this->mapdb["field"][$tabel][$this->ignore_alias($this->mapdb["pk"][$tabel][0])].")").((!array_key_exists("filter", $inject)) ? "" : (($inject["filter"] != "") ? " and ".$inject["filter"] : "")))));
                }
              }
              $error  = 0;
              /* tidak ada data dari PDDIKTI yang dapat diproses */
              print_r($this->db["result"][$iddb]);
              if ($this->db["result"][$iddb]->num_rows == 0) 
              {
                  $this->cetak_accordion_akhiri();
                  echo "<br />Tidak ada data yang akan diproses<br />";
              }
              /* terdapat data dari PDDIKTI yang dapat diproses */
              else
              {
                echo "<br />Terdapat ".$this->db["result"][$iddb]->num_rows." data yang akan diproses<br />";
                /* memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri) */
                $this->cetak_tabel_parsial_mulai();
                /* cetak header */
                $this->cetak_tabel_parsial(array_merge($this->db["field"][$iddb], array("no sync"), array("keterangan")), true, 1, 1, false, true);
      /*-- insert --*/
                if ($inject["type"]=="insert")
                {
                  /* proses data dari Institusi */
                  while($row = $this->db["result"][$iddb]->fetch_assoc())
                  {
                    $proses++;
                    /* siapkan data yang hendak dimasukkan ke pddikti */
                    $data = array();
                    reset($this->mapdb["field"][$tabel]);
                    foreach ($this->mapdb["field"][$tabel] as $pddikti => $institusi)
                    {
                      /* kunci primer tidak ikut dimasukkan */
                      if ($pddikti != $this->mapdb["pk"][$tabel][0]) 
                      {
                        if ($row[$this->ignore_alias($institusi)] != null)
                        {
                            $data[$pddikti] = $row[$this->ignore_alias($institusi)];
                        }
                      }
                    }
                    /* siapkan data tambahan */
                    $tambahan = array();
                    reset($fieldtambahan);
                    foreach ($fieldtambahan as $value)
                    {
                      $tambahan[$value] = $row[$value];
                    }
                    /* injeksi data ke PDDIKTI */
                    $hasil = $this->InsertRecord($tabel_asli, $data);
                    /* tampilkan kesalahan jika ada */
                    if ($hasil["result"]["error_desc"] != "" || $hasil["error_desc"] != "")
                    {
                      $error++;
                      $row["no sync"] = $proses;
                      $row["ket"]     = $this->ifnull($hasil["result"]["error_desc"], $hasil["error_desc"])." InsertRecord(".$tabel_asli.", ".$this->cetak_array($data, true).")";
                      $this->cetak_tabel_parsial($row);
                    }
                    else
                    {
                        /* ambil nama kolom di PDDIKTI di mana guid berada */
                        $g    = explode(",", $this->mapdb["guid"][$tabel][0]["guid"][0]);
                        $upd  = array();
                        /* buat guid yang diambil dari PDDIKTI, jika lebih dari satu kolom maka akan digabungkan, DEPRECATED*/
//                        foreach ($g as $val)
//                        {
//                            $upd[] = "'".$hasil["result"][$val]."'";
//                        }
//                        $upd  = "concat(".implode(",", $upd).")";
                        foreach ($g as $val)
                        {
                            $upd    = "'".$hasil["result"][$val]."'";
                            break;
                        }
                        $jumlahproses = 0;
                        $idsync     = explode(",", $row[$this->ignore_alias($this->mapdb["pk"][$tabel][1])]);
                        if (!is_array($idsync))
                            $idsync[0] = $row[$this->ignore_alias($this->mapdb["pk"][$tabel][1])];
                        $nilaiid    = array();
                        foreach ($idsync as $nilai)
                        {
                            $x  = explode("_", $nilai);
                            if (empty($x[1]))
                                $nilaiid[0] = $nilai;
                            else
                                $nilaiid[$x[0]] = $x[1];
                        }
                        foreach($this->mapdb["guid"][$tabel] as $idx => $mapdb_guid)
                        {
                            if (!empty($nilaiid[$idx]))
                            {
                                $this->pddikti_sinkronisasi_injek_insert($upd, $mapdb_guid, $mode, $data, $iddb, $proses, $nilaiid[$idx], $tabel, $tambahan);
                                $jumlahproses++;
                            }
                        }
                        if ($jumlahproses == 0)
                        {
                            echo "Apakah GUID belum dipetakan untuk $tabel?<br/>";
                        }
                    }
                    /* kirim data ke browser setiap 50 proses */
                    if ($proses % 50 == 0)
                    {
                      echo "<br />Inject $tabel ".(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)." - Metode ".$inject["type"]." ($proses dari ".$this->db["result"][$iddb]->num_rows.")...<br />";
                      $this->kirim_buffer();
                    }
                    /* bersih-bersih */
                    unset($data);
                  }
                }
      /*-- update -- */
                else if ($inject["type"]=="update")
                {
                  /* proses data dari Institusi */
                  while($row = $this->db["result"][$iddb]->fetch_assoc())
                  {
                    $proses++;
                    /* siapkan data yang hendak diperbaharui ke pddikti */
                    $data   = array();
                    /* kolom yang diupdate */
                    reset($inject["fieldupdate"]);
                    foreach ($inject["fieldupdate"] as $idx => $pddikti)
                    {
                        /* data null tidak disertakan */
                        if ($row[$this->ignore_alias($this->mapdb["field"][$tabel][$this->ignore_alias($pddikti)])] != null) 
                        {
                            $data["data"][$pddikti] = $row[$this->ignore_alias($this->mapdb["field"][$tabel][$this->ignore_alias($pddikti)])];
                        }
                    }
                    /* siapkan data tambahan */
                    $tambahan = array();
                    print_r($fieldtambahan);
                    reset($fieldtambahan);
                    foreach ($fieldtambahan as $value)
                    {
                      $tambahan[$value] = $row[$value];
                    }
                    /* kolom filter */
                    reset($inject["fieldwhere"]);
                    foreach ($inject["fieldwhere"] as $idx => $pddikti)
                    {
                        /* data null tidak disertakan */
                        if ($row[$this->ignore_alias($this->mapdb["field"][$tabel][$this->ignore_alias($pddikti)])] != null) 
                        {
                            $data["key"][$pddikti]  = $row[$this->ignore_alias($this->mapdb["field"][$tabel][$this->ignore_alias($pddikti)])];
                        }
                    }
                    /* injeksi data ke PDDIKTI */
                    $hasil = $this->UpdateRecord($tabel_asli, $data);
                    /* tampilkan kesalahan jika ada */
                    if ($hasil["result"]["error_desc"] != "" || $hasil["error_desc"] != "")
                    {
                      $error++;
                      $row["no sync"] = $proses;
                      $row["ket"]     = $this->ifnull($hasil["result"]["error_desc"], $hasil["error_desc"])." UpdateRecord(".$tabel_asli.", ".$this->cetak_array($data, true).")";
                      $this->cetak_tabel_parsial($row);
                    }
                    else
                    {
                        $jumlahproses = 0;
                        $idsync     = explode(",", $row[$this->ignore_alias($this->mapdb["pk"][$tabel][1])]);
                        if (!is_array($idsync))
                            $idsync[0] = $row[$this->ignore_alias($this->mapdb["pk"][$tabel][1])];
                        $nilaiid    = array();
                        foreach ($idsync as $nilai)
                        {
                            $x  = explode("_", $nilai);
                            if (empty($x[1]))
                                $nilaiid[0] = $nilai;
                            else
                                $nilaiid[$x[0]] = $x[1];
                        }
                        foreach($this->mapdb["guid"][$tabel] as $idx => $mapdb_guid)
                        {
                            if (!empty($nilaiid[$idx]))
                            {
                                $this->pddikti_sinkronisasi_injek_update($hasil["result"][$this->mapdb["guid"][$tabel][0]["guid"][0]], $mapdb_guid, $mode, $data, $iddb, $proses, $nilaiid[$idx], $tabel, $tambahan);
                                $jumlahproses++;
                            }
                        }
                        if ($jumlahproses == 0)
                        {
                            $idsync     = explode(",", $row[$this->ignore_alias($this->mapdb["pk"][$tabel_asli][1])]);
                            if (!is_array($idsync))
                                $idsync[0] = $row[$this->ignore_alias($this->mapdb["pk"][$tabel_asli][1])];
                            $nilaiid    = array();
                            foreach ($idsync as $nilai)
                            {
                                $x  = explode("_", $nilai);
                                if (empty($x[1]))
                                    $nilaiid[0] = $nilai;
                                else
                                    $nilaiid[$x[0]] = $x[1];
                            }
                            foreach($this->mapdb["guid"][$tabel_asli] as $idx => $mapdb_guid)
                            {
                                if (!empty($nilaiid[$idx]))
                                {
                                    $this->pddikti_sinkronisasi_injek_update($hasil["result"][$this->mapdb["guid"][$tabel_asli][0]["guid"][0]], $mapdb_guid, $mode, $data, $iddb, $proses, $nilaiid[$idx], $tabel_asli, $tambahan);
                                    $jumlahproses++;
                                }
                            }
                            if ($jumlahproses == 0)
                            {
                                echo "Apakah GUID belum dipetakan untuk $tabel_asli dan/atau $tabel?<br/>";
                            }
                        }
                    }
                    /* kirim data ke browser setiap 50 proses */
                    if ($proses % 50 == 0)
                    {
                      echo "<br />Inject $tabel ".(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)." - Metode ".$inject["type"]." ($proses dari ".$this->db["result"][$iddb]->num_rows.")...";
                      $this->kirim_buffer();
                    }
                    /* bersih-bersih */
                    unset($data);
                  }
                }
                $this->cetak_tabel_parsial_akhiri();
                $this->cetak_accordion_akhiri();
              }
          }
          else 
          {
              $this->cetak_accordion_akhiri();
              echo "Tahun Akademik Tidak Aktif. Aktifkan terlebih dahulu Tahun Akademiknya.";
          }
          /* tampilkan informasi proses */
          echo "<br />Inject $tabel ".(($inject["istahunakademikkrs"]) ? $tahunakademikkrs : $tahunakademiksebelum)." - Metode ".$inject["type"]." ($proses dari ".$this->db["result"][$iddb]->num_rows.")...<br />";
          if ($error == 0)
          {
              echo "$proses proses inject/sync berhasil";
          }
          else
          {
              echo "$error kesalahan ditemukan pada $proses proses sync";
          }
          $this->kirim_buffer();
        }
        /* sinkronisasi guid semua data yang sudah diinjek (jika issinkron_injek bernilai true) */
        if ($prosesinjek && $mode != MODE_INJECT_GAGAL)
        {
          if ($mode == MODE_INJECT_MASSAL && $this->issinkron_injek)
          {
            if (array_key_exists("tahunakademik", $inject))
            {
                $this->pddikti_sinkron_guid_filterinjek($tabel, $inject, $tahunakademikkrs, $tahunakademiksebelum, "", $error);
            }
            else
            {
                $this->pddikti_sinkron_guid($tabel, $filter="", $filterIns = "", $error);
            }
          }
          else
          {
              echo "<br/>Sinkron GUID tidak diaktifkan. Sinkron dilakukan begitu data berhasil diinjek<br/>";
          }
        }
        unset($inject);
        unset($param);
      }
      /* bersih-bersih */
      $this->mysqli_bersihkan($iddb);
      $this->mysqli_putus($iddb);
    }

    private function pddikti_sinkronisasi_injek_insert($hasil, $mapdb_guid, $mode, $data, $iddb, $proses, $row, $tabel, $tambahan)
    {
      /*
        * apabila data berhasil dimasukkan, maka segera update guid yang ada di tabel institusi sesuai dengan guid yang didapatkan dari webservice
        * guid berada pada satu kolom
        */
       if ($hasil != "")
       {
         echo "<br /><b>Sisip >>".$proses.">> </b>";
         print_r($data);
         print_r($tambahan);
         echo "<b> [OK]</b>";
         /* sinkronisasi jika modenya adalah injek massal */
         if ($mode == MODE_INJECT_MASSAL || $mode == MODE_INJECT_INDIVIDU)
         {
           $upd  = $hasil;
           $this->mysqli_iud($iddb, "update ".$mapdb_guid["table"]["update"]." set ".$mapdb_guid["guid"][1]."=".$upd." where ".$this->mapdb["pk"][$tabel][1]."='".$row."' and (".$mapdb_guid["guid"][1]."<>".$upd." or isnull(".$mapdb_guid["guid"][1]."))");
           echo "<br /><b>--Sync Sisip >>".$proses.">> </b> sync tabel institusi ".$mapdb_guid["table"]["update"]." dengan data ".$mapdb_guid["guid"][1]."=".$upd." dimana ".$this->mapdb["pk"][$tabel][1]."='".$row."' (jika GUIDnya berbeda) [".$this->db["info"][$iddb]."]";
         }
       }
       /* apabila data berhasil dimasukkan, maka segera update guid yang ada di tabel institusi sesuai dengan guid yang didapatkan dari webservice 
        * guid berada pada lebih dari satu kolom atau tidak terdapat kunci primer pada tabel PDDIKTI
        */
       else
       {
         echo "<br /><b>Sisip >>".$proses.">> </b>";
         print_r($data);
         print_r($tambahan);
         echo "<b> [OK]</b>";
         /* sinkronisasi jika modenya adalah injek massal */
         if ($mode == MODE_INJECT_MASSAL || $mode == MODE_INJECT_INDIVIDU)
         {
           $g    = explode(",", $mapdb_guid["guid"][0]);
           reset($g);
           $upd  = array();
           foreach ($g as $val)
           {
               $upd[]  = "'".$row[$this->ignore_alias($this->mapdb["field"][$tabel][$this->ignore_alias($val)])]."'";
           }
           $upd  = "concat(".implode(",", $upd).")";
           $this->mysqli_iud($iddb, "update ".$mapdb_guid["table"]["update"]." set ".$mapdb_guid["guid"][1]."=".$upd." where ".$this->mapdb["pk"][$tabel][1]."='".$row."' and (".$mapdb_guid["guid"][1]."<>".$upd." or isnull(".$mapdb_guid["guid"][1]."))");
           echo "<br /><b>--Sync Sisip >>".$proses.">> </b> sync tabel institusi ".$mapdb_guid["table"]["update"]." dengan data ".$mapdb_guid["guid"][1]."=".$upd." dimana ".$this->mapdb["pk"][$tabel][1]."='".$row."' (jika GUIDnya berbeda) [".$this->db["info"][$iddb]."]";
         }
       }
    }

    private function pddikti_sinkronisasi_injek_update($hasil, $mapdb_guid, $mode, $data, $iddb, $proses, $row, $tabel, $tambahan)
    {
      /* apabila data berhasil dimasukkan, maka segera update guid yang ada di tabel institusi sesuai dengan guid yang didapatkan dari webservice 
       * guid berada pada satu kolom
       */
      if ($hasil != "")
      {
        echo "<br /><b>Ubah >>".$proses.">> </b>";
        print_r($data);
        print_r($tambahan);
        echo "<b> [OK]</b>";
        if ($mode == MODE_INJECT_MASSAL || $mode == MODE_INJECT_INDIVIDU)
        {
            $upd  = $hasil;
            $this->mysqli_iud($iddb, "update ".$mapdb_guid["table"]["update"]." set ".$mapdb_guid["guid"][1]."='".$upd."' where ".$this->mapdb["pk"][$tabel][1]."='".$row."' and ".$mapdb_guid["guid"][1]."<>'".$upd."'");
            echo "<br /><b>--Sync Ubah >>".$proses.">> </b> sync tabel institusi ".$mapdb_guid["table"]["update"]." dengan data ".$mapdb_guid["guid"][1]."='".$upd."' dimana ".$this->mapdb["pk"][$tabel][1]."='".$row."' (jika GUIDnya berbeda) [".$this->db["info"][$iddb]."]";
        }
      }
      /* apabila data berhasil dimasukkan, maka segera update guid yang ada di tabel institusi sesuai dengan guid yang didapatkan dari webservice 
       * guid berada pada lebih dari satu kolom atau tidak terdapat kunci primer pada tabel PDDIKTI
       */
      else
      {
        echo "<b><br />Ubah >>".$proses.">> </b>";
        //tidak terdapat primary key pada tabel PDDIKTI
        print_r($data);
        print_r($tambahan);
        echo "<b> [OK]</b>";
        if ($mode == MODE_INJECT_MASSAL || $mode == MODE_INJECT_INDIVIDU)
        {
            $upd  = implode("", $data["key"]);
            $this->mysqli_iud($iddb, "update ".$mapdb_guid["table"]["update"]." set ".$mapdb_guid["guid"][1]."='".$upd."' where ".$this->mapdb["pk"][$tabel][1]."='".$row."' and ".$mapdb_guid["guid"][1]."<>'".$upd."'");
            echo "<br /><b>--Sync Ubah >>".$proses.">> </b> sync tabel institusi ".$mapdb_guid["table"]["update"]." dengan data ".$mapdb_guid["guid"][1]."='".$upd."' dimana ".$this->mapdb["pk"][$tabel][1]."='".$row."' (jika GUIDnya berbeda) [".$this->db["info"][$iddb]."]";
        }
      }
    }

    /**
     * memperbaiki peta_injek_usang ke PDDIKTI.<br/>
     * memanggil pddikti_injek dengan $modeinjek bernilai inject_perbaiki_usang dan $ignoreguid bernilai true
     * @param type $tahunakademikkrs
     * - tahun akademik krs
     * @param type $tabelinjectindividual
     * - nama tabel PDDIKTI, jika diisi, maka hanya tabel ini saja yang diinjek - OPSIONAL, default: ""
     */
    function pddikti_injek_perbaiki_usang($tahunakademikkrs, $tabelinjectindividual="")
    {
        $this->pddikti_injek($tahunakademikkrs, $tabelinjectindividual, "inject_perbaiki_usang", true);
    }

    /**
     * mensinkronkan GUID pada tabel-tabel basis data dasar Institusi dari basis data Feeder PDDIKTI
     * <br />satuan_pendidikan (institusi), sms (program studi), kurikulum dan mata_kuliah
     */
    function sinkron_data_institusi()
    {
      //sync Institusi
      $this->pddikti_sinkron_guid("satuan_pendidikan");
      //sync Prodi
      $temp = $this->GetRecord("satuan_pendidikan", "npsn='".$this->pddikti["login"]["username"]."'");
      $this->pddikti_sinkron_guid("sms", "id_sp='".$temp["result"]["id_sp"]."'");
      //sync Kurikulum
      $this->pddikti_sinkron_guid("kurikulum");
      //sync Matakuliah
      $this->pddikti_sinkron_guid("mata_kuliah");
    }

    /*^^ sync DB - WEBSERVICE PDDIKTI ^^*/

    /**
     * mengabaikan alias tabel pada nama kolom, misal: p.kdkrsnilai menjadi kdkrsnilai
     * @param type $str
     * - nama kolom (yang mungkin ada alias tabelnya)
     * @return type
     * - nama kolom tanpa alias tabel
     */
    function ignore_alias($str)
    {
      return trim((substr_count($str, '.') == 0) ? $str : substr($str, strpos($str, '.')+1));
    }

    /**
     * apakah kumpulan kata yang dipisahkan dengan tanda koma ada pada kalimat
     * @param type $haysack
     * - kalimat lengkap
     * @param type $needles
     * - kata dicari
     * @param type $restrict
     * - restrict=true berarti harus semua kata ditemukan
     * @return type
     * - true: terdapat kata, false: tidak terdapat kata
     */
    function is_exist($haysack, $needles, $restrict=false)
    {
      $needle     = explode(",", $needles);
      $found      = 0;
      $tobefound  = count($needle);
      foreach ($needle as $n)
      {
          if (strpos($haysack, $n)!==false) 
          {
              $found++;
          }
      }
      return (($restrict && $found == $tobefound && $found > 0) || (!$restrict && $found > 0)) ? true : false;
    }

    /**
     * mencetak array dalam bentuk yang mudah untuk dibaca
     * @param type $arr
     * - array yang akan ditampilkan
     */
    function print_r_rapi($arr)
    {
      echo "<pre>";
      print_r($arr);
      echo "</pre>";
      /* mengirimkan buffer terakhir ke browser, kemudian membersihkan buffer */
      $this->kirim_buffer();
    }

    /**
     * memulai membuat accordion (harus diakhiri dengan cetak_accordion_akhiri)
     * @param String $judul
     * - judul accordion
     */
    function cetak_accordion_mulai($judul)
    {
        echo "<hr/><button class=\"accordion\" onClick=\"this.classList.toggle('active');var panel = this.nextElementSibling;if (panel.style.display === 'block') { panel.style.display = 'none'; } else { panel.style.display = 'block'; }\">$judul</button>
              <div class=\"panel\">";
    }

    /**
     * menutup accordion (yang dimulai dengan cetak_accordion_mulai)
     */
    function cetak_accordion_akhiri()
    {
        echo "</div>";
    }

    /**
     * mencetak tabel dari array header dan data
     * @param String $header
     * - array header
     * @param String $data
     * - array data
     */
    function cetak_tabel($header, $data)
    {
      echo "<table class='table table-bordered table-striped table-hover'>";
      /* cetak header */
      echo "<thead>";
      echo "<tr>";
      foreach ($header as $idx => $cell)
      {
          echo "<th>$cell</th>";
      }
      echo "</tr>";
      echo "</thead>";
      /* cetak data */
      $row  = 0;
      echo "<tbody>";
      foreach ($data as $idx => $value)
      {
        echo "<tr>";
        for ($i=0; $i<count($header); $i++)
        {
            echo "<td>".$value[$i]."</td>";
        }
        echo "</tr>";
        /* cetak secara parsial */
        if ($row++ % 1000 == 0)
        {
            $this->kirim_buffer();
        }
      }
      echo "</tbody>";
      echo "</table>";
      echo "Terdapat $row baris<br />";
    }
    
    /**
     * mencetak tabel instruksi
     * @param String $href
     * - array alamat eksekusi
     * @param String $data
     * - array data
     */
    function cetak_instruksi($href, $ta, $data)
    {
      $html = "<table class='table table-bordered table-striped-2nd table-hover'>";
      /* cetak data */
      $html .= "<tbody>";
      foreach ($data as $idx => $value)
      {
        $html .= "<tr>";
        $html .= "<td>&amp;n=".$idx."</td><td>".($href != "" ? "<a href='".$href."&t=".$ta."&n=".$idx."'>Eksekusi ".$value." ".((strpos($value, 'TA-1') !== false)?$this->tahunakademiksebelum($ta):$ta)."</a>" : $value)."</td>";
        $html .= "</tr>";
      }
      $html .= "</tbody>";
      $html .= "</table>";
      return $html;
    }

    /**
     * menghitung berapa dimensi array
     * <br/> sumber: http://theserverpages.com/php/manual/en/ref.array.php
     * @param type $array
     * - array yang dihitung dimensinya
     * @return int
     * - dimensi
     */
    function countdim($array)
    {
      if (is_array($array))
      {
        if (is_array(reset($array)))
        { 
            $return = $this->countdim(reset($array)) + 1;
        }
        else
        {
            $return = 1;
        }
      }
      else 
      {
        $return = 0;
      }
      return $return;
    }
    
    /**
     * 
     * @param type $a
     * - array yang akan dicetak (seperti print_r)
     * @return type
     * - teks array
     */
    function cetak_array($a)
    {
        return str_replace(",\n)", ")", var_export($a, true));
    }
    
    /**
     * mencetak string atau array
     * @param type $sel
     * - sel yang akan dicetak
     */
    function cetak_tabel_sel($sel, $t = "td")
    {
        if (is_array($sel))
        {
            echo "<".$t."><pre>";
            print_r($sel);
            echo "</pre></".$t.">";
        }
        else
        {
            echo "<".$t.">".$sel."</".$t.">";
        }
    }

    /**
     *  memulai membuat tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri)
     * @param type $id
     * - id dari tabel, tabel akan dibuat sesuai dengan id-nya
     * @param type $mode
     * - 1 -> echo, 2 -> return
     * @return string
     * - header <tabel>
     */
    function cetak_tabel_parsial_mulai($id=1, $mode=1)
    {
      $this->table_row[$id] = 0;
      /* mode cetak, kirim data ke browser */
      if ($mode == 1)
      {
          echo "<table class='table table-bordered table-striped table-hover'>"; 
      }
      /* mode return */
      else 
      {
          return "<table class='table table-bordered table-striped table-hover'>";
      }
    }

    /**
     * mengisi tabel secara terpisah (harus diakhiri dengan cetak_tabel_parsial_akhiri)
     * @param type $data
     * - isi tabel
     * @param type $ignore_count
     * - jumlah baris tidak perlu dihitung?
     * @param type $id
     * - id dari tabel, tabel akan dibuat sesuai dengan id-nya
     * @param type $mode
     * - 1 -> echo, 2 -> return
     * @param type $include_index
     * - apakah indeks array perlu dicetak?
     * @param type $ishead
     * - true: th, false: td
     * @param type $nonumericindex
     * - true: index of integer excluded, false: index of integer included
     * @return string
     * - <tr><td></td></tr>
     */
    function cetak_tabel_parsial($data, $ignore_count=false, $id=1, $mode=1, $include_index=false, $ishead=false, $nonumericindex=true)
    {
      $t   = ($ishead) ? "th" : "td";
      $ret = "";
      /* menghitung berapa dimensi array */
      $dimensi  = $this->countdim($data);
      /* jika data merupakan array satu dimensi */
      if (is_array($data) && count($data > 0) && $dimensi == 1)
      {
        /* mode echo, cetak */
        if ($mode == 1)
        {
          echo "<tr>";
          if ($include_index)
          {
              $this->cetak_tabel_sel("", $t);
          }
          foreach ($data as $idx => $cell)
          {
              if ($nonumericindex && !$ishead)
              {
                  if (!is_numeric($idx))
                  {
                      $this->cetak_tabel_sel($cell, $t);
                  }
              }
              else
              {
                  $this->cetak_tabel_sel($cell, $t);
              }
          }
          echo "</tr>";
        }
        /* mode return, simpan ke dalam variabel */
        else
        {
          $ret .= "<tr>";
          if ($include_index)
          {
              $ret .= "<".$t."></".$t.">";
          }
          foreach ($data as $idx => $cell)
          {
              if ($nonumericindex && !$ishead)
              {
                  if (!is_numeric($idx))
                  {
                      $ret .= "<".$t.">$cell</".$t.">";
                  }
              }
              else
              {
                  $ret .= "<".$t.">$cell</".$t.">";
              }
          }
          $ret .= "</tr>";
        }
        /* jika tidak ignore_count, simpan informasi jumlah baris */
        if (!$ignore_count)
        {
            $this->table_row[$id]++;
        }
      }
      /* jika data merupakan array dua dimensi (baris dan kolom) */
      else if (is_array($data) && $dimensi == 2)
      {
        /* mode echo, cetak */
        if ($mode == 1)
        {
          foreach ($data as $idx => $value)
          {
            echo "<tr>";
            if ($include_index)
            {
                $this->cetak_tabel_sel($idx, $t);
            }
            foreach ($value as $idx => $cell)
            {
                $this->cetak_tabel_sel($cell, $t);
            }
            echo "</tr>";
            /* jika tidak ignore_count, simpan informasi jumlah baris */
            if (!$ignore_count)
            {
                $this->table_row[$id]++;
            }
          }
        }
        /* mode return, simpan ke dalam variabel */
        else
        {
          foreach ($data as $idx => $value)
          {
            $ret .= "<tr>";
            if ($include_index)
            {
                $ret .= "<".$t.">$idx</".$t.">";
            }
            foreach ($value as $idx => $cell)
            {
                $ret .= "<".$t.">$cell</".$t.">";
            }
            $ret .= "</tr>";
            
            /* jika tidak ignore_count, simpan informasi jumlah baris */
            if (!$ignore_count)
            {
                $this->table_row[$id]++;
            }
          }
        }
      }
      /* bukan array, tidak dibuat tabel */
      else
      {
          exit();
      }
      /* mode cetak, kirim data ke browser */
      if ($mode == 1)
      {
          $this->kirim_buffer(); 
      }
      /* mode return */
      else
      {
          return $ret;
      }
    }

    /**
     * mengisi tabel secara terpisah, tetapi hanya data indeks dari array (harus diakhiri dengan cetak_tabel_parsial_akhiri)
     * @param type $data
     * - isi tabel
     * @param type $mode
     * - 1 -> echo, 2 -> return
     * @param type $include_index
     * - apakah indeks array perlu dicetak?
     * @return string
     * - <tr><td></td></tr>
     */
    function cetak_tabel_parsial_indeks($data, $mode=1, $include_index=false, $ishead=false)
    {
      $t   = ($ishead) ? "th" : "td";
      $ret  = "";
      /* jika data merupakan array */
      if (is_array($data))
      {
        reset($data);
        $arr  = current($data);
        /* mode echo, cetak */
        if ($mode == 1)
        {
          echo "<tr>";
          if ($include_index)
          {
              echo "<".$t.">Item</".$t.">";
          }
          foreach ($arr as $idx => $val)
          {
              echo "<".$t.">".ucwords($idx)."</".$t.">";
          }
          echo "</tr>";
        }
        /* mode return */
        else
        {
          $ret .= "<tr>";
          foreach ($arr as $idx => $val)
          {
              $ret .= "<".$t.">$idx</".$t.">";
          }
          $ret .= "</tr>";
        }
      }
      else
      /* bukan array, tidak dibuat tabel */
      {
        exit();
      }
      /* bersih-bersih */
      unset($arr);
      /* mode cetak, kirim data ke browser */
      if ($mode == 1) 
      {
          $this->kirim_buffer();
      }
      /* mode return */
      else 
      {
          return $ret;
      }
    }

    /**
     * menutup tabel terpisah (yang dimulai dengan cetak_tabel_parsial_mulai)
     * @param type $id
     * - id dari tabel, tabel akan dibuat sesuai dengan id-nya
     * @param type $mode
     * - 1 -> echo, 2 -> return
     * @return string
     * - footer </tabel>
     */
    function cetak_tabel_parsial_akhiri($id=1, $mode=1)
    {
      /* mode cetak, kirim data ke browser */
      if ($mode == 1)
      {
        echo "</table>";
        echo "Terdapat ".$this->table_row[$id]." baris<br />";
        $this->kirim_buffer();
      }
      /* mode return */
      else
      {
        return  "</table>".
                "Terdapat ".$this->table_row[$id]." baris<br />";
      }
    }

    /**
     * membuat array yang memiliki kolom yang sama untuk semua baris, kolom baru berisi data kosong
     * @param type $data
     * - array dua dimensi, yang mungkin kolomnya tidak sama setiap barisnya, 
     *   misal: arrayA = array(array("idx1"=>1, "idx2"=>2), array("idx1"=>3, "idx3"=>4));
     * @return type
     * - array yang memiliki kolom yang sama untuk semua baris, 
     *   output dari permisalan data: arrayA = array(array("idx1"=>1, "idx2"=>2, "idx3" =>), array("idx1"=>3, "idx2"=>, "idx3"=>4));
     */
    function array_auto_fill($data)
    {
      /* buat daftar kolom, catat semua indeks (distinct) */
      $idxList  = array();
      foreach ($data as $element)
      {
        foreach ($element as $idx => $val)
        {
          /* jika indeks tidak ada di daftar kolom, maka ditambahkan ke daftar kolom */
          if (!in_array($idx, $idxList))
          {
              $idxList[$idx]  = $idx;
          }
        }
      }
      /* buat array selebar idxList, kemudian sisipkan data jika pada baris tersebut terdapat kolomnya atau dikosongi jika pada baris tersebut tidak terdapat kolomnya */
      reset($data);
      $tempData = array();
      foreach ($data as $idx => $val)
      {
        reset($idxList);
        foreach ($idxList as $idx_ => $val_)
        {
            /* sisipkan data, jika tidak ada maka akan menyisipkan data kosong */
            $tempData[$idx][$idx_]  = $val[$idx_];
            $val_                   = $val_; //clean up
        }
      }
      /* bersih-bersih */
      unset($idxList);
      return $tempData;
    }
    
    /**
     * Menampilkan pemetaan secara visual, sehingga tidak perlu lihat kode sumber
     */
    function visualisasi_pemetaan_injek()
    {
        $visual = array();
        foreach($this->mapdb["inject"] as $idx_ => $val_)
        {
            $visual[$val_["ignoreinject"]][$idx_] = [   "type"          => $val_["type"], 
                                                        "tahunakademik" => $val_["tandatahunakademik"]." ".$val_["tahunakademik"]." ".(($val_["istahunakademikkrs"]) ? "" : $this->tahunakademiksebelum($val_["tandatahunakademik"])),
                                                        "data"          => ($val_["type"] == "insert") ? $this->mapdb["field"][$idx_] : $val_["fieldupdate"],
                                                        "filter"        => $val_["filter"],
                                                        "ideksekusi"    => $val_["ideksekusi"]
                                                    ];
        }
//        echo "<pre>";
//        print_r($visual[false]);
//        echo "</pre>";
        $this->cetak_tabel_parsial_mulai();
        $this->cetak_tabel_parsial_indeks($visual[false], 1, true);
        $this->cetak_tabel_parsial($visual[true], true, 1, 1, true);
        $this->cetak_tabel_parsial($visual[false], true, 1, 1, true);
        $this->cetak_tabel_parsial_akhiri();
    }
    
    /**
    * return non-null value. If input non null then return input else return alternative
    * Example:
    * $a = 12;
    * echo ifnull($a, 0);  - output: 12
    * echo ifnull($x, 0);  - output: 0
    * @param type $input       Input to check
    * @param type $alternative Alternative if input is null or not set yet
    * @return type non null output
    */
    function ifnull($input, $alternative)
    {
        return (!isset($input) || is_null($input) || trim($input) == "") ? $alternative : $input;
    }

    function get_url()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI] -- $_SERVER[HTTP_REFERER]";
    }
    
    function get_url_nonget()
    {
        return explode('?', $this->get_url(), 2)[0];
    }
    
    /**
     * membuat string acak
     * @param type $panjang
     * - panjang string acak
     * @return string 
     * - string acak
     */
    function generaterandom($panjang)
    {
        $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
        $hasil  = '';
        for($i=0; $i< $panjang; $i++)
        {
            $str = rand(0, strlen($char)-1);
            $hasil .= $char{$str};
        }

        return $hasil;
    }
}
