<?php
/** 
 * init.inc.php
 * <br/> untuk inisialisasi proses sebelum melakukan aksi yang lain
 * <br/> profil  https://id.linkedin.com/in/basitadhi
 * <br/> buat    2015-10-30
 * <br/> rev     2018-03-27
 * <br/> sifat   open source
 * @author Basit Adhi Prabowo, S.T. <basit@unisayogya.ac.id>
 * @access public
 */

error_reporting(E_ERROR);
require_once ("config.inc.php");
require_once ("webservice.inc.php");
require_once ("mapping.inc.php");
session_start();
$mapdb  = new mapdb();
$ws     = new webservice($pddikti, $institusi);
$ws->setMapdb($mapdb->peta());
$_a     = filter_input(INPUT_GET, "a", FILTER_SANITIZE_NUMBER_INT);
switch ($_a)
{
    case 1: $ws->cek_tabel(); break;
    case 2: $ws->pddikti_sinkron_guid("satuan_pendidikan"); 
            $temp = $ws->GetRecord("satuan_pendidikan", "npsn='".$pddikti["login"]["username"]."'");
            $ws->pddikti_sinkron_guid("sms", "id_sp='".$temp["result"]["id_sp"]."'");
            $ws->pddikti_sinkron_guid("kurikulum");
            $ws->pddikti_sinkron_guid("mata_kuliah");
            break;
    case 3: $ws->GetDictionary_SemuaTabel(); break;
    case 4: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
            if ($_t > 0)
            {
                $ws->pddikti_injek($_t);
            }
            else
            {
                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
            }
            break;
    case 5: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
            if ($_t > 0)
            {
                $ws->pddikti_injek($_t, "mahasiswa updatedata");
            }
            else
            {
                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
            }
            break;
    case 6: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
            if ($_t > 0)
            {
                $ws->pddikti_injek($_t, "mahasiswa_pt updatedata");
            }
            else
            {
                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
            }
            break;
    case 7: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
            if ($_t > 0)
            {
                $ws->pddikti_sinkron_guid("dosen");
                $ws->cek_penugasan($_t);
            }
            else
            {
                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
            }
            break;
    case 8: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
            if ($_t > 0)
            {
                $ws->pddikti_injek($_t, "ajar_dosen");
            }
            else
            {
                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
            }
            break;  
    default :   echo "<table border=1 cellspacing=2 cellpadding=2>"
                    . "       <tr><th>Kode</th><th>Aksi</th></tr>"
                    . "       <tr><td>?a=1</td><td>Cek data tabel referensi</td></tr>"
                    . "       <tr><td>?a=2</td><td>Sinkronisasi PT, Prodi, Kurikulum dan Matakuliah</td></tr>"
                    . "       <tr><td>?a=3</td><td>Lihat deskripsi</td></tr>"
                    . "       <tr><td>?a=4&t=tahunakademik</td><td>Injek pada tahunakademik</td></tr>"
                    . "       <tr><td>?a=5&t=tahunakademik</td><td>Update data personal pada tahunakademik</td></tr>"
                    . "       <tr><td>?a=6&t=tahunakademik</td><td>Update data mahasiswa pada tahunakademik</td></tr>"
                    . "       <tr><td>?a=7&t=tahunakademik</td><td>Mengupdate data NIDN/NUPN di tabel Institusi, memasukkan data dosen_pt dari PDDIKTI ke Institusi dan Cek penugasan dalam 1 tahun.</td></tr>"
                    . "       <tr><td>?a=8&t=tahunakademik</td><td>Isi rekap mengajar dosen (penugasan harus sudah dilakukan). Awas!!! Per tahun akademik hanya dapat dilakukan satu kali karena modenya adalah sisip, tidak (belum) ada mode update.</td></tr>"
                    . "</table>";
                break;
}

//$ws->GetDictionary_SemuaTabel();
//print_r($ws->pddikti["proxy"]->GetJenisPendaftaran());

/* langkah 1 */
//  $ws->check_table(array("kebutuhan_khusus"));
/* langkah 2 */
//sync PT
//  $ws->pddikti_sinkron_guid("satuan_pendidikan");
//sync Prodi
//**  $temp = $ws->GetRecord("satuan_pendidikan", "npsn='051022'");
//**  $ws->pddikti_sinkron_guid("sms", "id_sp='".$temp["result"]["id_sp"]."'");
//sync Kurikulum
//**  $ws->pddikti_sinkron_guid("kurikulum");
//  $ws->print_r_rapi($ws->cetak_recordset("kurikulum"));
//sync Matakuliah
//**  $ws->pddikti_sinkron_guid("mata_kuliah");
//sync Matakuliah-Kurikulum
//  $ws->pddikti_sinkron_guid("mata_kuliah_kurikulum");
//sync Penawaran Matakuliah
//  $ws->pddikti_sinkron_guid("kelas_kuliah");
//sync Person
 // $ws->pddikti_sinkron_guid("mahasiswa");
//sync Mahasiswa_PT
  //$ws->pddikti_sinkron_guid("mahasiswa_pt");
