<?php
/** 
 * init.inc.php
 * <br/> untuk inisialisasi proses sebelum melakukan aksi yang lain
 * <br/> profil  https://id.linkedin.com/in/basitadhi
 * <br/> buat    2015-10-30
 * <br/> rev     2021-10-01
 * <br/> sifat   open source
 * @author Basit Adhi Prabowo, S.T. <basit@unisayogya.ac.id>
 * @access public
 */
error_reporting(E_ERROR);
require_once ("config.inc.php");
require_once ("webservice.inc.php");
require_once ("mapping.inc.php");
?>
<html>
<head>
<link rel="stylesheet"  href="gaya.css" />
</head>
<body>
<?php
session_start();
if ($_SESSION["passthru"] == "leres")
{
    session_write_close();
    $mapdb  = new mapdb();
    $ws     = new webservice($pddikti, $institusi, false, true);
    $ws->setMapdb($mapdb->peta());
    if ($ws->log["islog"])
    {
        $ws->cetak_log();
    }
    $ws->ignorelog = false;
    $_a     = filter_input(INPUT_GET, "a", FILTER_SANITIZE_NUMBER_INT);

    function keteranganTA($petainject, $index)
    {
        return " [".($petainject[$index]["istahunakademikkrs"]?"TA":"TA-1")."]";
    }

    $daftar =   [   "1" => "mata_kuliah_kurikulum (TA tidak dihiraukan)",
                    "2" => "kelas_kuliah".keteranganTA($mapdb->peta["inject"], "kelas_kuliah"),
                    "3"  => "mahasiswa".keteranganTA($mapdb->peta["inject"], "mahasiswa"),
                    "4"  => "mahasiswa_pt".keteranganTA($mapdb->peta["inject"], "mahasiswa_pt"),
                    "5"  => "nilai_transfer".keteranganTA($mapdb->peta["inject"], "nilai transfer"),
                    "6"  => "nilai krs".keteranganTA($mapdb->peta["inject"], "nilai krs"),
                    "7"  => "nilai update".keteranganTA($mapdb->peta["inject"], "nilai update"),
                    "8"  => "kuliah_mahasiswa".keteranganTA($mapdb->peta["inject"], "kuliah_mahasiswa"),
                    "9"  => "mahasiswa_pt keluar".keteranganTA($mapdb->peta["inject"], "mahasiswa_pt keluar"),
                    "10" => "mahasiswa_pt lulus".keteranganTA($mapdb->peta["inject"], "mahasiswa_pt lulus"),
                    "11" => "kuliah_mahasiswa aktif".keteranganTA($mapdb->peta["inject"], "kuliah_mahasiswa aktif"),
                    "12" => "kuliah_mahasiswa aktif_update (ips, ipk)".keteranganTA($mapdb->peta["inject"], "kuliah_mahasiswa aktif_update"),
                    "13" => "dosen pembimbing".keteranganTA($mapdb->peta["inject"], "bimbing_mahasiswa tugasakhir").", dosen penguji".keteranganTA($mapdb->peta["inject"], "uji_mahasiswa tugasakhir")." dan aktivitas_mahasiswa tugasakhir".keteranganTA($mapdb->peta["inject"], "aktivitas_mahasiswa tugasakhir"),
                    "14" => "ajar_dosen".keteranganTA($mapdb->peta["inject"], "ajar_dosen"),
                    "15" => "dosen pembimbing".keteranganTA($mapdb->peta["inject"], "bimbing_mahasiswa prestasi")." dan aktivitas_mahasiswa prestasi".keteranganTA($mapdb->peta["inject"], "aktivitas_mahasiswa prestasi"),
    //                "16" => "dosen pembimbing".keteranganTA($mapdb->peta["inject"], "bimbing_mahasiswa bimbing_aka")." dan aktivitas_mahasiswa bimbing_aka".keteranganTA($mapdb->peta["inject"], "aktivitas_mahasiswa bimbing_aka"),
                ];
    $daftarinjek    = [1,2,3,4,5,6,7,8,9,10,11,13,14,15,16];
    $daftarsync     = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16];
    $daftarupdate   = [3,4,12,14];

    //$ws->print_r_rapi($ws->cetak_recordset("bimbing_mahasiswa", "id_akt_mhs='74ae1a3a-eced-4d26-a881-b7676ae2fec2'", "", 1000));

    //$ws->print_r_rapi($ws->cetak_recordset("kuliah_mahasiswa", "p.id_reg_pd='ffcacbe9-c275-411f-8b84-a1ac5b7ae310'", "", 1000));
    //print_r($ws->UpdateRecord('mahasiswa_pt', array ( 'data' => array ( 'id_jns_keluar' => '1', 'tgl_keluar' => '2019-10-31', 'sk_yudisium' => ' /KR-UNISA/Ad/X/2019', 'tgl_sk_yudisium' => '2019-10-31', 'ipk' => '3,23', 'no_seri_ijazah' => 'UNISA/KEB-III/IJZ/1019.001'), 'key' => array ( 'id_reg_pd' => 'b32493d6-4a71-48e6-a09d-e0a4ab758615'))));
    //$ws->print_r_rapi($ws->cetak_recordset("kelas_kuliah", "", "", 10));
    //print_r($ws->GetCountRecordset("jenis_aktivitas_mahasiswa"));
    //$ws->print_r_rapi($ws->cetak_recordset("penugasan"));
    //$ws->print_r_rapi($ws->cetak_recordset("kuliah_mahasiswa", "p.id_reg_pd='805cf814-99cf-4dcb-b5cf-6c76fa5401f3' and p.id_smt='20152' and p.id_stat_mhs='C'", "", 1000));
    $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
    echo '<script type="text/javascript">
            window.addEventListener(\'keydown\',function(e){if(e.keyIdentifier==\'U+000A\'||e.keyIdentifier==\'Enter\'||e.keyCode==13){if(e.target.nodeName==\'INPUT\'&&e.target.type==\'text\'){e.preventDefault();return false;}}},true);
          </script>
          <form action="">
            <input type="text" id="ta" value="'.$_t.'" />
            <input type="button" value="Set Tahun Akademik" onclick="window.location.href=\'?t=\'+document.getElementById(\'ta\').value"/>
          </form>';
    echo "<table class='table table-bordered table-striped table-hover'>"
        . "       <thead>"
        . "       <tr><th width='200px'>Kode</th><th>Aksi</th></tr>"
        . "       </thead>"
        . "       <tbody>"
        . "       <tr><td>?a=1</td><td><a href='?a=1'>Eksekusi Cek data tabel referensi </a></td></tr>"
        . "       <tr><td>?a=2</td><td><a href='?a=2'>Eksekusi Sinkronisasi PT, Prodi, Kurikulum dan Matakuliah </a></td></tr>"
        . "       <tr><td>?a=3</td><td><a href='?a=3'>Eksekusi Lihat deskripsi </a></td></tr>"
        . "       <tr><td>?a=4&t=tahunakademik</td><td>Injek pada tahunakademik".$ws->cetak_instruksi(($_t > 0) ? "?a=4" : "", $_t, array_flip(array_intersect(array_flip($daftar), $daftarinjek)))."</td></tr>"
        . "       <tr><td>?a=5&t=tahunakademik</td><td>Sync pada tahunakademik".$ws->cetak_instruksi(($_t > 0) ? "?a=5" : "", $_t, array_flip(array_intersect(array_flip($daftar), $daftarsync)))."</td></tr>"
        . "       <tr><td>?a=6&t=tahunakademik</td><td>Update data pada tahunakademik".$ws->cetak_instruksi(($_t > 0) ? "?a=6" : "", $_t, array_flip(array_intersect(array_flip($daftar), $daftarupdate)))."</td></tr>"
    //                    . "       <tr><td>?a=6&t=tahunakademik</td><td>Update data personal pada tahunakademik</td></tr>"
    //                    . "       <tr><td>?a=7&t=tahunakademik</td><td>Update data mahasiswa pada tahunakademik</td></tr>"
        . "       <tr><td>?a=7&t=tahunakademik</td><td>".(($_t > 0) ? "<a href='?a=7&t=".$_t."'>" : "")."Eksekusi Mengupdate data NIDN/NUPN di tabel Institusi, memasukkan data dosen_pt dari PDDIKTI ke Institusi dan Cek penugasan dalam 1 tahun ".(($_t > 0) ? $_t."</a> " : "")."</td></tr>"
    //                    . "       <tr><td>?a=9&t=tahunakademik</td><td>Isi rekap mengajar dosen (penugasan harus sudah dilakukan). Awas!!! Per tahun akademik hanya dapat dilakukan satu kali karena modenya adalah sisip, tidak (belum) ada mode update.</td></tr>"
        . "       <tr><td>?a=8</td><td><a href='?a=8'>Eksekusi Lihat pemetaan </a></td></tr>"
        . "       <tr><td>?a=9</td><td><a href='?a=9'>Ekstrak Data PT dan Prodi seluruh Dunia </a></td></tr>"        
        . "       </tbody>"
        . "</table>Note: Untuk nama dengan petik, sementara sinkronisasinya dilakukan secara manual<br/>";
    switch ($_a)
    {
        case 1: $ws->cek_tabel(array(), array("satuan_pendidikan","sms")); break;
        case 2: $ws->pddikti_sinkron_guid("satuan_pendidikan institusi"); 
                $temp = $ws->GetRecord("satuan_pendidikan", "npsn='".$pddikti["login"]["username"]."'");
                $ws->pddikti_sinkron_guid("sms institusi", "id_sp='".$temp["result"]["id_sp"]."'");
                $ws->pddikti_sinkron_guid("kurikulum");
                $ws->pddikti_sinkron_guid("mata_kuliah");
                $ws->pddikti_sinkron_guid("dosen");
                break;
        case 3: $ws->GetDictionary_SemuaTabel(); break;
        case 4: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
                if ($_t > 0)
                {
                    $_n = filter_input(INPUT_GET, "n", FILTER_SANITIZE_NUMBER_INT);
                    switch ($_n)
                    {
                        case 1:     $ws->pddikti_injek($_t, "mata_kuliah_kurikulum"); break;
                        case 2:     $ws->pddikti_injek($_t, "kelas_kuliah"); break;
                        case 3:     $ws->pddikti_injek($_t, "mahasiswa"); break;
                        case 4:     $ws->pddikti_injek($_t, "mahasiswa_pt"); break;
                        case 5:     $ws->pddikti_injek($_t, "nilai_transfer"); break;
                        case 6:     $ws->pddikti_injek($_t, "nilai krs"); break;
                        case 7:     $ws->pddikti_injek($_t, "nilai update"); break;
                        case 8:     $ws->pddikti_injek($_t, "kuliah_mahasiswa"); break;
                        case 9:     $ws->pddikti_injek($_t, "mahasiswa_pt keluar"); break;
                        case 10:    $ws->pddikti_injek($_t, "mahasiswa_pt lulus"); break;
                        case 11:    $ws->pddikti_injek($_t, "kuliah_mahasiswa aktif"); break;
                        case 12:    break;
                        case 13:    $ws->pddikti_injek($_t, "aktivitas_mahasiswa tugasakhir");
                                    $ws->pddikti_injek($_t, "anggota_aktivitas_mahasiswa tugasakhir");
                                    $ws->pddikti_injek($_t, "bimbing_mahasiswa tugasakhir");
                                    $ws->pddikti_injek($_t, "uji_mahasiswa tugasakhir");
                                    break;
                        case 14:    $ws->pddikti_injek($_t, "ajar_dosen"); break;
                        case 15:    $ws->pddikti_injek($_t, "aktivitas_mahasiswa prestasi");
                                    $ws->pddikti_injek($_t, "anggota_aktivitas_mahasiswa prestasi");
                                    $ws->pddikti_injek($_t, "bimbing_mahasiswa prestasi");
                                    break;
                        case 16:    $ws->pddikti_injek($_t, "aktivitas_mahasiswa bimbing_aka");
                                    $ws->pddikti_injek($_t, "anggota_aktivitas_mahasiswa bimbing_aka");
                                    $ws->pddikti_injek($_t, "bimbing_mahasiswa bimbing_aka");
                                    break;
                        default:    $ws->pddikti_injek($_t); break;
                    }
                }
                else
                {
                    echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
                }
                break;
        case 5: if ($_t > 0)
                {
                    $_n     = filter_input(INPUT_GET, "n", FILTER_SANITIZE_NUMBER_INT);
                    $inject = array();
                    switch ($_n)
                    {
                        case 1:     $inject = [ "mata_kuliah_kurikulum" => $mapdb->peta["inject"]["mata_kuliah_kurikulum"] ]; break;
                        case 2:     $inject = [ "kelas_kuliah" => $mapdb->peta["inject"]["kelas_kuliah"] ]; break;
                        case 3:     $inject = [ "mahasiswa" => $mapdb->peta["inject"]["mahasiswa"] ]; break;
                        case 4:     $inject = [ "mahasiswa_pt" => $mapdb->peta["inject"]["mahasiswa_pt"] ]; break;
                        case 5:     $inject = [ "nilai_transfer" => $mapdb->peta["inject"]["nilai_transfer"] ]; break;
                        case 6:     $inject = [ "nilai krs" => $mapdb->peta["inject"]["nilai krs"] ]; break;
                        case 7:     $inject = [ "nilai update" => $mapdb->peta["inject"]["nilai update"] ]; break;
                        case 8:     $inject = [ "kuliah_mahasiswa" => $mapdb->peta["inject"]["kuliah_mahasiswa"] ]; break;
                        case 9:     $inject = [ "mahasiswa_pt keluar" => $mapdb->peta["inject"]["mahasiswa_pt keluar"] ]; break;
                        case 10:    $inject = [ "mahasiswa_pt lulus" => $mapdb->peta["inject"]["mahasiswa_pt lulus"] ]; break;
                        case 11:    $inject = [ "kuliah_mahasiswa aktif" => $mapdb->peta["inject"]["kuliah_mahasiswa aktif"] ]; break;
                        case 12:    $inject = [ "kuliah_mahasiswa aktif_update" => $mapdb->peta["inject"]["kuliah_mahasiswa aktif_update"] ]; break;
                        case 13:    $inject = [ "aktivitas_mahasiswa tugasakhir" => $mapdb->peta["inject"]["aktivitas_mahasiswa tugasakhir"] ,
                                                "anggota_aktivitas_mahasiswa tugasakhir" => $mapdb->peta["inject"]["anggota_aktivitas_mahasiswa tugasakhir"],
                                                "bimbing_mahasiswa tugasakhir" => $mapdb->peta["inject"]["bimbing_mahasiswa tugasakhir"],
                                                "uji_mahasiswa tugasakhir" => $mapdb->peta["inject"]["uji_mahasiswa tugasakhir"]
                                              ];
                                    break;
                        case 14:    $inject = [ "ajar_dosen" => $mapdb->peta["inject"]["ajar_dosen"] ]; break;
                        case 15:    $inject = [ "aktivitas_mahasiswa prestasi" => $mapdb->peta["inject"]["aktivitas_mahasiswa prestasi"] ,
                                                "anggota_aktivitas_mahasiswa prestasi" => $mapdb->peta["inject"]["anggota_aktivitas_mahasiswa prestasi"],
                                                "bimbing_mahasiswa prestasi" => $mapdb->peta["inject"]["bimbing_mahasiswa prestasi"]
                                              ];
                                    break;
                        case 16:    $inject = [ "aktivitas_mahasiswa bimbing_aka" => $mapdb->peta["inject"]["aktivitas_mahasiswa bimbing_aka"] ,
                                                "anggota_aktivitas_mahasiswa bimbing_aka" => $mapdb->peta["inject"]["anggota_aktivitas_mahasiswa bimbing_aka"],
                                                "bimbing_mahasiswa bimbing_aka" => $mapdb->peta["inject"]["bimbing_mahasiswa bimbing_aka"]
                                              ];
                                    break;
                        default:    break;
                    }
                    foreach ($inject as $tbl => $ij)
                    {
                        if (array_key_exists("tahunakademik", $ij))
                        {
                            $ws->pddikti_sinkron_guid_filterinjek($tbl, $ij, $_t, $ws->tahunakademiksebelum($_t), "", 0);
                        }
                        else
                        {
                            $ws->pddikti_sinkron_guid($tbl, "", "", 0);
                        }
                    }
                }
                else
                {
                    echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
                }
                break;
        case 6: if ($_t > 0)
                {
                    $_n = filter_input(INPUT_GET, "n", FILTER_SANITIZE_NUMBER_INT);
                    switch ($_n)
                    {
                        case 1:     break;
                        case 2:     break;
                        case 3:     $ws->pddikti_injek($_t, "mahasiswa updatedata"); break;
                        case 4:     $ws->pddikti_injek($_t, "mahasiswa_pt updatedata"); break;
                        case 5:     break;
                        case 6:     break;
                        case 7:     break;
                        case 8:     break;
                        case 9:     break;
                        case 10:    $ws->pddikti_injek($_t, "mahasiswa_pt lulus_updatedata"); break;
                        case 11:    break;
                        case 12:    $ws->pddikti_injek($_t, "kuliah_mahasiswa aktif_update"); break;
                        case 13:    break;
                        case 14:    $ws->pddikti_injek($_t, "ajar_dosen updatedata"); break;
                        case 15:    break;
                        default:    break;
                    }
                }
                else
                {
                    echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
                }
    //            if ($_t > 0)
    //            {
    //                $ws->pddikti_injek($_t, "mahasiswa updatedata");
    //            }
    //            else
    //            {
    //                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
    //            }
                break;
    //    case 7: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
    //            if ($_t > 0)
    //            {
    //                $ws->pddikti_injek($_t, "mahasiswa_pt updatedata");
    //            }
    //            else
    //            {
    //                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
    //            }
    //            break;
        case 7: if ($_t > 0)
                {
                    $ws->pddikti_sinkron_guid("dosen");
                    $ws->cek_penugasan($_t);
                }
                else
                {
                    echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
                }
                break;
    //    case 9: $_t = filter_input(INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT);
    //            if ($_t > 0)
    //            {
    //                $ws->pddikti_injek($_t, "ajar_dosen");
    //            }
    //            else
    //            {
    //                echo "Mode ini mengharuskan adanya variabel GET t yang berisi Tahun Akademik";
    //            }
    //            break;
        case 8: $ws->visualisasi_pemetaan_injek();
                break;
        case 9: $ws->pddikti_ekstrak(0, "satuan_pendidikan");
                $ws->pddikti_ekstrak(0, "sms");
                break;
        default :   break;
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
    //
    //  
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

    $ws->ignorelog = true;
    $ws->kirim_buffer();
    $ws->akhirwebservice(); 
    //bersih-bersih
    unset($pddikti, $institusi);
    unset($temp);
}
else 
{
    session_write_close();
    header("location: login.php");
}
?>
</body>
</html>
