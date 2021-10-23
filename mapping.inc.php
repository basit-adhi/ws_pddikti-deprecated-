<?php


/** 
 * mapping.inc.php
 * <br/> untuk melakukan pemetaan antara basis data Institusi dengan basis data PDDIKTI
 * <br/> profil  https://id.linkedin.com/in/basitadhi
 * <br/> buat    2015-10-30
 * <br/> rev     2021-10-01
 * <br/> sifat   open source
 * <br/> <a href="https://pdsi.unisayogya.ac.id/wordpress_bpti/wp-content/uploads/2017/04/mapdb.ppt">Dokumentasi MapDB</a>
 * <br/> <img src="mapdb2.png" />
 * <br/> <img src="mapdb.png" />
 * <br/> * Pada peta["inject"], jika terdapat indeks "jenisfilter" berisi "internalfilter", maka tabel berisi query yang terdapat [internalfilter], misal: select * from A where where [internalfilter]
 * <br/> catatan:
 * <br/> 1. rule nama kolom PDDIKTI:
 * <br/> --- dengan raw.  -> nama kolom akan ditampilkan tanpa alias, contoh: raw.kolom1 akan ditampilkan kolom1
 * <br/> --- tanpa  raw.  -> diberikan fungsi trim pada nama kolom,   contoh: kolom1     akan ditampilkan trim(kolom1)
 * <br/> --- dengan alias -> nama kolom akan ditampilkan apa adanya,  contoh: p.kolom1   akan ditampilkan p.kolom1
 * <br/> 2. yang dimaksud tabel institusi dapat berupa tabel, view atau query
 * <br/> 3. peta yang harus diisi: 
 * <br/> -- a. webservice::cek_tabel()------------ : field, table, pk
 * <br/> -- b. webservice::pddikti_sinkron_guid()- : guid, pk
 * <br/> -- c. webservice::pddikti_injek()-------- : field, inject, lihat 2. Sync GUID
 * <br/> -- d. webservice::pddikti_ekstrak()------ : field, extract
 * @author Basit Adhi Prabowo, S.T. <basit@unisayogya.ac.id>
 * @access public
 * @todo Penanganan data dengan tanda petik, misalnya pada nama
 */
class mapdb
{
    /**
     *  pemetaaan tabel dan kolom Institusi dengan PDDIKTI 
     */
    var $peta; 
    /**
     *  apakah pemetaan sudah dipetakan? 
     */
    var $isdipetakan=false; 
    