//sync Keaktifan Mahasiswa
//  $ws->pddikti_sinkron_guid("nilai krs");
//  $ws->print_r_rapi($ws->cetak_recordset("nilai", "p.id_kls='9a41d8cc-7668-4a44-8fa7-fb30a89a28ed' and p.id_reg_pd='c4daa3dc-db18-46a3-a6ce-cefbe5112f36'", "", 100000));
//Update NIDN
//  $ws->update_nidn();
//$ws->print_r_rapi($ws->cetak_recordset("nilai", "id_kls='25d884ae-3f78-4053-abfb-59fe2dca6419' and id_reg_pd='42e5dbb2-774a-4a58-8161-9a6a6c911d5c'", "", 1000));
//insert equiv
//$ws->cek_penugasan(20161);
//$ws->print_r_rapi($ws->cetak_recordset("dosen_pt", "t.id_thn_ajaran=2015", "", 1000));
/* langkah 3 */
//INJECT
//$ws->pddikti_sinkron_guid("mahasiswa_pt lulus_mahasiswa", "", "ri.kdtahunakademik=20142");
/*$ws->print_r_rapi($ws->cetak_recordset("tahun_ajaran", "", "", 1000));
$ws->print_r_rapi($ws->cetak_recordset("semester", "", "", 1000));
$ws->pddikti_injek(20141);
$ws->pddikti_injek(20142);
$ws->pddikti_injek(20151);*/

/*$ws->pddikti_injek(20161, "mahasiswa");
$ws->pddikti_injek(20152, "mahasiswa");
$ws->pddikti_injek(20151, "mahasiswa");
$ws->pddikti_injek(20142, "mahasiswa");
$ws->pddikti_injek(20141, "mahasiswa");
$ws->pddikti_injek(20132, "mahasiswa");
$ws->pddikti_injek(20131, "mahasiswa");
$ws->pddikti_injek(20122, "mahasiswa");
$ws->pddikti_injek(20121, "mahasiswa");
$ws->pddikti_injek(20112, "mahasiswa");
$ws->pddikti_injek(20111, "mahasiswa");
$ws->pddikti_injek(20102, "mahasiswa");
$ws->pddikti_injek(20101, "mahasiswa");
$ws->pddikti_injek(20092, "mahasiswa");
$ws->pddikti_injek(20091, "mahasiswa");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20161");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20152");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20151");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20142");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20141");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20132");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20131");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20122");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20121");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20112");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20111");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20102");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20101");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20092");
$ws->pddikti_sinkron_guid( "mahasiswa","","kdtamasuk=20091");
$ws->pddikti_injek(20161, "mahasiswa_pt");
$ws->pddikti_injek(20152, "mahasiswa_pt");
$ws->pddikti_injek(20151, "mahasiswa_pt");
$ws->pddikti_injek(20142, "mahasiswa_pt");
$ws->pddikti_injek(20141, "mahasiswa_pt");
$ws->pddikti_injek(20132, "mahasiswa_pt");
$ws->pddikti_injek(20131, "mahasiswa_pt");
$ws->pddikti_injek(20122, "mahasiswa_pt");
$ws->pddikti_injek(20121, "mahasiswa_pt");
$ws->pddikti_injek(20112, "mahasiswa_pt");
$ws->pddikti_injek(20111, "mahasiswa_pt");
$ws->pddikti_injek(20102, "mahasiswa_pt");
$ws->pddikti_injek(20101, "mahasiswa_pt");
$ws->pddikti_injek(20092, "mahasiswa_pt");
$ws->pddikti_injek(20091, "mahasiswa_pt");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20161");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20152");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20151");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20142");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20141");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20132");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20131");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20122");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20121");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20112");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20111");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20102");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20101");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20092");
$ws->pddikti_sinkron_guid( "mahasiswa_pt","","kdtamasuk=20091");*/

//$ws->setIssinkron_injek(false);
//$ws->sinkron_data_institusi();
//$ws->pddikti_injek(20152);
//$ws->pddikti_injek(20161);
//$ws->pddikti_injek(20162, "nilai_transfer");
//$ws->pddikti_injek(20171, "nilai_transfer");
//$ws->pddikti_injek(20162, "nilai krs");
//$ws->pddikti_injek(20171, "nilai krs");
//$ws->pddikti_injek(20162, "nilai update");
//$ws->pddikti_injek(20171, "nilai update");
//$ws->pddikti_injek(20162, "mahasiswa_pt lulus");
//$ws->pddikti_injek(20172, "mahasiswa_pt lulus");




//$ws->pddikti_injek(20152, "mahasiswa_pt updatedata");
//$ws->pddikti_injek(20161, "mahasiswa_pt updatedata");
/*$ws->pddikti_injek(20142, "mahasiswa updatedata");
$ws->pddikti_injek(20131, "mahasiswa updatedata");
$ws->pddikti_injek(20132, "mahasiswa updatedata");
$ws->pddikti_injek(20121, "mahasiswa updatedata");
$ws->pddikti_injek(20122, "mahasiswa updatedata");
$ws->pddikti_injek(20111, "mahasiswa updatedata");
$ws->pddikti_injek(20112, "mahasiswa updatedata");
$ws->pddikti_injek(20101, "mahasiswa updatedata");
$ws->pddikti_injek(20102, "mahasiswa updatedata");
*/
//$ws->print_r_rapi($ws->GetDictionary("mahasiswa"));
//$ws->print_r_rapi($ws->GetDictionary("mahasiswa_pt"));



//ditanyakan ke KOPERTIS
//$ws->print_r_rapi($ws->cetak_recordset("ajar_dosen", "p.id_reg_ptk='9910f9cd-c474-41e2-b975-ef447342af40' and k.id_kls='c2b46f08-3aa1-4914-b428-8dfe21666c6f'", "", 1000));

$ws->kirim_buffer();
$ws->akhirwebservice(); 
//bersih-bersih
unset($pddikti, $institusi);
unset($temp);
