<?php


/** 
 * readme.info.php
 * <br/> untuk mencatat catatan (termasuk perubahan-perubahan sistem)
 * <br/> profil  https://id.linkedin.com/in/basitadhi
 * <br/> buat    2017-04-27
 * <br/> rev     2021-10-23
 * <br/> sifat   open source
 * <br/> catatan:
 * <br/> - Bugs Fix (BF) & Improvement (IM)
 * <br/> - Hanya berupa catatan dan fungsi kosong berkomentar, tidak ada koding
 * @author Basit Adhi Prabowo, S.T. <basit@unisayogya.ac.id>
 * @access public
 */
class readme
{
    /**
     * <ol>
     * <li>Webservice V2</li>
     * <li>Inject Merdeka Belajar</li>
     * </ol>
     */
    function TODO()
    {
        
    }
    
    function _akanrilis()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF Tutup sesi jika tidak diperlukan (menyebabkan blocking)</li>
     * <li>IM Tambahan info eksekusi ketika error</li>
     * <li>IM Prestasi mahasiswa sekarang turut disertakan, termasuk merdeka belajar</li>
     * </ol>
     */
    function _20211023()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF Pada pddikti_injek(), sync update menggunakan $table, bukan $table_asli (sama dengan sync insert). Sinkronisasi juga dilakukan untuk MODE_INJECT_INDIVIDU. MODE_INJECT_GAGAL tidak ikut disinkron</li>
     * <li>BF Cek Tabel ada yang hanya muncul 1 baris, padahal lebih dari satu baris. Tambah peringatan untuk menambah peringatan bahwa $this->peta["pk"] untuk tabel tersebut belum ada</li>
     * <li>IM Tambahkan info [".$this->db["info"][$iddb]."] di semua query update/insert/delete (contoh hasil: [Rows matched: 1 Changed: 1 Warnings: 1]). Informasi update/insert/delete diletakkan setelah query (bukan sebelum query)</li>
     * <li>IM Lengkapi informasi error pada update/insert/delete</li>
     * <li>IM Perubahan pemetaan kuliah_mahasiswa menjadi "p.id_reg_pd,p.id_smt,p.id_stat_mhs" pada variabel PDDIKTI</li>
     * <li>IM Menampilkan daftar perubahan/penambahan web service, agar programmer tidak buta perubahan</li>
     * <li>IM Menampilkan daftar method webservice PDDIKTI</li>
     * <li>IM Jika jumlah sync sama dengan jumlah injeksi, maka tidak perlu sync. Tambahkan parameter error di pddikti_sinkron_guid(), pddikti_sinkron_guid_filterinjek() dan pddikti_sinkron_guid_tunggal()</li>
     * <li>IM Tambahkan data sms/prodi pada init.inc.php?a=1</li>
     * <li>IM Tambahkan tabel non referensi ke cek tabel. Non referensi selain tambahan tidak perlu dicek</li>
     * <li>IM Buat opsi baru di init.inc.php untuk menampilkan pemetaan secara visual. 1. Ubah fungsi cetak_tabel_parsial() agar dapat mencetak array dan indeksnya sebagai baris 2. Tambah fungsi cetak_tabel_sel() agar dapat mencetak string atau array 3. Tambah indeks ideksekusi di peta inject agar mudah dalam eksekusi
     * <li>IM Tambahkan variabel issudahcekpenugasan agar cek penugasan dieksekusi sekali saja</li>
     * <li>IM Perubahan struktur dosen pembimbing. Sekarang dipisah ke dalam beberapa tabel, menginduk ke kegiatan mahasiswa, cabangnya pembimbing, penguji, anggota.</li>
     * <li>IM Group by pada sync</li>
     * </ol>
     */
    function _20190126()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF Perbaikan peta peta_pk, pada pekerjaan</li>
     * <li>BF Sync setelah injek gagal (tambahkan ignore_alias)</li>
     * <li>BF Tidak diketahui apabila tidak error tetapi tidak ada data yang diproses ketika injek update</li>
     * <li>BF Aktifkan cek penugasan untuk injeksi ajar_dosen. TIdak dilanjutkan apabila penugasan belum sempurna. id_ptk berubah menjadi id_sdm, sesuaikan pemetaan updatenidn. Ubah istahunakademikkrs menjadi true pada mapdb["extract"]. Tambahkan field id_thn_ajaran pada pemetaan extract di dosen_pt </li>
     * <li>IM Menambahkan accordion, agar dapat memudahkan ketika melihat data (terutama jika ada yang sangat panjang datanya</li>
     * <li>IM Cek keaktifan Laporan Tahun Akademik. Jika aktif maka lanjutkan proses injek, tidak non aktif maka by pass. Tambah fungsi is_tahunakademikaktif(). Diaplikasikan di pddikti_injek() untuk yang terpengaruh dengan tahunakademik.</li>
     * <li>IM Tetapkan mode SQL setiap koneksi untuk menjamin stabilitas injeksi/sinkronisasi. Tambahkan di query("SET SESSION sql_mode='NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");</li>
     * <li>IM Pembersihan warning pada source code (Netbeans)</li>
     * <li>IM Adaptasi perubahan/penambahan fitur PDDIKTI: (1.) Mahasiswa (mahasiswa, mahasiswa_pt) tambah: NISN, NIK Ayah, NIK Ibu, NPWP, No BPJS (2.) Keaktifan Mahasiswa (kuliah_mahasiswa) sekarang hanya bisa untuk A, N, C; sedangkan L, K sudah tidak bisa lagi</li>
     * <li>IM Menambah menu pada init.inc.php</li>
     * </ol>
     */
    function _20180331()
    {
        
    }
    
    /**
     * <ol>
     * <li>Terjadi masalah ketika field bukan NULL, tetapi "NULL" atau "", buat fungsi filter_perbaiki_isnull() dan diaplikasikan di beberapa tempat</li>
     * <li>Memperbaiki mekanisme sinkron di injek</li>
     * </ol>
     */
    function _2017Mei04()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM sekarang aktif di github: https://github.com/basit-adhi/ws_pddikti</li>
     * <li>IM menambah fungsi sinkron_data_institusi()</li>
     * <li>IM edit beberapa mapping, penambahan info tambahan error</li> 
     * </ol>
     */
    function _2017Mei02()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF merubah strpos()!==false menjadi substr_count()>0 untuk mencari apakah sub string ada atau tidak pada string</li>
     * <li>BF menggunakan $this->filtertahunakademik untuk memanggil fungsi pddikti_sinkron_guid() pada fungsi pddikti_injek()</li>
     * <li>IM menambah fungsi peta_injek_usang() dan peta_injek_perbaiki_usang()</li>
     * <li>IM menambah fungsi pddikti_injek_perbaiki_usang()</li>
     * <li>IM menambah parameter modeinjek dan ignorenull pada fungsi pddikti_injek() untuk mengakomodir fungsi pddikti_injek_perbaiki_usang()</li>
     * <li>IM cetak daftar lengkap penugasan tahun tersebut pada fungsi cek_penugasan(), karena ada kemungkinan perubahan UUID/GUID dosen</li>
     * <li>IM penggunaan upper dan lower akan disamakan antara PDDIKTI dan Institusi</li>
     * <li>IM mekanisme sinkronisasi untuk satu tabel PDDIKTI ke banyak tabel Institusi, penambahan dimensi array pada array peta["guid"], penambahan fungsi private pddikti_sinkronisasi_tunggal(), private pddikti_sinkronisasi_injek_insert(), private pddikti_sinkronisasi_injek_update() dan perubahan fungsi pddikti_sinkronisasi() untuk mengakomodir penambahan dimensi array</li>
     * <li>IM menambah indeks "tahunakademikinjectdipakai" pada array peta["guid"], menambah fungsi pddikti_sinkron_guid_filterinjek() dan menyesuaikan fungsi filtertahunakademik() agar hanya menggunakan parameter sebanyak yang diinginkan</li>
     * <li>IM menambah variabel issinkron_injek (apakah perlu memanggil fungsi pddikti_sinkron_guid()? mengingat sudah ada proses sinkronisasi bersamaan dengan data diinjek)</li>
     * <li>IM memindahkan readme.txt ke dalam kelas readme (readme.info.php) -> agar keluar di dokumentasi :)</li>
     * <li>IM menambah indeks tahunakademik pada peta["injek"]["nilai_transfer"]</li>
     * </ol>
     */
    function _2017Apr27()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM memindahkan mapdb ke dalam kelas mapdb -> agar keluar di dokumentasi :)</li>
     * <li>IM dokumentasi seluruh kelas/fungsi</li>
     * <li>IM pindah fungsi mode() ke webservice.inc.php</li>
     * </ol>
     */
    function _2017Mar03()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM penambahan fungsi extract_pddikti(), salah satunya untuk mengambil data penugasan</li>
     * <li>IM penambahan fungsi cek_penugasan(), untuk cek apakah semua dosen mengajar sudah dimasukkan ke Penugasan di Feeder</li>
     * <li>IM penambahan fungsi filtertahunakademik(), untuk memberikan filter tahunakademik lebih dari satu, misal: select * from a join b on a.kdprimer=b.kdprimer where a.kdtahunakademik=20151 and b.kdtahunakademik=20151. Untuk mengakomodir adanya partisi.</li>
     * <li>IM tidak bisa melakukan injeksi apabila belum semua dosen ditugaskan</li>
     * <li>IM pengalih-bahasaan fungsi dan variabel ke dalam bahasa Indonesia</li>
     * </ol>
     */
    function _2017Mar01()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF fungsi mysqli_free_result(), inject_pddikti() dan ignore_alias()</li>
     * </ol>
     */
    function _2016Mei21()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF perbaikan sync_guid dan inject</li>
     * <li>BF penghitungan tahunakademik sebelum</li>
     * <li>BF perubahan perilaku isnull pada primary key met<ode update, dari isnull menjadi not isnull</li>
     * <li>IM penggabungan data yang digunakan lebih dari satu kali pada check_table()</li>
     * <li>IM menambah kemampuan untuk memberi ID pada tabel</li>
     * <li>IM menghilangkan fitur "force sync"</li>
     * <li>IM menambah item yang diignore pada saat injeksi dan dapat dieksekusi secara individu, dengan flag isignore</li>
     * </ol>
     */
    function _2016Mei20()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM memperbaiki kinerja inject_pddikti(), yaitu dengan langsung memasukkan UUID yang diterima dari proses Insert melalui web service</li>
     * <li>IM menambah fungsi ignore_alias()</li>
     * </ol>
     */
    function _2016Apr04()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM menambah fitur "force sync" dan jenis injeksi berupa update data (terutama untuk nilai) pada inject_pddikti()</li>
     * </ol>
     */
    function _2016Jan28()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF kesalahan nomor proses pada fungsi inject_pddikti()</li>
     * <li>IM memungkinkan untuk inject satu tabel lebih dari satu kali</li>
     * <li>IM mengubah mekanisme kdtahunakademik, memindah tanda pembanding ke mapping</li>
     * </ol>
     */
    function _2015Des29()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM menghilangkan nilai null ke dalam fungsi inject_pddikti()</li>
     * </ol>
     */
    function _2015Des28()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM menambah fitur untuk update NIDN dari Institusi berdasarkan data dari PDDIKTI pada fungsi update_nidn()</li>
     * <li>IM menambah filter pada tabel institusi untuk fungsi sync_guid()</li>
     * <li>IM menambah fungsi inject_pddikti()</li>
     * <li>IM menambah variabel iddb pada koneksi mysql</li>
     * </ol>
     */
    function _2015Des13()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM menambah fitur untuk update guid Institusi yang berasal dari composite key di PDDIKTI --> misalnya keaktifan mahasiswa</li>
     * <li>IM menambah fitur untuk otomatis memperbaiki data dobel menjadi satu --> misalnya personal mahasiswa --> very-very dangerous, backup dahulu datanya</li>
     * </ol>
     */
    function _2015Des12()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF memperbaiki fungsi mysqli_retrieve() dan sync_guid() ketika ada data yang kosong</li>
     * <li>BF memperbaiki fungsi mysqli_free_result() karena header menambah terus</li>
     * <li>IM menambah order by pada fungsi sync_guid()</li>
     * </ol>
     */
    function _2015Des11()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF menambah ignore_count pada cetak tabel secara parsial</li>
     * <li>IM menambah cek daftar guid yang ada di PDDIKTI tetapi tidak ada di Institusi</li>
     * <li>IM menambah filter tabel Institusi pada fungsi sync_guid()</li>
     * <li>IM menambah info tambahan error pada fungsi sync_guid()</li>
     * <li>IM menambah info field pada fungsi mysqli_retrieve()</li>
     * </ol>
     */
    function _2015Des10()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM menambah fungsi untuk mencetak indeks array menjadi tabel (bagian dari tabel secara parsial)</li>
     * <li>IM menambah fungsi untuk mencetak semua tabel dan deskripsinya</li>
     * <li>IM menambah fungsi untuk membuat array menjadi simetris</li>
     * </ol>
     */
    function _2015Des08()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM menambah filter pada tabel pddikti untuk fungsi sync_guid()</li>
     * <li>IM menambah lebih banyak variabel yang dapat digunakan untuk perbandingan pada sync_guid()</li>
     * <li>IM menambah fungsi untuk menentukan apakah kumpulan kata ada pada suatu kalimat --> is_exist()</li>
     * <li>IM menambah informasi jumlah data pada cetak tabel</li>
     * </ol>
     */
    function _2015Des07()
    {
        
    }
    
    /**
     * <ol>
     * <li>IM penambahan fungsi untuk sinkronisasi GUID</li>
     * </ol>
     */
    function _2015Des02()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF kesalahan logika pada fungsi partial_print_table dan penambahan cetak untuk satu baris</li>
     * <li>IM menambah fungsi untuk menghitung dimensi array</li>
     * <li>IM menambah fungsi untuk mencetak recordset</li>
     * <li>IM penambahan array kelompok MK dan jenis MK, karena tidak tersedia di Web Service</li>
     * </ol>
     */
    function _2015Nov30()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF terdapat pesan error: Allowed memory size of 123456789 bytes exhausted (tried to allocate 1234567 bytes) in /var/www/???/nusoap.php on line 291; sepertinya dia lelah</li>
     * <li>solusi:
     * <br/>1. mengambil data per n baris
     * <br/>2. menambah skrip bersih-bersih: unset pada array atau object, mysqli_free_result
     * <br/>3. Secara manual, edit pada nusoap.php: (sumber: http://stackoverflow.com/questions/13511058/nusoap-vardump-php-fatal-error-allowed-memory-size-of-134217728-bytes-exhausted)
     * <br/>--a. $GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'] = 9 menjadi 0
     * <br/>--b. Ubah fungsi VarDump menjadi:
     * <br/>----function varDump($data) {
     * <br/>----$ret_val = "";
     * <br/>----if ($this->debugLevel > 0) {
     * <br/>----ob_start();
     * <br/>----var_dump($data);
     * <br/>----$ret_val = ob_get_contents();
     * <br/>----ob_end_clean();
     * <br/>----}
     * <br/>----return $ret_val;
     * <br/>----}</li>
     * <li>BF terdapat pesan error: Maximum execution time of 30 seconds exceeded</li>
     * <li>solusi:</li>
     * <li>mengubah batas waktu dengan fungsi set_time_limit(EXECUTION_TIME_LIMIT);</li>
     * <li>IM menambah buffering --> ob_start() dkk</li>
     * <li>IM menambah fungsi menampilkan tabel secara parsial</li>
     * <li>IM menambah exception (perkecualian) pada fungsi check_tabel, sehingga bisa mengabaikan pengecekan pada tabel-tabel tertentu</li>
     * </ol>
     */
    function _2015Nov26()
    {
        
    }
    
    /**
     * <ol>
     * <li>BF penambahan fungsi ping() untuk mengecek kehidupan server (mencoba $nusoap->getError() tidak berhasil)</li>
     * <li>BF harus ada fungsi trim() untuk kunci primer dari FEEDER PDDIKTI, karena terkadang ada tambahan spasi</li>
     * </ol>
     */
    function _2015Nov20()
    {
        
    }
}
?>