    /**
     * pemetaan kolom PDDIKTI dan Institusi
     * <br/> indeks:
     * <br/> - field
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array(field1.1_pddikti => field1.1_institusi, field1.2_pddikti => field1.2_institusi, ...),
     * <br/> nama_tabel_pddikti2 => array(field2.1_pddikti => field2.1_institusi, field2.2_pddikti => field2.2_institusi, ...),
     * <br/> ...
     * <br/> catatan:
     * <br/> - primary key harus di kolom pertama
     */
    private function peta_kolom()
    {
        $this->peta["field"] = array    (   /* tabel Referensi */
                                            "agama"                                     => array("id_agama" => "kdagama", "nm_agama" => "agama"),
                                            "bentuk_pendidikan"                         => array("id_bp" => "", "nm_bp" => "", "a_jenj_paud" => "", "a_jenj_tk" => "", "a_jenj_sd" => "", "a_jenj_smp" => "", "a_jenj_sma" => "", "a_jenj_tinggi" => "", "dir_bina" => "", "a_aktif" => ""),
                                            "ikatan_kerja_dosen"                        => array("id_ikatan_kerja" => "", "nm_ikatan_kerja" => "", "ket_ikatan_kerja" => ""),
                                            "semester"                                  => array("id_smt" => "kdtahunakademik", "id_thn_ajaran" => "tahunajaran", "nm_smt" => "tahunakademik", "smt" => "semester", "a_periode_aktif" => "isaktif", "tgl_mulai" => "tanggalawal", "tgl_akhir" => "tanggalakhir"),
                                            "jurusan"                                   => array("id_jur" => "kodeprodi", "nm_jur" => "namaprodi", "nm_intl_jur" => "", "u_sma" => "", "u_smk" => "", "u_pt" => "", "u_slb" => "", "id_jenj_didik" => "", "id_induk_jurusan" => "", "id_kel_bidang" => ""),
                                            "jabfung"                                   => array("id_jabfung" => "kdjafa", "nm_jabfung" => "jafa"),
                                            "ikatan_kerja_dosen"                        => array("id_ikatan_kerja" => "kdikatankerjadosen", "nm_ikatan_kerja" => "ikatankerjadosen", "ket_ikatan_kerja" => ""),
                                            "jenis_keluar"                              => array("id_jenis_keluar" => "kdjeniskeluar", "ket_keluar" => "jeniskeluar", "a_pd" => "", "a_ptk" => ""),
                                            "jenjang_pendidikan"                        => array("id_jenj_didik" => "kdjenjang", "nm_jenj_didik" => "jenjang", "u_jenj_lemb" => "", "u_jenj_org" => ""),
                                            "penghasilan"                               => array("id_penghasilan" => "kdpenghasilan", "nm_penghasilan" => "penghasilan", "batas_bawah" => "", "batas_atas" => ""),
                                            "wilayah"                                   => array("id_wil" => "idwil", "nm_wil" => "wil", "asal_wil" => "", "kode_bps" => "", "kode_dagri" => "", "kode_keu" => "", "id_induk_wilayah" => "idindukwil", "id_level_wil" => "level", "id_negara" => ""),
                                            "negara"                                    => array("id_negara" => "kdnegara", "nm_negara" => "namanegara", "a_ln" => "isluarnegeri", "benua" => "benua"),
                                            "pekerjaan"                                 => array("id_pekerjaan" => "kdpekerjaanpddikti", "nm_pekerjaan" => "pekerjaan"),
                                            "jenis_pendaftaran"                         => array("id_jenis_daftar" => "kdjeniskelaspddikti", "nama_jenis_daftar" => "jeniskelas"),
                                            /* tabel Isian */
                                            /* primary key tidak digunakan untuk memasukkan data ke FEEDER PDDIKTI */
                                            /* informasi pemetaan dengan tabel institusi ada pada $this->peta["table"] */
                                            "mata_kuliah"                               => array("id_mk" => "", "id_sms" => "", "id_jenj_didik" => "", "kode_mk" => "", "nm_mk" => "", "jns_mk" => "", "kel_mk" => "", "sks_mk" => "", "sks_tm" => "", "sks_prak" => "", "sks_prak_lap" => "", "sks_sim" => "", "metode_pelaksanaan_kuliah" => "", "a_sap" => "", "a_silabus" => "", "a_bahan_ajar" => "", "acara_prak" => "", "a_diktat" => "", "tgl_mulai_efektif" => "", "tgl_akhir_efektif" => ""),
                                            "nilai_transfer"                            => array("id" => "guid", "id_reg_pd" => "guidmahasiswa", "id_mk" => "guidmatakuliah", "kode_mk_asal" => "kodematakuliahasal", "nm_mk_asal" => "matakuliahasal", "sks_asal" => "sksasal", "sks_diakui" => "sks", "nilai_huruf_asal" => "nilaihurufasal", "nilai_huruf_diakui" => "nilai", "nilai_angka_diakui" => "nilaiangka"),
                                            "kelas_kuliah"                              => array("id" => "guid", "id_sms" => "guid_prodi", "id_smt" => "kdtahunakademik", "id_mk" => "guid_matakuliah", "nm_kls" => "kelas", "sks_mk" => "sks"),
                                            "mata_kuliah_kurikulum"                     => array("id" => "guid", "id_kurikulum_sp" => "guid_kurikulum", "id_mk" => "guid_matakuliah", "smt" => "semester", "a_wajib" => "wajib"),
                                            "kuliah_mahasiswa"                          => array("id" => "guid", "id_smt" => "kdtahunakademik", "id_reg_pd" => "guidmahasiswa", "ips" => "ips", "sks_smt" => "sks", "ipk" => "ipk", "sks_total" => "skstotal", "id_stat_mhs" => "kdaktivitasmhs", "biaya_smt" => "biaya_smt"), /*untuk mahasiswa yang Non Aktif dan Keluar */
                                            "mahasiswa_pt keluar"                       => array("id" => "guidlulus", "id_reg_pd" => "guid", "id_jns_keluar" => "kdaktivitasmhs", "tgl_keluar" => "tglkeluar", "ket" => "keterangan", "sk_yudisium" => "nosk", "tgl_sk_yudisium" => "tglkeluar", "smt_yudisium" => "kdtahunakademik"), /*untuk update data mahasiswa yang Keluar pada tabel mahasiswa */
                                            "mahasiswa_pt lulus"                        => array("id" => "guidlulus", "id_reg_pd" => "guid", "id_jns_keluar" => "kdaktivitasmhs", "tgl_keluar" => "tglkeluar", "ket" => "keterangan", "jalur_skripsi" => "jalurskripsi", "judul_skripsi" => "judulkaryatulis", "sk_yudisium" => "nosk", "tgl_sk_yudisium" => "tglsk", "ipk" => "ipk", "no_seri_ijazah" => "noijazah", "smt_yudisium" => "kdtahunakademik"), /*untuk update data mahasiswa yang Lulus pada tabel mahasiswa */
                                            "mahasiswa_pt lulus_updatedata"             => array("id" => "null",      "id_reg_pd" => "guid", "id_jns_keluar" => "kdaktivitasmhs", "tgl_keluar" => "tglkeluar", "ket" => "keterangan", "jalur_skripsi" => "jalurskripsi", "judul_skripsi" => "judulkaryatulis", "sk_yudisium" => "nosk", "tgl_sk_yudisium" => "tglsk", "ipk" => "ipk", "no_seri_ijazah" => "noijazah", "smt_yudisium" => "kdtahunakademik"), /*untuk update data mahasiswa yang Lulus pada tabel mahasiswa */
                                            "kuliah_mahasiswa aktif"                    => array("id" => "guidinsert", "id_smt" => "kdtahunakademik", "id_reg_pd" => "guidmahasiswa", "ips" => "ips", "sks_smt" => "skss", "ipk" => "ipk", "sks_total" => "sks", "id_stat_mhs" => "kdaktivitasmhs", "biaya_smt" => "biaya_smt"), /*untuk mahasiswa yang Aktif */
                                            "kuliah_mahasiswa aktif_update"             => array("id" => "guidupdate", "id_smt" => "kdtahunakademik", "id_reg_pd" => "guidmahasiswa", "ips" => "ips", "sks_smt" => "skss", "ipk" => "ipk", "sks_total" => "sks", "id_stat_mhs" => "kdaktivitasmhs", "biaya_smt" => "biaya_smt"), /*untuk update data mahasiswa yang Aktif */
                                            "kuliah_mahasiswa lulus_keaktifan"          => array("id" => "guidlulus", "id_smt" => "kdtahunakademik", "id_reg_pd" => "guidmahasiswa", "id_stat_mhs" => "kdaktivitasmhs", "biaya_smt" => "biaya_smt"), /*untuk update data mahasiswa yang Lulus */
                                            "aktivitas_mahasiswa tugasakhir"            => array("id" => "guidjudulakt", "id_sms" => "guidprodi", "id_jns_akt_mhs" => "idjenisaktivitaspddikti", "id_smt" => "kdtahunakademik", "judul_akt_mhs" => "judulkaryatulis", "a_komunal" => "komunal", "sk_tugas" => "nosk", "tgl_sk_tugas" => "tanggalsk"),
                                            "anggota_aktivitas_mahasiswa tugasakhir"    => array("id" => "guidjudulang", "id_reg_pd" => "guidmahasiswa", "id_akt_mhs" => "guidjudulakt", "nm_pd" => "namalengkap", "nipd" => "nim", "jns_peran_mhs" => "jenisperan"),
                                            "bimbing_mahasiswa tugasakhir"              => array("id" => "guid", "id_akt_mhs" => "guidjudulakt", "id_sdm" => "guiddosen", "urutan_promotor" => "nourut", "id_katgiat" => "id_katgiat"),
                                            "uji_mahasiswa tugasakhir"                  => array("id" => "guid", "id_akt_mhs" => "guidjudulakt", "id_sdm" => "guiddosen", "urutan_uji" => "nourut", "id_katgiat" => "id_katgiat"),
                                            "aktivitas_mahasiswa prestasi"              => array("id" => "guidakt", "id_sms" => "guidprodi", "id_jns_akt_mhs" => "idjenisaktivitaspddikti", "id_smt" => "kdtahunakademik", "judul_akt_mhs" => "judul", "a_komunal" => "komunal", "sk_tugas" => "nosk", "tgl_sk_tugas" => "tanggalsk"),
                                            "anggota_aktivitas_mahasiswa prestasi"      => array("id" => "guidang", "id_reg_pd" => "guidmahasiswa", "id_akt_mhs" => "guidakt", "nm_pd" => "namalengkap", "nipd" => "nim", "jns_peran_mhs" => "jenisperan"),
                                            "bimbing_mahasiswa prestasi"                => array("id" => "guiddosenpembimbing", "id_akt_mhs" => "guidakt", "id_sdm" => "guiddosen", "urutan_promotor" => "nourut", "id_katgiat" => "idkatgiat"),
                                            "aktivitas_mahasiswa bimbing_aka"           => array("id" => "guidakt", "id_sms" => "guidprodi", "id_jns_akt_mhs" => "idjenisaktivitaspddikti", "id_smt" => "kdtahunakademik", "judul_akt_mhs" => "judul", "a_komunal" => "komunal", "sk_tugas" => "nosk", "tgl_sk_tugas" => "tanggalsk"),
                                            "anggota_aktivitas_mahasiswa bimbing_aka"   => array("id" => "guidang", "id_reg_pd" => "guidmahasiswa", "id_akt_mhs" => "guidakt", "nm_pd" => "namalengkap", "nipd" => "nim", "jns_peran_mhs" => "jenisperan"),
                                            "bimbing_mahasiswa bimbing_aka"             => array("id" => "guiddosenpembimbing", "id_akt_mhs" => "guidakt", "id_sdm" => "guiddosen", "urutan_promotor" => "nourut", "id_katgiat" => "idkatgiat"),
                                            "mahasiswa"                                 => array("id" => "guid",                    "nm_pd" => "namalengkap", "jk" => "jeniskelamin", "nik" => "nik", "tmpt_lahir" => "tempatlahir", "tgl_lahir" => "tanggallahir", "a_terima_kps" => "statuskps", "id_kk" => "kdkebutuhankhusus", "id_agama" => "kdagama", "jln" => "alamatlengkap", "rt" => "rt", "rw" => "rw", "nm_dsn" => "dusun", "ds_kel" => "kelurahan", "id_wil" => "kodekecpddikti", "kode_pos" => "kodepos", "no_tel_rmh" => "notelponortu", "no_hp" => "notelpon", "email" => "email", "nm_ayah" => "namaayah", "tgl_lahir_ayah" => "tgllahirayah", "id_jenjang_pendidikan_ayah" => "kdpendidikanayah", "id_pekerjaan_ayah" => "kdpekerjaan", "id_penghasilan_ayah" => "kdpenghasilanayah", "nm_ibu_kandung" => "namaibu", "tgl_lahir_ibu" => "tgllahiribu", "id_jenjang_pendidikan_ibu" => "kdpendidikanibu", "id_pekerjaan_ibu" => "kdpekerjaanibu", "id_penghasilan_ibu" => "kdpenghasilanibu", "kewarganegaraan" => "kdkewarganegaraan", "id_kebutuhan_khusus_ayah" => "idkebutuhankhususayah", "id_kebutuhan_khusus_ibu" => "idkebutuhankhususibu", "nik_ayah" => "nikayah", "nik_ibu" => "nikibu", "npwp" => "npwp", "nisn" => "nisn", "no_kps" => "nobpjs"),
                                            "mahasiswa updatedata"                      => array("id" => "null", "id_pd" => "guid", "nm_pd" => "namalengkap", "jk" => "jeniskelamin", "nik" => "nik", "a_terima_kps" => "statuskps", "id_kk" => "kdkebutuhankhusus", "id_agama" => "kdagama", "jln" => "alamatlengkap", "rt" => "rt", "rw" => "rw", "nm_dsn" => "dusun", "ds_kel" => "kelurahan", "id_wil" => "kodekecpddikti", "kode_pos" => "kodepos", "no_tel_rmh" => "notelponortu", "no_hp" => "notelpon", "email" => "email", "nm_ayah" => "namaayah", "tgl_lahir_ayah" => "tgllahirayah", "id_jenjang_pendidikan_ayah" => "kdpendidikanayah", "id_pekerjaan_ayah" => "kdpekerjaan", "id_penghasilan_ayah" => "kdpenghasilanayah", "tgl_lahir_ibu" => "tgllahiribu", "id_jenjang_pendidikan_ibu" => "kdpendidikanibu", "id_pekerjaan_ibu" => "kdpekerjaanibu", "id_penghasilan_ibu" => "kdpenghasilanibu", "kewarganegaraan" => "kdkewarganegaraan", "id_kebutuhan_khusus_ayah" => "idkebutuhankhususayah", "id_kebutuhan_khusus_ibu" => "idkebutuhankhususibu", "nik_ayah" => "nikayah", "nik_ibu" => "nikibu", "npwp" => "npwp", "nisn" => "nisn", "no_kps" => "nobpjs"),
                                            "mahasiswa_pt"                              => array("id" => "guid",                        "id_sms" => "guidprodi", "id_pd" => "guidmahasiswa", "id_sp" => "guidinstitusi", "id_jns_daftar" => "kdprogkul", "nipd" => "nim", "tgl_masuk_sp" => "tglawalkuliah", "a_pernah_paud" => "ispernahpaud", "a_pernah_tk" => "ispernahtk", "mulai_smt" => "mulaisemester", "sks_diakui" => "sksdiakui", "id_jalur_masuk" => "jalurmasuk", "id_prodi_asal" => "kodeprodiasal", "id_pt_asal" => "kdptasal", "no_peserta_ujian" => "nopesertaujian", "biaya_masuk_kuliah" => "biayamasukkuliah"), //"id_pt_asal" => "kdptasal", "id_prodi_asal" => "kodeprodiasal"),
                                            "mahasiswa_pt updatedata"                   => array("id" => "null", "id_reg_pd" => "guid", "id_sms" => "guidprodi", "id_pd" => "guidmahasiswa", "id_sp" => "guidinstitusi", "id_jns_daftar" => "kdprogkul", "nipd" => "nim", "tgl_masuk_sp" => "tglawalkuliah", "a_pernah_paud" => "ispernahpaud", "a_pernah_tk" => "ispernahtk", "mulai_smt" => "mulaisemester", "sks_diakui" => "sksdiakui", "id_jalur_masuk" => "jalurmasuk", "id_prodi_asal" => "kodeprodiasal", "id_pt_asal" => "kdptasal", "no_peserta_ujian" => "nopesertaujian", "biaya_masuk_kuliah" => "biayamasukkuliah"), //"id_pt_asal" => "kdptasal", "id_prodi_asal" => "kodeprodiasal"),
                                            "nilai krs"                                 => array("id" => "guidkrs", "id_kls" => "guidpenawaran", "id_reg_pd" => "guidmahasiswa", "nilai_angka" => "nilairiil", "nilai_huruf" => "nilai", "nilai_indeks" => "nilaiangka"),
                                            "nilai update"                              => array("id" => "guidnilai", "id_kls" => "guidpenawaran", "id_reg_pd" => "guidmahasiswa", "nilai_angka" => "nilairiil", "nilai_huruf" => "nilai", "nilai_indeks" => "nilaiangka"),
                                            "ajar_dosen"                                => array("id" => "tt.guid",                      "id_reg_ptk" => "guidpenugasan", "id_kls" => "guidkelas", "jml_tm_renc" => "rencana", "jml_tm_real" => "realisasi", "id_jns_eval" => "jeniseval", "sks_subst_tot" => "skssubsttot"),
                                            "ajar_dosen updatedata"                     => array("id" => "null",    "id_ajar" => "guid", "id_reg_ptk" => "guidpenugasan", "id_kls" => "guidkelas", "jml_tm_renc" => "rencana", "jml_tm_real" => "realisasi", "id_jns_eval" => "jeniseval", "sks_subst_tot" => "skssubsttot"),
                                            "dosen_pt"                                  => array("id" => "null", "id_reg_ptk" => "id_reg_ptk", "id_sdm" => "id_sdm", "id_thn_ajaran" => "tahun"),
                                            "satuan_pendidikan"                         => array("id" => "null", "npsn" => "kodept", "nm_lemb" => "namapt", "stat_sp" => "statuspt", "jln" => "kotapt", "id_sp"=>"guid"),
                                            "sms"                                       => array("id" => "null", "id_sms" => "guidprodi", "id_sp" => "guidpt", "kode_prodi" => "kodeprodi")
                                        );
    }
    
    /**
     * pemetaan tabel PDDIKTI dan Institusi
     * <br/> indeks:
     * <br/> - table
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array("nama" => nama_tabel_institusi1, "filter" => filter_tabel_institusi1),
     * <br/> - atau -
     * <br/> nama_tabel_pddikti2 => array("nama" => "", "filter" => "", "data" => array(indeks2.1=>data2.1, indeks2.2=>data2.2, ...)),
     * <br/> ...
     * <br/> dimana:
     * <br/> - nama-- : nama tabel institusi
     * <br/> - filter : filter data untuk tabel institusi
     * <br/> - data-- : OPSIONAL - membuat data sendiri, tidak mengambil dari tabel. jika diisi, maka "nama" dan "filter" akan diabaikan. berupa array()
     */
    private function peta_tabel()
    {
        $this->peta["table"] = array (  /* tabel Referensi */
                                        "agama"               => array("nama" => "pt_agama", "filter" => ""),
                                        "semester"            => array("nama" => "pddikti_v_tahunakademik", "filter" => ""),
                                        "jurusan"             => array("nama" => "ak_programstudi", "filter" => ""),
                                        "jabfung"             => array("nama" => "pt_jafa", "filter" => ""),
                                        "ikatan_kerja_dosen"  => array("nama" => "ak_ikatankerjadosen", "filter" => ""),
                                        "jenis_keluar"        => array("nama" => "ak_jeniskeluar", "filter" => ""),
                                        "jenjang_pendidikan"  => array("nama" => "pt_jenjangpendidikan", "filter" => ""),
                                        "penghasilan"         => array("nama" => "ak_penghasilan", "filter" => ""),
                                        "wilayah"             => array("nama" => "pt_v_propinsikabupatenkecamatan", "filter" => ""),
                                        "negara"              => array("nama" => "pt_negara", "filter" => ""),
                                        "pekerjaan"           => array("nama" => "pt_pekerjaan", "filter" => ""),
                                        "jenis_pendaftaran"   => array("nama" => "ak_jeniskelas", "filter" => ""),
                                        /* tabel Referensi yang tidak ada di WebService */
                                        "kel_mk"              => array("nama" => "", "filter" => "", "data" => array("A" => "MPK", "B" => "MKK", "C" => "MKB", "D" => "MPB", "E" => "MBB", "F" => "MKU/MKDU", "G" => "MKDK", "H" => "MKK")),
                                        "jns_mk"              => array("nama" => "", "filter" => "", "data" => array("A" => "Wajib", "B" => "Pilihan", "C" => "Wajib Peminatan", "D" => "Pilihan Peminatan", "S" => "Skripsi/Tugas Akhir")),
                                        /* tabel Isian tidak diisikan di sini */
                                        "satuan_pendidikan"   => array("nama" => "pt_perguruantinggi", "filter" => ""),
                                    );
    }
    
