<?php
// Eksperimen kunci (Bagian G) + pengujian validasi (Bagian J, skenario 03-06 dan 12)
require_once __DIR__ . '/DataMahasiswa.php';

echo "<pre>";
$daftar = [
    new MahasiswaReguler('23001', 'Andi', 'Informatika', 4, 5000000),
    new MahasiswaBeasiswa('23002', 'Siti', 'Informatika', 4, 5000000, 60)
];
foreach ($daftar as $m) {
    echo $m->getNama() . ' / ' . $m->getJenis()
       . ' / Rp' . number_format($m->hitungBiaya(), 0, ',', '.') . PHP_EOL;
}

echo PHP_EOL . "--- Pengujian validasi ---" . PHP_EOL;
$uji = [
    '03 NIM kosong'        => fn() => new MahasiswaReguler('', 'Budi', 'Informatika', 3, 5000000),
    '04a Semester 0'       => fn() => new MahasiswaReguler('23003', 'Budi', 'Informatika', 0, 5000000),
    '04b Semester 15'      => fn() => new MahasiswaReguler('23003', 'Budi', 'Informatika', 15, 5000000),
    '05 UKT -100'          => fn() => new MahasiswaReguler('23003', 'Budi', 'Informatika', 3, -100),
    '06 Potongan 120%'     => fn() => new MahasiswaBeasiswa('23003', 'Budi', 'Informatika', 3, 5000000, 120),
];
foreach ($uji as $nama => $fn) {
    try { $fn(); echo "$nama => DITERIMA (tidak sesuai)" . PHP_EOL; }
    catch (InvalidArgumentException $e) { echo "$nama => Ditolak: " . $e->getMessage() . PHP_EOL; }
}

$repo = new DataMahasiswa($daftar);
echo "07 NIM duplikat => " . ($repo->tambah(new MahasiswaReguler('23001', 'Dobel', 'TI', 1, 1000)) ? 'DITERIMA' : 'Ditolak repository') . PHP_EOL;
echo "09 Edit NIM berbeda => " . ($repo->ubah('23001', new MahasiswaReguler('99999', 'Andi', 'Informatika', 4, 5000000)) ? 'DITERIMA' : 'Ditolak repository') . PHP_EOL;
$repo->ubah('23001', new MahasiswaBeasiswa('23001', 'Andi', 'Informatika', 4, 5000000, 50));
echo "08 Edit Reguler->Beasiswa => jenis " . $repo->cari('23001')->getJenis() . ", jumlah record " . count($repo->semua()) . PHP_EOL;
$repo->hapus('23002');
echo "10 Hapus satu record => sisa " . count($repo->semua()) . " record" . PHP_EOL;

echo "12 Akses \$m->nim => ";
try { echo $daftar[0]->nim; } catch (Error $e) { echo "Ditolak: " . $e->getMessage(); }
echo "</pre>";
