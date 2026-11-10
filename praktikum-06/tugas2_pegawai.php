<?php
declare(strict_types=1);

// Parent Class
abstract class Pegawai {
    // kode program selanjutnya
    public function __construct(
        private string $id,
        private string $nama
    ) {
        if (trim($id) === '' || trim($nama) === '') {
            throw new InvalidArgumentException('ID dan nama wajib diisi');
        }
    }

    public function getId(): string {
        return $this->id;
    }

    public function getNama(): string {
        return $this->nama;
    }

    abstract public function hitungGaji(): int;
}

// Child Class Pegawai Tetap
class PegawaiTetap extends Pegawai {
    public function __construct(
        string $id,
        string $nama,
        private int $gajiPokok
    ) {
        parent::__construct($id, $nama);

        if ($gajiPokok < 0) {
            throw new InvalidArgumentException('Gaji pokok tidak boleh negatif');
        }
    }

    public function hitungGaji(): int {
        return $this->gajiPokok;
    }
}

// Child Class Pegawai Kontrak
class PegawaiKontrak extends Pegawai {
    public function __construct(
        string $id,
        string $nama,
        private int $upahPerJam,
        private int $jamKerja
    ) {
        parent::__construct($id, $nama);

        if ($upahPerJam < 0 || $jamKerja < 0) {
            throw new InvalidArgumentException(
                'Upah per jam dan jam kerja tidak boleh negatif'
            );
        }
    }

    public function hitungGaji(): int {
        return $this->upahPerJam * $this->jamKerja;
    }
}

// Membuat dua object
$daftar = [
    new PegawaiTetap('P001', 'Rina', 6000000),
    new PegawaiKontrak('K001', 'Dedi', 40000, 120)
];

// Menampilkan hasil
echo "<pre>";

foreach ($daftar as $p) {
    echo $p->getId() . ' / '
       . $p->getNama() . ' / '
       . get_class($p) . ' / Gaji: Rp'
       . number_format($p->hitungGaji(), 0, ',', '.')
       . PHP_EOL;
}

echo "</pre>";
?>