    /**
     * pemetaan untuk memasukkan data dari Institusi ke PDDIKTI<br/>pindah ke peta_injek_usang() jika sudah usang dan perbaiki data dengan peta_injek_perbaiki_usang()
     * <br/> indeks:
     * <br/> - inject
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array("table" => nama_tabel_institusi1, "filter" => filter_tabel_institusi1, "type" => type1, "ignoreinject" => ignoreinject1, "tahunakademik" => tahunakademik_tabel_institusi1, "tandatahunakademik" => tandatahunakademik_tabel_institusi1, "istahunakademikkrs" => istahunakademikkrs_tabel_institusi1, "fieldupdate" => array(fieldupdate_tabel_pddikti1, ...), "fieldwhere" => array(fieldwhere_tabel_pddikti1, ...), "jenisfilter" => jenisfilter1),
     * <br/> nama_tabel_pddikti2 => array("table" => nama_tabel_institusi2, "filter" => filter_tabel_institusi2, "type" => type2, "ignoreinject" => ignoreinject2, "tahunakademik" => tahunakademik_tabel_institusi2, "tandatahunakademik" => tandatahunakademik_tabel_institusi2, "istahunakademikkrs" => istahunakademikkrs_tabel_institusi2, "fieldupdate" => array(fieldupdate_tabel_pddikti2, ...), "fieldwhere" => array(fieldwhere_tabel_pddikti2, ...), "jenisfilter" => jenisfilter2),
     * <br/> ...
     * <br/> dimana:
     * <br/> table-------------- : nama tabel institusi, untuk guid lebih dari 1 tabel: select group_concat(kdtimteaching separator ',') as kdtimteaching, ... from (select concat('0_', tt.kdtimteaching) as kdtimteaching, ...) union all (select concat('1_', tt.kdtimteaching) as kdtimteaching, ...) )
     * <br/> filter------------- : filter data untuk tabel institusi
     * <br/> type--------------- : insert (memasukkan data ke PDDIKTI) atau update (memperbarui data yang ada di PDDIKTI)
     * <br/> ignoreinject------- : true (diabaikan ketika injeksi massal) atau false (dijalankan ketika injeksi massal)
     * <br/> tahunakademik------ : OPSIONAL - tahun akademik dari data yang akan diambil
     * <br/> tandatahunakademik- : OPSIONAL - =, &lt;, &gt;, &lt;=, atau &gt;=
     * <br/> istahunakademikkrs- : OPSIONAL - tahunakademikkrs adalah tahun akademik aktif, di mana pada saat itu mahasiswa melakukan KRS. true (tahun akademik tidak berubah) atau false (tahun akademik menjadi tahun akademik sebelumnya)
     * <br/> fieldupdate-------- : OPSIONAL - digunakan ketika type="update", kolom-kolom di PDDIKTI yang akan diubah. berupa array()
     * <br/> fieldwhere--------- : OPSIONAL - digunakan ketika type="update", filter data di PDDIKTI yang akan diubah. berupa array()
     * <br/> jenisfilter-------- : OPSIONAL - internalfilter (mengganti [internalfilter] dengan "filter") atau string kosong
     * <br/> catatan:
     * <br/> - format nama tabel pddikti: [nama_tabel_pddikti] atau [nama_tabel_pddikti]<spasi>[keterangan]
     * <br/> - Ingat! indeks "tahunakademik" pada "inject" harus bisa di-query-kan di this->peta["table"] pada this->peta["guid"], perhatikan alias tabel
     * <br/> - "id" pada this->peta["field"] otomatis dibuat isnull()
     */
    private function peta_injek()
    {
        $this->peta["inject"] = array 
                                (  // tipe insert: table, filter, tahunakademik, tandatahunakademik, istahunakademikkrs, type=insert
                                    "mata_kuliah_kurikulum"                     => array( "table"               => "pddikti_v_matakuliahkurikulum mk",
                                                                                          "filter"              => "not isnull(guid_kurikulum) and not isnull(guid_matakuliah)",
                                                                                          "type"                => "insert",
                                                                                          "infotambahanerror"   => "kdmatakuliah",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "kelas_kuliah"                              => array( "table"               => "pddikti_v_penawaranmatakuliah pm", 
                                                                                          "filter"              => "not isnull(guid_matakuliah)",
                                                                                          "tahunakademik"       => "pm.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "infotambahanerror"   => "matakuliah, prodi",
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "mahasiswa"                                 => array( "table"               => "pddikti_v_mahasiswa p",
                                                                                          "filter"              => "isnull(guid) and p.isignore=0",
                                                                                          "tahunakademik"       => "p.kdtamasuk",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "infotambahanerror"   => "nim", 
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "mahasiswa updatedata"                      => array( "table"               => "pddikti_v_mahasiswa p",
                                                                                          "filter"              => "p.isignore=0",
                                                                                          "tahunakademik"       => "p.kdtamasuk",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "type"                => "update",
                                                                                          "fieldupdate"         => array("jk","nik","id_kk","id_agama","jln","rt","rw","nm_dsn","ds_kel","id_wil","kode_pos","no_tel_rmh","no_hp","email","nm_ayah","tgl_lahir_ayah", "id_jenjang_pendidikan_ayah","id_pekerjaan_ayah","id_penghasilan_ayah","tgl_lahir_ibu","id_jenjang_pendidikan_ibu","id_pekerjaan_ibu","id_penghasilan_ibu","kewarganegaraan","id_kebutuhan_khusus_ayah", "id_kebutuhan_khusus_ibu", "nik_ayah", "nik_ibu", "npwp", "nisn", "a_terima_kps", "no_kps"),
                                                                                          "infotambahanerror"   => "namalengkap",
                                                                                          "fieldwhere"          => array("id_pd"),
                                                                                          "ignoreinject"        => true,
                                                                                          "ideksekusi"          => "?a=5&t=tahunakademik"
                                                                                       ),
                                    "mahasiswa_pt"                              => array( "table"               => "pddikti_v_mahasiswa_pt m",
                                                                                          "filter"              => "isnull(guid) and not isnull(guidmahasiswa) and m.isignore=0",
                                                                                          "tahunakademik"       => "m.kdtamasuk",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "mahasiswa_pt updatedata"                  => array(  "table"               => "pddikti_v_mahasiswa_pt m",
                                                                                          "filter"              => "m.isignore=0",
                                                                                          "tahunakademik"       => "m.kdtamasuk",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "type"                => "update",
                                                                                          "fieldupdate"         => array("mulai_smt", "sks_diakui", "id_jns_daftar", "biaya_masuk_kuliah"),//, "id_pt_asal", "id_prodi_asal"
                                                                                          "fieldwhere"          => array("id_reg_pd"),
                                                                                          "ignoreinject"        => true,
                                                                                          "ideksekusi"          => "?a=6&t=tahunakademik"
                                                                                        ),
                                    "nilai_transfer"                            => array( "table"               => "pddikti_v_nilai_transfer nt",
                                                                                          "filter"              => "isnull(guid) and nt.isignoremahasiswa=0 and nt.isignorematakuliah=0 and not isnull(guidmahasiswa) and not isnull(guidmatakuliah)",
                                                                                          "tahunakademik"       => "kdtamasuk",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "infotambahanerror"   => "kodematakuliah, nim",
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "nilai krs"                                 => array( "table"               => "pddikti_v_krs k", //krs
                                                                                          "filter"              => "k.isignoremahasiswa=0 and k.isignorepenawaran=0 and not isnull(guidmahasiswa) and not isnull(guidpenawaran)",
                                                                                          "tahunakademik"       => "k.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "infotambahanerror"   => "nim, kodematakuliah",
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    // tipe update: table, filter, tahunakademik, tandatahunakademik, istahunakademikkrs, type=update, fieldupdate
                                    "nilai update"                              => array( "table"               => "select k.kdkrsnilai AS kdkrsnilai, m.nim, m.isignore AS isignoremahasiswa, p.isignore AS isignorepenawaran, k.kdtahunakademik AS kdtahunakademik, k.guidnilai AS guid, k.guidkrs AS guidkrs, p.guid AS guidpenawaran, m.guid AS guidmahasiswa, 9 AS asaldata, NULL AS nilairiil, k.nilai AS nilai, k.nilaiangka AS nilaiangka, nim, kodematakuliah from ak_krsnilai_nonremidial k join ak_mahasiswa m ON ((m.kdmahasiswa = k.kdmahasiswa)) join ak_penawaranmatakuliah p ON ((p.kdpenawaran = k.kdpenawaran)) where [internalfilter]",
                                                                                          "filter"              => "(k.nilai <= 'E' or k.nilai = 'T') and not isnull(guidkrs) and m.isignore=0 and p.isignore=0 and k.isignore=0",
                                                                                          "jenisfilter"         => "internalfilter",
                                                                                          "tahunakademik"       => "k.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "infotambahanerror"   => "nim",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "update",
                                                                                          "fieldupdate"         => array("nilai_angka", "nilai_huruf", "nilai_indeks"),
                                                                                          "fieldwhere"          => array("id_kls","id_reg_pd"),
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "kuliah_mahasiswa"                          => array( "table"               => "pddikti_v_kuliah_mahasiswa c",
                                                                                          "filter"              => "isnull(c.guid) and not isnull(c.guidmahasiswa) and c.isignore=0 and c.isignoremahasiswa=0 and c.kdaktivitasmhs not in('L', 'A')",  //pantau, keluar harus non aktif terlebih dahulu
                                                                                          "tahunakademik"       => "c.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "infotambahanerror"   => "nim",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "mahasiswa_pt keluar"                       => array( "table"               => "pddikti_v_keluar_mahasiswa m",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "type"                => "update",
                                                                                          "fieldupdate"         => array("id_jns_keluar","tgl_keluar","ket","sk_yudisium","tgl_sk_yudisium","smt_yudisium"),
                                                                                          "fieldwhere"          => array("id_reg_pd"),
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "mahasiswa_pt lulus"                        => array( "table"               => "pddikti_v_lulus_mahasiswa m",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "m.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "update",
                                                                                          "fieldupdate"         => array("id_jns_keluar","tgl_keluar","ket","sk_yudisium","tgl_sk_yudisium","ipk","no_seri_ijazah", "smt_yudisium"),
                                                                                          "infotambahanerror"   => "nim",
                                                                                          "fieldwhere"          => array("id_reg_pd"),
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                       ),
                                    "mahasiswa_pt lulus_updatedata"             => array( "table"               => "pddikti_v_lulus_mahasiswa m",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "m.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "update",
                                                                                          "fieldupdate"         => array("id_jns_keluar","tgl_keluar","ket","sk_yudisium","tgl_sk_yudisium","ipk","no_seri_ijazah", "smt_yudisium"),
                                                                                          "infotambahanerror"   => "kdmahasiswa,nim",
                                                                                          "fieldwhere"          => array("id_reg_pd"),
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                       ),
                                    "kuliah_mahasiswa aktif"                    => array( "table"               => "select kdrekapipk, nim, ri.kdtahunakademik, m.guid as guidmahasiswa, ips, skss, ipk, sks, kdaktivitasmhs, guidinsert, guidupdate, biaya_smt from (select distinct kdmahasiswa, kdtahunakademik, 'A' as kdaktivitasmhs, 0 as biaya_smt from ak_krsnilai) krs join ak_rekap_ipk ri on (ri.kdmahasiswa=krs.kdmahasiswa and ri.kdtahunakademik=krs.kdtahunakademik) join ak_mahasiswa m on m.kdmahasiswa=ri.kdmahasiswa where [internalfilter]",
        //                                                                          "filter"              => "isnull(guidinsert) and m.isignore=0 and (isnull(kdyudisium) or kdyudisium=0)",
                                                                                          "filter"              => "isnull(guidinsert) and m.isignore=0", //--> mahasiswa lulus itu harus aktif terlebih dahulu, kemudian baru statusnya berubah menjadi lulus.. meskipun data pernah masuk tidak masalah, nanti akan tertolak, daripada data tidak masuk
                                                                                          "jenisfilter"         => "internalfilter",
                                                                                          "tahunakademik"       => "ri.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => true,
                                                                                          "infotambahanerror"   => "nim",
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "kuliah_mahasiswa aktif_update"             => array( "table"               => "select kdrekapipk, nim, namalengkap, ri.kdtahunakademik, m.guid as guidmahasiswa, ips, skss, ipk, sks, kdaktivitasmhs, guidinsert, guidupdate from (select distinct kdmahasiswa, kdtahunakademik, 'A' as kdaktivitasmhs from ak_krsnilai) krs join ak_rekap_ipk ri on (ri.kdmahasiswa=krs.kdmahasiswa and ri.kdtahunakademik=krs.kdtahunakademik) join ak_mahasiswa m on m.kdmahasiswa=ri.kdmahasiswa join pt_person p on p.kdperson=m.kdperson where [internalfilter]",
                                                                                          "filter"              => "m.isignore=0",
                                                                                          "jenisfilter"         => "internalfilter",
                                                                                          "tahunakademik"       => "ri.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "update",
                                                                                          "fieldupdate"         => array("ips", "sks_smt", "ipk", "sks_total"),
                                                                                          "fieldwhere"          => array("id_smt","id_reg_pd","id_stat_mhs"),
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "aktivitas_mahasiswa tugasakhir"            => array( "table"               => "pddikti_v_tugasakhirakt t",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "infotambahanerror"   => "nim",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "anggota_aktivitas_mahasiswa tugasakhir"    => array( "table"               => "pddikti_v_tugasakhirang t",
                                                                                          "filter"              => "isignore=0 and not isnull(guidjudulakt)",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "bimbing_mahasiswa tugasakhir"              => array( "table"               => "pddikti_v_dosen_pembimbing p",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "infotambahanerror"   => "nim,namadosen",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "uji_mahasiswa tugasakhir"                  => array( "table"               => "pddikti_v_dosen_penguji p",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "infotambahanerror"   => "nim,namadosen",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=4&t=tahunakademik"
                                                                                        ),
                                    "aktivitas_mahasiswa prestasi"              => array( "table"               => "pddikti_v_prestasiakt t",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "infotambahanerror"   => "nim",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=15&t=tahunakademik"
                                                                                        ),
                                    "anggota_aktivitas_mahasiswa prestasi"      => array( "table"               => "pddikti_v_prestasiang t",
                                                                                          "filter"              => "isignore=0 and not isnull(guidakt)",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=15&t=tahunakademik"
                                                                                        ),
                                    "bimbing_mahasiswa prestasi"                => array( "table"               => "pddikti_v_dosen_pembimbing_prestasi p",
                                                                                          "filter"              => "isignore=0",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "infotambahanerror"   => "nim,namadosen",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=15&t=tahunakademik"
                                                                                        ),
                                    "aktivitas_mahasiswa bimbing_aka"           => array( "table"               => "select grouping, nosk, if(group_concat(ifnull(guidakt, '') separator '')='', NULL, group_concat(ifnull(guidakt, '') separator '')) as guidakt, `ps`.`guid` AS `guidprodi`, 7 AS `idjenisaktivitaspddikti`, `mpa`.`kdtahunakademik` AS `kdtahunakademik`, concat('Bimbingan Akademik ', namalengkap) AS `judul`, 0 AS `komunal`, 0 AS `isignore`, `mpa`.`nosk` AS `nosk`, `mpa`.`tanggalsk` AS `tanggalsk` from ak_mahasiswa_pa mpa join ak_mahasiswa m on m.kdmahasiswa=mpa.kdmahasiswa JOIN `ak_programstudi` `ps` ON ((`ps`.`kdunitkerja` = `m`.`kdunitkerja`)) join pt_person p on p.kdperson=mpa.kdpersonpa where [internalfilter] group by nosk, ps.guid, mpa.kdpersonpa having if(group_concat(ifnull(guidakt, '') separator '')='', NULL, group_concat(ifnull(guidakt, '') separator '')) is null",
                                                                                          "filter"              => "",
                                                                                          "jenisfilter"         => "internalfilter",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=16&t=tahunakademik"
                                                                                        ),
                                    "anggota_aktivitas_mahasiswa bimbing_aka"   => array( "table"               => "pddikti_v_bimbingakademikang t",
                                                                                          "filter"              => "isignore=0 and not isnull(guidakt)",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=16&t=tahunakademik"
                                                                                        ),
                                    "bimbing_mahasiswa bimbing_aka"             => array( "table"               => "SELECT grouping, `pm`.`guidakt` AS `guidakt`, `p`.`guiddosen` AS `guiddosen`, `p`.`namalengkap` AS `namadosen`, 1 AS `nourut`, `m`.`nim` AS `nim`, if(group_concat(ifnull(guiddosenpembimbing, '') separator '')='', NULL, group_concat(ifnull(guiddosenpembimbing, '') separator '')) AS `guiddosenpembimbing`, `pm`.`kdtahunakademik` AS `kdtahunakademik`, 110600 AS `idkatgiat`, 0 AS `isignore` FROM ((`ak_mahasiswa_pa` `pm` JOIN `ak_mahasiswa` `m` ON ((`m`.`kdmahasiswa` = `pm`.`kdmahasiswa`))) JOIN `pt_person` `p` ON ((`p`.`kdperson` = `pm`.`kdpersonpa`))) where [internalfilter] group by grouping having if(group_concat(ifnull(guiddosenpembimbing, '') separator '')='', NULL, group_concat(ifnull(guiddosenpembimbing, '') separator '')) is null",
                                                                                          "filter"              => "",
                                                                                          "jenisfilter"         => "internalfilter",
                                                                                          "tahunakademik"       => "kdtahunakademik",
                                                                                          "infotambahanerror"   => "nim,namadosen",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=16&t=tahunakademik"
                                                                                        ),
                                    "ajar_dosen"                                => array( "table"               => "select group_concat(kdtimteaching separator ',') as kdtimteaching, guid, guidkelas, id_reg_ptk as guidpenugasan, if(ceil(sum(rencana))>99,99,ceil(sum(rencana))) as rencana, if(ceil(sum(realisasi))>99,99,ceil(sum(realisasi))) as realisasi, jeniseval, skssubsttot, datamengajar
                                                                                                                   from 
                                                                                                                   (
                                                                                                                       (select concat('0_', tt.kdtimteaching) as kdtimteaching, tt.guid, pm.guid as guidkelas, id_reg_ptk, sum(durasislot) as rencana, sum(if(isrealisasi in (1,4),1,0)*durasislot) as realisasi, 1 as jeniseval, 0 as skssubsttot, tt.kdpenawaran, tt.kdpersonepsbed, concat(namalengkap, '-', matakuliah, '-', kelas, '-', namaprodipendek) as datamengajar from ak_jadwalkuliah jk join ak_timteaching tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_penawaranmatakuliah pm on pm.kdpenawaran=tt.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed join ak_penugasan pn on (pn.id_sdm=p.guiddosen and pn.tahun=floor(jk.kdtahunakademik/10)) join ak_matakuliah m on m.kdmatakuliah=pm.kdmatakuliah join ak_kurikulum kr on kr.kdkurikulum=m.kdkurikulum join ak_programstudi ps on ps.kdunitkerja=kr.kdunitkerja where [internalfilter] group by tt.kdpenawaran, tt.kdpersonepsbed) 
                                                                                                                    union all 
                                                                                                                       (select concat('1_', tt.kdtimteaching) as kdtimteaching, tt.guid, pm.guid as guidkelas, id_reg_ptk, sum(durasislot) as rencana, sum(if(isrealisasi in (1,4),1,0)*durasislot) as realisasi, 1 as jeniseval, 0 as skssubsttot, kl.kdpenawaran, tt.kdpersonepsbed, concat(namalengkap, '-', matakuliah, '-', kelas, '-', namaprodipendek) as datamengajar from ak_jadwalkuliah_lab jk join ak_timteaching_lab tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_kelompok kl on kl.kdkelompok=tt.kdkelompok join ak_penawaranmatakuliah pm on pm.kdpenawaran=kl.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed join ak_penugasan pn on (pn.id_sdm=p.guiddosen and pn.tahun=floor(jk.kdtahunakademik/10)) join ak_matakuliah m on m.kdmatakuliah=pm.kdmatakuliah join ak_kurikulum kr on kr.kdkurikulum=m.kdkurikulum join ak_programstudi ps on ps.kdunitkerja=kr.kdunitkerja where [internalfilter] group by kl.kdpenawaran, tt.kdpersonepsbed)
                                                                                                                   ) ajar_dosen 
                                                                                                                   group by kdpenawaran, kdpersonepsbed",
                                                                                          "filter"              => "tt.isignore=0 and pm.isignore=0",
                                                                                          "jenisfilter"         => "internalfilter",
                                                                                          "tahunakademik"       => "tt.kdtahunakademik,pm.kdtahunakademik,jk.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "insert",
                                                                                          "ignoreinject"        => false,
                                                                                          "ideksekusi"          => "?a=8&t=tahunakademik"
                                                                                        ),
                                    "ajar_dosen updatedata"                     => array( "table"               => "select group_concat(kdtimteaching separator ',') as kdtimteaching, guid, guidkelas, id_reg_ptk as guidpenugasan, if(ceil(sum(rencana))>99,99,ceil(sum(rencana))) as rencana, if(ceil(sum(realisasi))>99,99,ceil(sum(realisasi))) as realisasi, jeniseval, skssubsttot, datamengajar
                                                                                                                   from 
                                                                                                                   (
                                                                                                                       (select concat('0_', tt.kdtimteaching) as kdtimteaching, tt.guid, pm.guid as guidkelas, id_reg_ptk, sum(durasislot) as rencana, sum(if(isrealisasi in (1,4),1,0)*durasislot) as realisasi, 1 as jeniseval, 0 as skssubsttot, tt.kdpenawaran, tt.kdpersonepsbed, concat(namalengkap, '-', matakuliah, '-', kelas, '-', namaprodipendek) as datamengajar from ak_jadwalkuliah jk join ak_timteaching tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_penawaranmatakuliah pm on pm.kdpenawaran=tt.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed join ak_penugasan pn on (pn.id_sdm=p.guiddosen and pn.tahun=floor(jk.kdtahunakademik/10)) join ak_matakuliah m on m.kdmatakuliah=pm.kdmatakuliah join ak_kurikulum kr on kr.kdkurikulum=m.kdkurikulum join ak_programstudi ps on ps.kdunitkerja=kr.kdunitkerja where [internalfilter] group by tt.kdpenawaran, tt.kdpersonepsbed) 
                                                                                                                    union all 
                                                                                                                       (select concat('1_', tt.kdtimteaching) as kdtimteaching, tt.guid, pm.guid as guidkelas, id_reg_ptk, sum(durasislot) as rencana, sum(if(isrealisasi in (1,4),1,0)*durasislot) as realisasi, 1 as jeniseval, 0 as skssubsttot, kl.kdpenawaran, tt.kdpersonepsbed, concat(namalengkap, '-', matakuliah, '-', kelas, '-', namaprodipendek) as datamengajar from ak_jadwalkuliah_lab jk join ak_timteaching_lab tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_kelompok kl on kl.kdkelompok=tt.kdkelompok join ak_penawaranmatakuliah pm on pm.kdpenawaran=kl.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed join ak_penugasan pn on (pn.id_sdm=p.guiddosen and pn.tahun=floor(jk.kdtahunakademik/10)) join ak_matakuliah m on m.kdmatakuliah=pm.kdmatakuliah join ak_kurikulum kr on kr.kdkurikulum=m.kdkurikulum join ak_programstudi ps on ps.kdunitkerja=kr.kdunitkerja where [internalfilter] group by kl.kdpenawaran, tt.kdpersonepsbed)
                                                                                                                   ) ajar_dosen 
                                                                                                                   group by kdpenawaran, kdpersonepsbed",
                                                                                          "filter"              => "tt.isignore=0 and pm.isignore=0",
                                                                                          "jenisfilter"         => "internalfilter",
                                                                                          "tahunakademik"       => "tt.kdtahunakademik,pm.kdtahunakademik,jk.kdtahunakademik",
                                                                                          "tandatahunakademik"  => "=",
                                                                                          "istahunakademikkrs"  => false,
                                                                                          "type"                => "update",
                                                                                          "ignoreinject"        => true,
                                                                                          "fieldupdate"         => array("jml_tm_renc", "jml_tm_real"),
                                                                                          "fieldwhere"          => array("id_ajar"),
                                                                                          "ideksekusi"          => "?a=8&t=tahunakademik"
                                                                                        ),
                                );
    }
    
    /**
     * pemetaan untuk memasukkan data dari Institusi ke PDDIKTI - sudah usang, dahulu digunakan, tetapi untuk versi berikutnya tidak digunakan
     * <br/> indeks:
     * <br/> - inject
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array("table" => nama_tabel_institusi1, "filter" => filter_tabel_institusi1, "type" => type1, "ignoreinject" => ignoreinject1, "tahunakademik" => tahunakademik_tabel_institusi1, "tandatahunakademik" => tandatahunakademik_tabel_institusi1, "istahunakademikkrs" => istahunakademikkrs_tabel_institusi1, "fieldupdate" => array(fieldupdate_tabel_pddikti1, ...), "fieldwhere" => array(fieldwhere_tabel_pddikti1, ...), "jenisfilter" => jenisfilter1),
     * <br/> nama_tabel_pddikti2 => array("table" => nama_tabel_institusi2, "filter" => filter_tabel_institusi2, "type" => type2, "ignoreinject" => ignoreinject2, "tahunakademik" => tahunakademik_tabel_institusi2, "tandatahunakademik" => tandatahunakademik_tabel_institusi2, "istahunakademikkrs" => istahunakademikkrs_tabel_institusi2, "fieldupdate" => array(fieldupdate_tabel_pddikti2, ...), "fieldwhere" => array(fieldwhere_tabel_pddikti2, ...), "jenisfilter" => jenisfilter2),
     * <br/> ...
     * <br/> dimana:
     * <br/> table-------------- : nama tabel institusi
     * <br/> filter------------- : filter data untuk tabel institusi
     * <br/> type--------------- : insert (memasukkan data ke PDDIKTI) atau update (memperbarui data yang ada di PDDIKTI)
     * <br/> ignoreinject------- : true (diabaikan ketika injeksi massal) atau false (dijalankan ketika injeksi massal)
     * <br/> tahunakademik------ : OPSIONAL - tahun akademik dari data yang akan diambil
     * <br/> tandatahunakademik- : OPSIONAL - =, &lt;, &gt;, &lt;=, atau &gt;=
     * <br/> istahunakademikkrs- : OPSIONAL - tahunakademikkrs adalah tahun akademik aktif, di mana pada saat itu mahasiswa melakukan KRS. true (tahun akademik tidak berubah) atau false (tahun akademik menjadi tahun akademik sebelumnya)
     * <br/> fieldupdate-------- : OPSIONAL - digunakan ketika type="update", kolom-kolom di PDDIKTI yang akan diubah. berupa array()
     * <br/> fieldwhere--------- : OPSIONAL - digunakan ketika type="update", filter data di PDDIKTI yang akan diubah. berupa array()
     * <br/> jenisfilter-------- : OPSIONAL - internalfilter (mengganti [internalfilter] dengan "filter") atau string kosong
     * <br/> catatan:
     * <br/> - format nama tabel pddikti: [nama_tabel_pddikti] atau [nama_tabel_pddikti]<spasi>[keterangan]
     * <br/> - Ingat! indeks "tahunakademik" pada "inject" harus bisa di-query-kan di this->peta["table"] pada this->peta["guid"], perhatikan alias tabel
     * <br/> - "id" pada this->peta["field"] otomatis dibuat isnull()
     */
    private function peta_injek_usang()
    {
        $this->peta["inject_usang"] = array (  
                                                //usang pada versi 2.0
                                                "kuliah_mahasiswa lulus_keaktifan"  => array(   "table"               => "pddikti_v_lulus_keaktifan y",
                                                                                                "filter"              => "y.isignore=0",
                                                                                                "tahunakademik"       => "y.kdtahunakademik",
                                                                                                "tandatahunakademik"  => "=",
                                                                                                "istahunakademikkrs"  => false,
                                                                                                "type"                => "update",
                                                                                                "fieldupdate"         => array("id_stat_mhs"),
                                                                                                "fieldwhere"          => array("id_smt","id_reg_pd"),
                                                                                                "ignoreinject"        => false
                                                                                            ),
                                            );
    }
    
    /**
     * memperbaiki data pemetaan untuk memasukkan data dari Institusi ke PDDIKTI karena ada yang sudah usang seperti pada peta_injek_usang()
     * <br/> indeks:
     * <br/> - inject
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array("table" => nama_tabel_institusi1, "filter" => filter_tabel_institusi1, "type" => type1, "ignoreinject" => ignoreinject1, "tahunakademik" => tahunakademik_tabel_institusi1, "tandatahunakademik" => tandatahunakademik_tabel_institusi1, "istahunakademikkrs" => istahunakademikkrs_tabel_institusi1, "fieldupdate" => array(fieldupdate_tabel_pddikti1, ...), "fieldwhere" => array(fieldwhere_tabel_pddikti1, ...), "jenisfilter" => jenisfilter1),
     * <br/> nama_tabel_pddikti2 => array("table" => nama_tabel_institusi2, "filter" => filter_tabel_institusi2, "type" => type2, "ignoreinject" => ignoreinject2, "tahunakademik" => tahunakademik_tabel_institusi2, "tandatahunakademik" => tandatahunakademik_tabel_institusi2, "istahunakademikkrs" => istahunakademikkrs_tabel_institusi2, "fieldupdate" => array(fieldupdate_tabel_pddikti2, ...), "fieldwhere" => array(fieldwhere_tabel_pddikti2, ...), "jenisfilter" => jenisfilter2),
     * <br/> ...
     * <br/> dimana:
     * <br/> table-------------- : nama tabel institusi
     * <br/> filter------------- : filter data untuk tabel institusi
     * <br/> type--------------- : insert (memasukkan data ke PDDIKTI) atau update (memperbarui data yang ada di PDDIKTI)
     * <br/> ignoreinject------- : true (diabaikan ketika injeksi massal) atau false (dijalankan ketika injeksi massal)
     * <br/> tahunakademik------ : OPSIONAL - tahun akademik dari data yang akan diambil
     * <br/> tandatahunakademik- : OPSIONAL - =, &lt;, &gt;, &lt;=, atau &gt;=
     * <br/> istahunakademikkrs- : OPSIONAL - tahunakademikkrs adalah tahun akademik aktif, di mana pada saat itu mahasiswa melakukan KRS. true (tahun akademik tidak berubah) atau false (tahun akademik menjadi tahun akademik sebelumnya)
     * <br/> fieldupdate-------- : OPSIONAL - digunakan ketika type="update", kolom-kolom di PDDIKTI yang akan diubah. berupa array()
     * <br/> fieldwhere--------- : OPSIONAL - digunakan ketika type="update", filter data di PDDIKTI yang akan diubah. berupa array()
     * <br/> jenisfilter-------- : OPSIONAL - internalfilter (mengganti [internalfilter] dengan "filter") atau string kosong
     * <br/> catatan:
     * <br/> - format nama tabel pddikti: [nama_tabel_pddikti] atau [nama_tabel_pddikti]<spasi>[keterangan]
     * <br/> - Ingat! indeks "tahunakademik" pada "inject" harus bisa di-query-kan di this->peta["table"] pada this->peta["guid"], perhatikan alias tabel
     * <br/> - "id" pada this->peta["field"] otomatis dibuat isnull()
     */
    private function peta_injek_perbaiki_usang()
    {
        $this->peta["inject_perbaiki_usang"] = array    (  
                                                            //memperbaiki peta_injek_usang "kuliah_mahasiswa lulus_keaktifan"
                                                            "kuliah_mahasiswa lulus_keaktifan"  => array(   "table"               => "pddikti_v_lulus_keaktifan_perbaiki y",
                                                                                                            "filter"              => "y.isignore=0",
                                                                                                            "tahunakademik"       => "y.kdtahunakademik",
                                                                                                            "tandatahunakademik"  => "=",
                                                                                                            "istahunakademikkrs"  => false,
                                                                                                            "type"                => "update",
                                                                                                            "fieldupdate"         => array("id_stat_mhs"),
                                                                                                            "fieldwhere"          => array("id_smt","id_reg_pd"),
                                                                                                            "ignoreinject"        => false
                                                                                                        ),
                                                        );
    }
    
    /**
     * pemetaan untuk mengambil data dari PDDIKTI ke Institusi
     * <br/> indeks:
     * <br/> - extract
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array("table" => nama_tabel_institusi1, "uniquefield" => uniquefield_tabel_pddikti1, "istahunakademikkrs" => istahunakademikkrs1, "filtertahunakademik" => filtertahunakademik_tabel_pddikti1),
     * <br/> nama_tabel_pddikti2 => array("table" => nama_tabel_institusi2, "uniquefield" => uniquefield_tabel_pddikti2, "istahunakademikkrs" => istahunakademikkrs2, "filtertahunakademik" => filtertahunakademik_tabel_pddikti2),
     * <br/> ...
     * <br/> dimana:
     * <br/> table--------------- : nama tabel institusi
     * <br/> uniquefield--------- : kolom-kolom sebagai parameter data akan diambil dari PDDIKTI ke Institusi
     * <br/> istahunakademikkrs-- : OPSIONAL - tahunakademikkrs adalah tahun akademik aktif, di mana pada saat itu mahasiswa melakukan KRS. true (tahun akademik tidak berubah) atau false (tahun akademik menjadi tahun akademik sebelumnya)
     * <br/> filtertahunakademik- : filter data untuk tabel PDDIKTI berdasarkan tahun akademik; menggunakan [tahunakademik], atau [tahun]
     */
    private function peta_ekstrak()
    {
        $this->peta["extract"]  = array (  "dosen_pt"           =>  array(  "table"                 => "ak_penugasan",
                                                                            "uniquefield"           => array("id_sdm,id_reg_ptk,id_thn_ajaran","id_sdm,id_reg_ptk,tahun"),
                                                                            "istahunakademikkrs"    => true,
                                                                            "filtertahunakademik"   => "t.id_thn_ajaran=[tahun]"
                                                                        ),
                                            "satuan_pendidikan" =>  array(  "table"                 => "pt_perguruantinggi",
                                                                            "uniquefield"           => array("id_sp", "guid"),
                                                                            "istahunakademikkrs"    => false,
                                                                            "filtertahunakademik"   => ""
                                                                        ),
                                            "sms"               =>  array(  "table"                 => "pt_programstudi_perguruantinggi",
                                                                            "uniquefield"           => array("id_sms", "guidprodi"),
                                                                            "istahunakademikkrs"    => false,
                                                                            "filtertahunakademik"   => ""
                                                                        )
                                        );
    }
    
    /**
     * pemetaan kunci primer PDDIKTI dan Institusi.
     * <br/> indeks:
     * <br/> - pk
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array(kunciprimer1_pddikti => kunciprimer1_institusi),
     * <br/> nama_tabel_pddikti2 => array(kunciprimer2_pddikti => kunciprimer2_institusi),
     * <br/> ...
     * <br/> catatan:
     * <br/> - semua tabel di FEEDER PDDIKTI dengan tipe REF harus dimasukkan, tampilkan dengan $ws->ListTable()
     */
    private function peta_pk()
    {
        $this->peta["pk"]   = array (   /* tabel Referensi */
                                        "agama"                                     => array("id_agama", "kdagama"),
                                        "bentuk_pendidikan"                         => array("id_bp", ""),
                                        "ikatan_kerja_dosen"                        => array("id_ikatan_kerja", "kdikatankerjadosen"),
                                        "jabfung"                                   => array("id_jabfung", "kdjafa"),
                                        "jalur_masuk"                               => array("id_jalur_masuk", ""),
                                        "jenis_evaluasi"                            => array("id_jns_eval", ""),
                                        "jenis_keluar"                              => array("id_jns_keluar", "kdjeniskeluar"),
                                        "jenis_sert"                                => array("id_jns_sert", ""),
                                        "jenis_sms"                                 => array("id_jns_sms", ""),
                                        "jenis_subst"                               => array("id_jns_subst", ""),
                                        "jenjang_pendidikan"                        => array("id_jenj_didik", "kdjenjang"),
                                        "jurusan"                                   => array("id_jur", "kodeprodi"),
                                        "kebutuhan_khusus"                          => array("id_kk", ""),
                                        "lembaga_pengangkat"                        => array("id_lemb_angkat", ""),
                                        "level_wilayah"                             => array("id_level_wil", ""),
                                        "negara"                                    => array("id_negara", "kdnegara"),
                                        "pangkat_gol"                               => array("id_pangkat_gol", ""),
                                        "pekerjaan"                                 => array("id_pekerjaan", "kdpekerjaanpddikti"),
                                        "jenis_pendaftaran"                         => array("id_jns_daftar", "kdjeniskelaspddikti"),
                                        "penghasilan"                               => array("id_penghasilan", "kdpenghasilan"),
                                        "semester"                                  => array("id_smt", "kdtahunakademik"),
                                        "status_keaktifan_pegawai"                  => array("id_stat_aktif", ""),
                                        "status_kepegawaian"                        => array("id_stat_pegawai", ""),
                                        "status_mahasiswa"                          => array("id_stat_mhs", ""),
                                        "wilayah"                                   => array("id_wil", "idwil"),
                                        "tahun_ajaran"                              => array("id_thn_ajaran", ""),
                                        /* tabel Isian hanya mengisi PK untuk institusi */
                                        "satuan_pendidikan institusi"               => array("id_sp", "idkonfigurasi"),
                                        "satuan_pendidikan"                         => array("npsn", "kodept"),
                                        "sms institusi"                             => array("", "kodeprodi"),
                                        "kurikulum"                                 => array("", "kdkurikulum"),
                                        "mata_kuliah"                               => array("", "kdmatakuliah"),
                                        "mahasiswa"                                 => array("id", "p.kdperson"),
                                        "mahasiswa updatedata"                      => array("id", "null"),
                                        "mahasiswa_pt"                              => array("id", "kdmahasiswa"),
                                        "mahasiswa_pt updatedata"                   => array("id", "null"),
                                        "dosen"                                     => array("", "p.kdperson"),
                                        "kelas_kuliah"                              => array("id", "kdpenawaran"),
                                        "kuliah_mahasiswa"                          => array("id", "kdmhsckd"),
                                        "kuliah_mahasiswa aktif"                    => array("id", "kdrekapipk"),
                                        "kuliah_mahasiswa aktif_update"             => array("id", "kdrekapipk"),
                                        "kuliah_mahasiswa lulus_keaktifan"          => array("id", "kdrekapipk"),
                                        "mahasiswa_pt lulus"                        => array("id", "m.kdmahasiswa"),
                                        "mahasiswa_pt lulus_updatedata"             => array("id", "null"),
                                        "mahasiswa_pt keluar"                       => array("id", "m.kdmahasiswa"),
                                        "nilai_transfer"                            => array("id", "kdkrsnilai"),
                                        "mata_kuliah_kurikulum"                     => array("id", "kdmatakuliah"),
                                        "nilai krs"                                 => array("id", "kdkrsnilai"),
                                        "nilai update"                              => array("id", "kdkrsnilai"),
                                        "ajar_dosen"                                => array("id", "tt.kdtimteaching"),
                                        "ajar_dosen updatedata"                     => array("id", "null"),
                                        "aktivitas_mahasiswa tugasakhir"            => array("id", "kdmahasiswa"),
                                        "anggota_aktivitas_mahasiswa tugasakhir"    => array("id", "kdmahasiswa"),
                                        "bimbing_mahasiswa tugasakhir"              => array("id", "kdkaryatulispenguji"),
                                        "uji_mahasiswa tugasakhir"                  => array("id", "kdkaryatulispenguji"),
                                        "aktivitas_mahasiswa prestasi"              => array("id", "kdprestasi"),
                                        "anggota_aktivitas_mahasiswa prestasi"      => array("id", "kdprestasi"),
                                        "bimbing_mahasiswa prestasi"                => array("id", "kdprestasi"),
                                        "aktivitas_mahasiswa bimbing_aka"           => array("id", "grouping"),
                                        "anggota_aktivitas_mahasiswa bimbing_aka"   => array("id", "kdmahasiswapa"),
                                        "bimbing_mahasiswa bimbing_aka"             => array("id", "grouping")
                                    );
    }
    
    /**
     * pemetaan guid PDDIKTI dan Institusi
     * <br/> indeks:
     * <br/> - guid
     * <br/> isi:
     * <br/> nama_tabel_pddikti1 => array ( array("guid" => array(field_guid_pddikti1, field_guid_institusi1), "variable" => array(field_pddikti1, field_institusi1), "table" => array("check" => tabel_institusi_untuk_cek1, "update" => tabel_institusi_untuk_update1), "prerequisite" => parameter_yang_harus_ada_pada_tabel_pddikti1, "filter" => filter_tabel_institusi1, "infotambahanerror" => field_tambahan_ketika_error1, "order by" => parameter_pengurutan1, "forcedouble" => perilaku_ketika_ada_data_rangkap1, "tahunakademikinjectdipakai" => tahunakademikinjectdipakai1), array(...), ... ),
     * <br/> nama_tabel_pddikti2 => array ( array("guid" => array(field_guid_pddikti2, field_guid_institusi2), "variable" => array(field_pddikti2, field_institusi2), "table" => array("check" => tabel_institusi_untuk_cek2, "update" => tabel_institusi_untuk_update2), "prerequisite" => parameter_yang_harus_ada_pada_tabel_pddikti2, "filter" => filter_tabel_institusi2, "infotambahanerror" => field_tambahan_ketika_error2, "order by" => parameter_pengurutan2, "forcedouble" => perilaku_ketika_ada_data_rangkap2, "tahunakademikinjectdipakai" => tahunakademikinjectdipakai2), array(...), ... ),
     * <br/> ...
     * <br/> dimana:
     * <br/> guid------------------------ : nama kolom PDDIKTI yang terdapat kunci primer untuk PDDIKTI dan Institusi
     * <br/> variable-------------------- : kolom yang digunakan untuk menyamakan data
     * <br/> table----------------------- : tabel yang dicek di institusi, tabel yang diupdate di institusi
     * <br/> prerequisite---------------- : kolom yang PDDIKTI yang harus ada isinya
     * <br/> filter---------------------- : filter untuk tabel institusi
     * <br/> infotambahanerror----------- : info tambahan ketika ada kesalahan
     * <br/> order by-------------------- : parameter pengurutan tabel institusi
     * <br/> forcedouble----------------- : perilaku ketika ada data rangkap
     * <br/> tahunakademikinjectdipakai-- : berapa banyak parameter tahunakademik yang dipakai dari peta["inject"][namatabel], -1 berarti semua
     */
    private function peta_guid()
    {
        $this->peta["guid"] = array (   /* tabel Referensi tidak memerlukan mapping ini */
                                        "satuan_pendidikan institusi"               => array(   array(  "guid"                          => array("id_sp", "k.guid"), 
                                                                                                        "variable"                      => array("npsn", "k.kodept"), 
                                                                                                        "table"                         => array("check" => "konfigurasi k", "update" => "konfigurasi k"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "", 
                                                                                                        "fieldtambahanerror"            => "",
                                                                                                        "infotambahanerror"             => "", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "sms institusi"                             => array(   array(  "guid"                          => array("id_sms", "ps.guid"), 
                                                                                                        "variable"                      => array("kode_prodi", "ps.kodeprodi"), 
                                                                                                        "table"                         => array("check" => "ak_programstudi ps", "update" => "ak_programstudi ps"), 
                                                                                                        "prerequisite"                  => "id_sp", 
                                                                                                        "filter"                        => "", 
                                                                                                        "fieldtambahanerror"            => "namaprodi",
                                                                                                        "infotambahanerror"             => "namaprodi", 
                                                                                                        "order by"                      => "ps.kodeprodi", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "kurikulum"                                 => array(   array(  "guid"                          => array("id_kurikulum_sp", "k.guid"), 
                                                                                                        "variable"                      => array("raw.id_sms,raw.id_smt", "ps.guid,k.tahun"), 
                                                                                                        "table"                         => array("check" => "ak_kurikulum k join ak_programstudi ps on ps.kdunitkerja=k.kdunitkerja",  "update" => "ak_kurikulum k"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "k.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "kurikulum",
                                                                                                        "infotambahanerror"             => "kurikulum", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "mata_kuliah"                               => array(   array(  "guid"                          => array("id_mk", "m.guid"), 
                                                                                                        "variable"                      => array("raw.kode_mk,s.id_sms,raw.sks_mk", "m.kodematakuliah,p.guid,m.sks"), 
                                                                                                       "table"                         => array("check" => "ak_matakuliah m join ak_kurikulum k on k.kdkurikulum=m.kdkurikulum join ak_programstudi p on p.kdunitkerja=k.kdunitkerja", "update" => "ak_matakuliah m"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0 and k.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "namaprodi,matakuliah,sksteori,skspraktikum,skstutorial,skslapangan",
                                                                                                        "infotambahanerror"             => "namaprodi,matakuliah,sksteori,skspraktikum,skstutorial,skslapangan", 
                                                                                                        "order by"                      => "m.kodematakuliah,m.sks", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "mata_kuliah_kurikulum"                     => array(   array(  "guid"                          => array("id_mk", "m.guidmkkurikulum"), 
                                                                                                        "variable"                      => array("p.id_kurikulum_sp,p.id_mk", "k.guid,m.guid"), 
                                                                                                        "table"                         => array("check" => "ak_matakuliah m join ak_kurikulum k on k.kdkurikulum=m.kdkurikulum", "update" => "ak_matakuliah m"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0 and k.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "m.kdmatakuliah,kodematakuliah,matakuliah",
                                                                                                        "infotambahanerror"             => "kdmatakuliah,kodematakuliah,matakuliah", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "mahasiswa"                                 => array(   array(  "guid"                          => array("id_pd", "p.guidmahasiswa"), 
                                                                                                        "variable"                      => array("nm_pd,raw.tmpt_lahir,raw.tgl_lahir", "upper(p.namalengkap),upper(p.tempatlahir),p.tanggallahir"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_mahasiswa p", "update" => "pt_person p"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "p.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "nim",
                                                                                                        "infotambahanerror"             => "nim", 
                                                                                                        "order by"                      => "p.namalengkap", 
                                                                                                        "forcedouble"                   => array( "table"       => "mahasiswa_pt",
                                                                                                                                                  "field"       => "id_pd"
                                                                                                                                                ),
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "mahasiswa_pt"                              => array(   array(  "guid"                          => array("id_reg_pd", "m.guid"), 
                                                                                                        "variable"                      => array("nipd,p.id_sms", "m.nim,p.guid"), 
                                                                                                        "table"                         => array("check" => "ak_mahasiswa m join ak_programstudi p on m.kdunitkerja=p.kdunitkerja", "update" => "ak_mahasiswa m"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "m.kdperson, nim",
                                                                                                        "infotambahanerror"             => "kdperson, nim", 
                                                                                                        "order by"                      => "m.nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "dosen"                                     => array(   array(  "guid"                          => array("id_sdm", "p.guiddosen"), 
                                                                                                        "variable"                      => array("nm_sdm,raw.tmpt_lahir,raw.tgl_lahir", "upper(p.namalengkap),upper(tempatlahir),p.tanggallahir"), 
                                                                                                        /*"variable"                      => array("raw.nidn", "d.nidn"), */
                                                                                                        "table"                         => array("check" => "pt_person p", "update" => "pt_person p"), 
                                                                                                        /*"table"                         => array("check" => "pt_person p join ak_dosen d on d.kdperson=p.kdperson", "update" => "pt_person p"), */
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "p.kdperson in (select distinct kdpersonepsbed from ((select kdpersonepsbed from ak_timteaching where kdtahunakademik>20152) union all (select kdpersonepsbed from ak_timteaching_lab where kdtahunakademik>20152)) pe)", 
                                                                                                        "fieldtambahanerror"            => "p.namalengkap",
                                                                                                        "infotambahanerror"             => "namalengkap", 
                                                                                                        "order by"                      => "p.namalengkap", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "kelas_kuliah"                              => array(   array(  "guid"                          => array("id_kls", "pm.guid"), 
                                                                                                        "variable"                      => array("p.id_smt,raw.nm_kls,p.id_mk", "pm.kdtahunakademik,kelasepsbed as kelas,m.guid as guidmatakuliah"), 
                                                                                                        "table"                         => array("check" => "ak_penawaranmatakuliah pm join ak_matakuliah m on m.kdmatakuliah=pm.kdmatakuliah join ak_tahunakademik ta on ta.kdtahunakademik=pm.kdtahunakademik", "update" => "ak_penawaranmatakuliah pm"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "ta.isremidial=0 and pm.kdtahunakademik>=20091 and not isnull(m.guid) and pm.isignore=0 and m.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "m.kodematakuliah,m.matakuliah,kelas",
                                                                                                        "infotambahanerror"             => "kodematakuliah,matakuliah,kelas", 
                                                                                                        "order by"                      => "m.kdkurikulum,m.kodematakuliah", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "kuliah_mahasiswa"                          => array(   array(  "guid"                          => array("id_reg_pd,id_smt", "c.guid"), 
                                                                                                        "variable"                      => array("p.id_reg_pd,p.id_smt,p.id_stat_mhs", "m.guid,c.kdtahunakademik,if(c.kdaktivitasmhs='K','N',c.kdaktivitasmhs) as kdaktivitasmhs"), 
                                                                                                        "table"                         => array("check" => "ak_mahasiswa_ckd c join ak_mahasiswa m on m.kdmahasiswa=c.kdmahasiswa join pt_person p on p.kdperson=m.kdperson", "update" => "ak_mahasiswa_ckd c"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "c.isignore=0 and m.isignore=0 and c.kdaktivitasmhs not in ('L', 'A')", //--> mahasiswa keluar harus di non aktifkan terlebih dahulu
                                                                                                        "fieldtambahanerror"            => "m.nim,p.namalengkap",
                                                                                                        "infotambahanerror"             => "nim,namalengkap", 
                                                                                                        "order by"                      => "c.kdaktivitasmhs, c.kdtahunakademik,m.nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "mahasiswa_pt keluar"                       => array(   array(  "guid"                          => array("id_reg_pd", "m.guidlulus"), 
                                                                                                        "variable"                      => array("raw.id_reg_pd,p.id_jns_keluar", "m.guid,jeniskeluar"), 
                                                                                                        "table"                         => array("check" => "(select 4 as jeniskeluar) l,ak_mahasiswa m join ak_mahasiswa_ckd c on c.kdmahasiswa=m.kdmahasiswa", "update" => "ak_mahasiswa m"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0 and kdaktivitasmhs='K'", 
                                                                                                        "fieldtambahanerror"            => "m.nim,c.kdtahunakademik",
                                                                                                        "infotambahanerror"             => "nim,kdtahunakademik", 
                                                                                                        "order by"                      => "c.kdtahunakademik,m.nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "mahasiswa_pt lulus"                        => array(   array(  "guid"                          => array("id_reg_pd", "m.guidlulus"), 
                                                                                                        "variable"                      => array("raw.id_reg_pd,p.id_jns_keluar", "m.guid,jeniskeluar"), 
                                                                                                        "table"                         => array("check" => "(select 1 as jeniskeluar) l,pddikti_v_lulus_mahasiswa m", "update" => "ak_mahasiswa m"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "m.nim,m.kdtahunakademik",
                                                                                                        "infotambahanerror"             => "nim,kdtahunakademik", 
                                                                                                        "order by"                      => "m.kdtahunakademik,m.nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "kuliah_mahasiswa aktif"                    => array(   array(  "guid"                          => array("id_reg_pd,id_smt", "ri.guidinsert"), 
                                                                                                        "variable"                      => array("p.id_reg_pd,p.id_smt,p.id_stat_mhs", "m.guid,ri.kdtahunakademik,krs.kdaktivitasmhs"), 
                                                                                                        "table"                         => array("check" => "(select distinct kdmahasiswa, kdtahunakademik, 'A' as kdaktivitasmhs from ak_krsnilai) krs join ak_rekap_ipk ri on (ri.kdmahasiswa=krs.kdmahasiswa and ri.kdtahunakademik=krs.kdtahunakademik) join ak_mahasiswa m on m.kdmahasiswa=ri.kdmahasiswa", "update" => "ak_rekap_ipk ri"), 
//                                                                                                        "table"                         => array("check" => "((select kdmahasiswa, kdtahunakademik, if(isnull(kdkrsnilaiequivalensi),'A','M') as kdaktivitasmhs from ak_krsnilai where kdtahunakademik=[internalfilter]) union all (select kdmahasiswa, kdtahunakademik, 'M' as kdaktivitasmhs from ak_krsnilai_equivalensi where [internalfilter])) krs join ak_rekap_ipk ri on (ri.kdmahasiswa=krs.kdmahasiswa and ri.kdtahunakademik=krs.kdtahunakademik) join ak_mahasiswa m on m.kdmahasiswa=ri.kdmahasiswa", "update" => "ak_rekap_ipk ri"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "m.nim,ri.kdtahunakademik",
                                                                                                        "infotambahanerror"             => "nim,kdtahunakademik", 
                                                                                                        "order by"                      => "ri.kdtahunakademik,m.nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "kuliah_mahasiswa aktif_update"             => array(   array(  "guid"                          => array("id_reg_pd,id_smt", "ri.guidupdate"), 
                                                                                                        "variable"                      => array("p.id_reg_pd,p.id_smt,p.id_stat_mhs", "m.guid,ri.kdtahunakademik,krs.kdaktivitasmhs"), 
                                                                                                        "table"                         => array("check" => "(select distinct kdmahasiswa, kdtahunakademik, 'A' as kdaktivitasmhs from ak_krsnilai) krs join ak_rekap_ipk ri on (ri.kdmahasiswa=krs.kdmahasiswa and ri.kdtahunakademik=krs.kdtahunakademik) join ak_mahasiswa m on m.kdmahasiswa=ri.kdmahasiswa", "update" => "ak_rekap_ipk ri"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0 and (isnull(kdyudisium) or kdyudisium=0)", 
                                                                                                        "fieldtambahanerror"            => "m.nim,ri.kdtahunakademik",
                                                                                                        "infotambahanerror"             => "nim,kdtahunakademik", 
                                                                                                        "order by"                      => "ri.kdtahunakademik,m.nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "kuliah_mahasiswa lulus_keaktifan"          => array(   array(  "guid"                          => array("id_reg_pd,id_smt", "ri.guidlulus"), 
                                                                                                        "variable"                      => array("p.id_reg_pd,p.id_smt,p.id_stat_mhs", "m.guid,y.kdtahunakademik,l.lulus"), 
                                                                                                        "table"                         => array("check" => "(select 'L' as lulus) l,ak_mahasiswa m join ak_yudisium y on y.kdyudisium = m.kdyudisium join ak_rekap_ipk ri on ri.kdtahunakademik = y.kdtahunakademik and m.kdmahasiswa = ri.kdmahasiswa", "update" => "ak_rekap_ipk ri"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "m.nim,ri.kdtahunakademik",
                                                                                                        "infotambahanerror"             => "nim,kdtahunakademik", 
                                                                                                        "order by"                      => "ri.kdtahunakademik,m.nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "aktivitas_mahasiswa tugasakhir"            => array(   array(  "guid"                          => array("id_akt_mhs", "guidjudulakt"), 
                                                                                                        "variable"                      => array("raw.id_akt_mhs", "guidjudulakt"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_tugasakhirakt t", "update" => "ak_mahasiswa m"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "",
                                                                                                        "infotambahanerror"             => "nim", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "anggota_aktivitas_mahasiswa tugasakhir"    => array(   array(  "guid"                          => array("id_ang_akt_mhs", "guidjudulang"), 
                                                                                                        "variable"                      => array("raw.id_akt_mhs", "guidjudulakt"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_tugasakhirang t", "update" => "ak_mahasiswa m"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "",
                                                                                                        "infotambahanerror"             => "", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "bimbing_mahasiswa tugasakhir"              => array(   array(  "guid"                          => array("id_bimb_mhs", "guid"), 
                                                                                                        "variable"                      => array("a.id_akt_mhs,s.id_sdm,k.id_katgiat", "guidjudulakt,guiddosen,id_katgiat"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_dosen_pembimbing p", "update" => "ak_karyatulis_penguji p"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "namadosen, nim",
                                                                                                        "infotambahanerror"             => "namadosen, nim", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "uji_mahasiswa tugasakhir"                  => array(   array(  "guid"                          => array("id_uji_mhs", "guid"), 
                                                                                                        "variable"                      => array("a.id_akt_mhs,s.id_sdm,k.id_katgiat", "guidjudulakt,guiddosen,id_katgiat"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_dosen_penguji p", "update" => "ak_karyatulis_penguji p"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "namadosen, nim",
                                                                                                        "infotambahanerror"             => "namadosen, nim", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "aktivitas_mahasiswa prestasi"              => array(   array(  "guid"                          => array("id_akt_mhs", "guidakt"), 
                                                                                                        "variable"                      => array("raw.id_akt_mhs", "guidakt"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_prestasirakt t", "update" => "ak_prestasimahasiswa pm"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "",
                                                                                                        "infotambahanerror"             => "nim", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "anggota_aktivitas_mahasiswa prestasi"      => array(   array(  "guid"                          => array("id_ang_akt_mhs", "guidang"), 
                                                                                                        "variable"                      => array("raw.id_akt_mhs", "guidakt"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_prestasiang t", "update" => "ak_prestasimahasiswa pm"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "",
                                                                                                        "infotambahanerror"             => "", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "bimbing_mahasiswa prestasi"                => array(   array(  "guid"                          => array("id_bimb_mhs", "guiddosenpembimbing"), 
                                                                                                        "variable"                      => array("a.id_akt_mhs,s.id_sdm,k.id_katgiat", "guidakt,guiddosen,idkatgiat"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_dosen_pembimbing_prestasi p", "update" => "ak_prestasimahasiswa pm"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "namadosen, nim",
                                                                                                        "infotambahanerror"             => "namadosen, nim", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "aktivitas_mahasiswa bimbing_aka"           => array(   array(  "guid"                          => array("id_akt_mhs", "guidakt"), 
                                                                                                        "variable"                      => array("raw.id_akt_mhs", "guidakt"), 
                                                                                                        "table"                         => array("check" => "ak_mahasiswa_pa t", "update" => "ak_mahasiswa_pa pm"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "",
                                                                                                        "infotambahanerror"             => "nim", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "anggota_aktivitas_mahasiswa bimbing_aka"   => array(   array(  "guid"                          => array("id_ang_akt_mhs", "guidang"), 
                                                                                                        "variable"                      => array("raw.id_akt_mhs", "guidakt"), 
                                                                                                        "table"                         => array("check" => "pddikti_v_bimbingakademikang t", "update" => "ak_mahasiswa_pa pm"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "isignore=0", 
                                                                                                        "fieldtambahanerror"            => "",
                                                                                                        "infotambahanerror"             => "", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "bimbing_mahasiswa bimbing_aka"             => array(   array(  "guid"                          => array("id_bimb_mhs", "guiddosenpembimbing"), 
                                                                                                        "variable"                      => array("a.id_akt_mhs,s.id_sdm", "guidakt,guiddosen"), 
                                                                                                        "table"                         => array("check" => "ak_mahasiswa_pa p join pt_person pr on p.kdpersonpa=pr.kdperson", "update" => "ak_mahasiswa_pa pm"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "", 
                                                                                                        "fieldtambahanerror"            => "namalengkap",
                                                                                                        "infotambahanerror"             => "namalengkap", 
                                                                                                        "order by"                      => "", 
                                                                                                        "group by"                      => "grouping",
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "nilai_transfer"                            => array(   array(  "guid"                          => array("id_ekuivalensi", "e.guid"), 
                                                                                                        "variable"                      => array("p.id_reg_pd,p.id_mk", "m.guid,mk.guid"), 
                                                                                                        "table"                         => array("check" => "ak_krsnilai_equivalensi e join ak_mahasiswa m on m.kdmahasiswa=e.kdmahasiswa join ak_matakuliah mk on mk.kdmatakuliah=e.kdmatakuliah", "update" => "ak_krsnilai_equivalensi e"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "e.isignore=0 and m.isignore=0 and mk.isignore=0 and not isnull(m.guid)", 
                                                                                                        "fieldtambahanerror"            => "nim,matakuliah",
                                                                                                        "infotambahanerror"             => "nim,matakuliah", 
                                                                                                        "order by"                      => "nim,matakuliah", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "nilai krs"                                 => array(   array(  "guid"                          => array("id_kls,id_reg_pd", "k.guidkrs"), 
                                                                                                        "variable"                      => array("p.id_kls,p.id_reg_pd", "p.guid,m.guid"), 
                                                                                                        "table"                         => array("check" => "ak_krsnilai_nonremidial k join ak_mahasiswa m on m.kdmahasiswa=k.kdmahasiswa join ak_penawaranmatakuliah p on p.kdpenawaran=k.kdpenawaran join ak_matakuliah mk on mk.kdmatakuliah=p.kdmatakuliah", "update" => "ak_krsnilai_nonremidial k"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0 and p.isignore=0 and k.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "nim, mk.kodematakuliah, p.kdpenawaran",
                                                                                                        "infotambahanerror"             => "nim, kodematakuliah, kdpenawaran", 
                                                                                                        "order by"                      => "nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "nilai update"                              => array(   array(  "guid"                          => array("id_kls,id_reg_pd", "k.guidnilai"), 
                                                                                                        "variable"                      => array("p.id_kls,p.id_reg_pd", "p.guid,m.guid"), 
                                                                                                        "table"                         => array("check" => "ak_krsnilai_nonremidial k join ak_mahasiswa m on m.kdmahasiswa=k.kdmahasiswa join ak_penawaranmatakuliah p on p.kdpenawaran=k.kdpenawaran", "update" => "ak_krsnilai_nonremidial k"), 
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "m.isignore=0 and p.isignore=0 and k.isignore=0 and (k.nilai<='E' or k.nilai='T') and not isnull(guidkrs)", 
                                                                                                        "fieldtambahanerror"            => "nim",
                                                                                                        "infotambahanerror"             => "nim", 
                                                                                                        "order by"                      => "nim", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => -1
                                                                                                     )
                                                                                    ),
                                        "ajar_dosen"                                => array(   array(  "guid"                          => array("id_ajar", "tt.guid"), 
                                                                                                        "variable"                      => array("p.id_reg_ptk,k.id_kls", "id_reg_ptk,pm.guid"), 
                                                                                                        "table"                         => array("check" => "ak_jadwalkuliah jk join ak_timteaching tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_penawaranmatakuliah pm on pm.kdpenawaran=tt.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed join ak_penugasan pn on (pn.id_sdm=p.guiddosen and pn.tahun=floor(tt.kdtahunakademik/10)) join ak_matakuliah m on m.kdmatakuliah=pm.kdmatakuliah join ak_kurikulum kr on kr.kdkurikulum=m.kdkurikulum join ak_programstudi ps on ps.kdunitkerja=kr.kdunitkerja", "update" => "ak_timteaching tt"),
                                                                                                    //"ak_timteaching tt join ak_penawaranmatakuliah pm on pm.kdpenawaran=tt.kdpenawaran"
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "tt.isignore=0 and pm.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "concat(namalengkap, '-', matakuliah, '-', kelas, '-', namaprodipendek) as datamengajar",
                                                                                                        "infotambahanerror"             => "datamengajar", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => 2,
                                                                                                        "troubleshoot"                  => "<ol><li>Dimungkinkan Penugasan akan dobel untuk Prodi yang mengalami migrasi sehingga akan muncul di list bermasalah, tetapi akan hilang pada eksekusi berikutnya</li></ol>"
                                                                                                     ),
                                                                                                array(  "guid"                          => array("id_ajar", "tt.guid"), 
                                                                                                        "variable"                      => array("p.id_reg_ptk,k.id_kls", "id_reg_ptk,pm.guid"), 
                                                                                                        "table"                         => array("check" => "ak_jadwalkuliah_lab jk join ak_timteaching_lab tt on tt.kdtimteaching=ifnull(jk.kdtimteachingperubahan, jk.kdtimteaching) join ak_kelompok k on k.kdkelompok=tt.kdkelompok join ak_penawaranmatakuliah pm on pm.kdpenawaran=k.kdpenawaran join pt_person p on p.kdperson=tt.kdpersonepsbed join ak_penugasan pn on (pn.id_sdm=p.guiddosen and pn.tahun=floor(tt.kdtahunakademik/10)) join ak_matakuliah m on m.kdmatakuliah=pm.kdmatakuliah join ak_kurikulum kr on kr.kdkurikulum=m.kdkurikulum join ak_programstudi ps on ps.kdunitkerja=kr.kdunitkerja", "update" => "ak_timteaching_lab tt"), 
                                                                                                    //"update" => "ak_timteaching_lab tt join ak_kelompok k on k.kdkelompok=tt.kdkelompok join ak_penawaranmatakuliah pm on pm.kdpenawaran=k.kdpenawaran"
                                                                                                        "prerequisite"                  => "", 
                                                                                                        "filter"                        => "tt.isignore=0 and pm.isignore=0", 
                                                                                                        "fieldtambahanerror"            => "concat(namalengkap, '-', matakuliah, '-', kelas, '-', namaprodipendek) as datamengajar",
                                                                                                        "infotambahanerror"             => "datamengajar", 
                                                                                                        "order by"                      => "", 
                                                                                                        "forcedouble"                   => "",
                                                                                                        "tahunakademikinjectdipakai"    => 2,
                                                                                                        "troubleshoot"                  => "<ol><li>Dimungkinkan Penugasan akan dobel untuk Prodi yang mengalami migrasi sehingga akan muncul di list bermasalah, tetapi akan hilang pada eksekusi berikutnya</li></ol>"
                                                                                                     )
                                                                                        ),
                                        "ajar_dosen update_data"                    => array(   array(  "infotambahanerror"             => "datamengajar"
                                                                                                     )
                                                                                        )
                                    );
    }
    
    /**
     * pemetaan update NIDN di Institusi, mengambil data dari PDDIKTI
     * <br/> indeks:
     * <br/> - updatenidn
     * <br/> isi:
     * <br/> array("table" => array(nama_tabel_institusi, nama_tabel_pddikti), "nidn" => array(nama_kolom_nidn_institusi, nama_kolom_nidn_pddikti), "guid" => guid, "filter" => filter)
     * <br/> dimana:
     * <br/> table--- : nama tabel institusi dan PDDIKTI. berupa array()
     * <br/> nidn---- : nama kolom nidn institusi dan PDDIKTI. berupa array()
     * <br/> guid---- : nama kolom PDDIKTI yang terdapat kunci primer dari dosen
     * <br/> filter-- : filter untuk tabel institusi
     * <br/> catatan:
     * <br/> - hanya 1 array
     */
    private function peta_updatenidn()
    {
        $this->peta["updatenidn"]   = array (   "table"   => array("dosen", "ak_dosen"),
                                                "nidn"    => array("nidn", "nidn"),
                                                "guid"    => "id_sdm",
                                                "info"    => "nm_sdm",
                                                "filter"  => "kdperson in (select kdperson from pt_person where guiddosen=trim(':guid'))",
                                                "kroscek" => "select ".$this->peta["guid"]["dosen"][0]["guid"][1]." from ak_dosen d join pt_person p on p.kdperson=d.kdperson where nidn=trim(':nidn')"
                                            );
    }
    
    /**
     * mengembalikan pemetaan log
     * setting unik di database UNIQUE (ideksekusi, waktumulai)
     */
    function peta_log()
    {
        $this->peta["log"]  = array (   "table" =>  "feeder_log",
                                        "field" =>  ["idlog" => "idlog", "ideksekusi" => "ideksekusi", "url" => "url", "waktumulai" => "waktumulai", "waktuterakhir" => "waktuterakhir"]
                                    );
    }
    
    /**
     * mengembalikan pemetaan antara tabel dan kolom PDDIKTI dengan Institusi untuk keperluan pengecekan, sinkronisasi, injeksi dan ekstraksi data
     * @return string
     * - pemetaan antara tabel dan kolom PDDIKTI dengan Institusi
     */
    function peta()
    {
        if (!$this->isdipetakan)
        {
            $this->peta_kolom();
            $this->peta_tabel();
            $this->peta_injek();
            $this->peta_injek_usang();
            $this->peta_injek_perbaiki_usang();
            $this->peta_ekstrak();
            $this->peta_pk();
            $this->peta_guid();
            $this->peta_updatenidn();
            $this->peta_log();
        }
        return $this->peta;
    }
}
