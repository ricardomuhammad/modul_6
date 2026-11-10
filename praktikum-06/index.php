<?php
// Kelas dimuat SEBELUM session_start agar objek di session bisa di-unserialize
require_once __DIR__ . '/DataMahasiswa.php';
session_start();

function aman(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$repo  = new DataMahasiswa($_SESSION['mahasiswa'] ?? []);
$error = '';
$info  = $_SESSION['info'] ?? '';
unset($_SESSION['info']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new InvalidArgumentException('Token tidak valid, muat ulang halaman');
        }
        $aksi = (string)($_POST['aksi'] ?? 'tambah');

        if ($aksi === 'hapus') {
            $nim = trim((string)($_POST['nim'] ?? ''));
            if (!$repo->hapus($nim)) throw new InvalidArgumentException('Data tidak ditemukan');
            $_SESSION['mahasiswa'] = $repo->semua();
            $_SESSION['info'] = 'Data berhasil dihapus';
            header('Location: index.php'); exit;
        }

        $nim      = trim((string)($_POST['nim'] ?? ''));
        $nama     = trim((string)($_POST['nama'] ?? ''));
        $prodi    = trim((string)($_POST['prodi'] ?? ''));
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT);
        $ukt      = filter_var($_POST['ukt'] ?? null, FILTER_VALIDATE_INT);
        if ($semester === false || $ukt === false) {
            throw new InvalidArgumentException('Semester dan UKT harus bilangan bulat');
        }

        $jenis = (string)($_POST['jenis'] ?? '');
        if ($jenis === 'reguler') {
            $m = new MahasiswaReguler($nim, $nama, $prodi, $semester, $ukt);
        } elseif ($jenis === 'beasiswa') {
            $potongan = filter_var($_POST['potongan'] ?? null, FILTER_VALIDATE_INT);
            if ($potongan === false) throw new InvalidArgumentException('Potongan harus bilangan bulat 0–100');
            $m = new MahasiswaBeasiswa($nim, $nama, $prodi, $semester, $ukt, $potongan);
        } else {
            throw new InvalidArgumentException('Jenis mahasiswa tidak dikenal');
        }

        $ok = $aksi === 'ubah'
            ? $repo->ubah((string)($_POST['nim_lama'] ?? ''), $m)
            : $repo->tambah($m);
        if (!$ok) throw new InvalidArgumentException('NIM duplikat atau data edit tidak cocok');

        $_SESSION['mahasiswa'] = $repo->semua();
        $_SESSION['info'] = $aksi === 'ubah' ? 'Data berhasil diubah' : 'Data berhasil ditambahkan';
        header('Location: index.php'); exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

// Nilai awal form: dari POST (jika gagal), dari data edit, atau kosong
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $repo->cari((string)$_GET['edit']);
}
$mode = ($edit !== null || (($_POST['aksi'] ?? '') === 'ubah')) ? 'ubah' : 'tambah';

$f = ['nim'=>'', 'nama'=>'', 'prodi'=>'', 'semester'=>'', 'ukt'=>'', 'jenis'=>'reguler', 'potongan'=>'0'];
if ($edit !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $f = [
        'nim' => $edit->getNim(), 'nama' => $edit->getNama(), 'prodi' => $edit->getProdi(),
        'semester' => $edit->getSemester(), 'ukt' => $edit->getUkt(),
        'jenis' => $edit instanceof MahasiswaBeasiswa ? 'beasiswa' : 'reguler',
        'potongan' => $edit instanceof MahasiswaBeasiswa ? $edit->getPotonganPersen() : 0,
    ];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) $f[$k] = (string)($_POST[$k] ?? $f[$k]);
}
$nimLama = $mode === 'ubah' ? ($edit?->getNim() ?? (string)($_POST['nim_lama'] ?? '')) : '';

$q = trim((string)($_GET['q'] ?? ''));
$daftar = $repo->cariKata($q);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRUD Mahasiswa — Inheritance</title>
<style>
  body{font-family:Arial,sans-serif;max-width:980px;margin:24px auto;padding:0 12px;color:#222}
  h1{font-size:22px} h2{font-size:17px;margin-top:28px}
  form.box{background:#f5f7fa;border:1px solid #d9dee5;border-radius:8px;padding:14px}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  label{font-size:13px;display:block;margin-bottom:3px}
  input,select{width:100%;padding:7px;box-sizing:border-box;border:1px solid #bbb;border-radius:5px}
  input[readonly]{background:#e8e8e8}
  button,.btn{padding:7px 14px;border:0;border-radius:5px;background:#1f6feb;color:#fff;cursor:pointer;text-decoration:none;font-size:13px}
  .btn2{background:#6b7280}.btn-del{background:#d1242f}
  table{width:100%;border-collapse:collapse;font-size:14px}
  th,td{border:1px solid #d9dee5;padding:7px;text-align:left}
  th{background:#eef1f5}
  .err{background:#ffe5e5;border:1px solid #f5b5b5;padding:8px;border-radius:5px;margin-bottom:10px}
  .ok{background:#e3f6e8;border:1px solid #a9dcb7;padding:8px;border-radius:5px;margin-bottom:10px}
  .aksi{display:flex;gap:6px} .aksi form{margin:0}
</style>
</head>
<body>
<h1>CRUD Data Mahasiswa (Reguler &amp; Beasiswa)</h1>

<?php if ($error): ?><div class="err"><?= aman($error) ?></div><?php endif; ?>
<?php if ($info): ?><div class="ok"><?= aman($info) ?></div><?php endif; ?>

<h2><?= $mode === 'ubah' ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' ?></h2>
<form class="box" method="post" action="index.php">
  <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
  <input type="hidden" name="aksi" value="<?= $mode ?>">
  <?php if ($mode === 'ubah'): ?>
    <input type="hidden" name="nim_lama" value="<?= aman($nimLama) ?>">
  <?php endif; ?>
  <div class="grid">
    <div><label>NIM</label>
      <input name="nim" value="<?= aman($mode === 'ubah' ? $nimLama : $f['nim']) ?>" <?= $mode === 'ubah' ? 'readonly' : '' ?>></div>
    <div><label>Nama</label><input name="nama" value="<?= aman($f['nama']) ?>"></div>
    <div><label>Prodi</label><input name="prodi" value="<?= aman($f['prodi']) ?>"></div>
    <div><label>Semester (1–14)</label><input name="semester" type="number" value="<?= aman($f['semester']) ?>"></div>
    <div><label>Jenis Mahasiswa</label>
      <select name="jenis" id="jenis" onchange="toggle()">
        <option value="reguler"  <?= $f['jenis']==='reguler'  ? 'selected' : '' ?>>Reguler</option>
        <option value="beasiswa" <?= $f['jenis']==='beasiswa' ? 'selected' : '' ?>>Beasiswa</option>
      </select></div>
    <div><label>UKT (Rp, ≥ 0)</label><input name="ukt" type="number" value="<?= aman($f['ukt']) ?>"></div>
    <div id="wrapPotongan"><label>Potongan Beasiswa (0–100 %)</label>
      <input name="potongan" type="number" value="<?= aman($f['potongan']) ?>"></div>
  </div>
  <p style="margin-bottom:0">
    <button type="submit"><?= $mode === 'ubah' ? 'Simpan Perubahan' : 'Tambah' ?></button>
    <?php if ($mode === 'ubah'): ?><a class="btn btn2" href="index.php">Batal</a><?php endif; ?>
  </p>
</form>

<h2>Cari Mahasiswa</h2>
<form method="get" action="index.php" style="display:flex;gap:8px">
  <input name="q" placeholder="NIM / nama / prodi" value="<?= aman($q) ?>">
  <button type="submit" style="width:90px">Cari</button>
  <?php if ($q !== ''): ?><a class="btn btn2" href="index.php">Reset</a><?php endif; ?>
</form>

<h2>Daftar Mahasiswa (<?= count($daftar) ?>)</h2>
<table>
  <tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Smt</th><th>Jenis</th><th>UKT</th><th>Potongan</th><th>Biaya Akhir</th><th>Aksi</th></tr>
  <?php foreach ($daftar as $m): ?>
  <tr>
    <td><?= aman($m->getNim()) ?></td>
    <td><?= aman($m->getNama()) ?></td>
    <td><?= aman($m->getProdi()) ?></td>
    <td><?= aman($m->getSemester()) ?></td>
    <td><?= aman($m->getJenis()) ?></td>
    <td><?= aman(number_format($m->getUkt(), 0, ',', '.')) ?></td>
    <td><?= $m instanceof MahasiswaBeasiswa ? aman($m->getPotonganPersen()) . '%' : '-' ?></td>
    <td><?= aman(number_format($m->hitungBiaya(), 0, ',', '.')) ?></td>
    <td><div class="aksi">
      <a class="btn" href="index.php?edit=<?= urlencode($m->getNim()) ?>">Edit</a>
      <form method="post" action="index.php" onsubmit="return confirm('Hapus data ini?')">
        <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="nim" value="<?= aman($m->getNim()) ?>">
        <button class="btn-del" type="submit">Hapus</button>
      </form>
    </div></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><tr><td colspan="9">Belum ada data.</td></tr><?php endif; ?>
</table>

<script>
function toggle(){
  var b = document.getElementById('jenis').value === 'beasiswa';
  document.getElementById('wrapPotongan').style.display = b ? 'block' : 'none';
}
toggle();
</script>
</body>
</html>
