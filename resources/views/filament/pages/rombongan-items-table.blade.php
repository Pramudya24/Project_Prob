<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Rombongan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .content {
            padding: 20px;
        }
        
        .info-group {
            margin-bottom: 25px;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            display: block;
        }
        
        .info-value {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            width: 100%;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .col {
            flex: 1;
            min-width: 200px;
            padding: 0 10px;
            margin-bottom: 15px;
        }
        
        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-cancel {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #c0392b;
        }
        
        .btn-save {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-save:hover {
            background-color: #27ae60;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .readonly {
            background-color: #f0f0f0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Detail Rombongan
        </div>
        
        <div class="content">
            <div class="section-title">Informasi Umum</div>
            
            <div class="row">
                <div class="col">
                    <div class="info-group">
                        <label class="info-label">Tanggal</label>
                        <div class="info-value">18/11/2025</div>
                    </div>
                </div>
                <div class="col">
                    <div class="info-group">
                        <label class="info-label">Nama Pekerjaan</label>
                        <div class="info-value">Kerja Rodi</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="info-group">
                        <label class="info-label">Kode RUP</label>
                        <div class="info-value">157854</div>
                    </div>
                </div>
                <div class="col">
                    <div class="info-group">
                        <label class="info-label">Pagu RUP</label>
                        <div class="info-value">IDR 15,000.00</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="info-group">
                        <label class="info-label">Nilai Kontrak</label>
                        <div class="info-value">Rp 50.000</div>
                    </div>
                </div>
                <div class="col">
                    <div class="info-group">
                        <label class="info-label">Status</label>
                        <div class="info-value">Aktif</div>
                    </div>
                </div>
            </div>
            
            <div class="section-title">Anggota Rombongan</div>
            
            <div class="info-group">
                <label class="info-label">Daftar Anggota</label>
                <div class="info-value readonly" style="min-height: 120px; padding: 15px;">
                    <div style="margin-bottom: 8px;">1. Ahmad Fauzi - Supervisor</div>
                    <div style="margin-bottom: 8px;">2. Budi Santoso - Teknisi</div>
                    <div style="margin-bottom: 8px;">3. Citra Dewi - Administrasi</div>
                    <div style="margin-bottom: 8px;">4. Dedi Pratama - Operator</div>
                </div>
            </div>
            
            <div class="section-title">Catatan</div>
            
            <div class="info-group">
                <label class="info-label">Keterangan Tambahan</label>
                <div class="info-value readonly" style="min-height: 80px; padding: 15px;">
                    Rombongan kerja rodi untuk pemeliharaan jalan desa. Durasi pekerjaan 5 hari.
                </div>
            </div>
            
            <div class="button-group">
                <button class="btn btn-cancel">Kembali</button>
                <button class="btn btn-edit">Edit Data</button>
                <button class="btn btn-save" style="display: none;">Simpan Perubahan</button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript untuk toggle mode edit
        document.querySelector('.btn-edit').addEventListener('click', function() {
            // Sembunyikan tombol Edit, tampilkan tombol Simpan
            this.style.display = 'none';
            document.querySelector('.btn-save').style.display = 'block';
            
            // Ubah field menjadi input (kecuali yang readonly)
            const infoValues = document.querySelectorAll('.info-value:not(.readonly)');
            infoValues.forEach(function(element) {
                const currentValue = element.textContent;
                element.innerHTML = `<input type="text" value="${currentValue}" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">`;
            });
        });
        
        document.querySelector('.btn-cancel').addEventListener('click', function() {
            // Kembali ke halaman sebelumnya (dalam implementasi nyata)
            alert('Kembali ke halaman sebelumnya');
            // window.history.back(); // untuk implementasi nyata
        });
    </script>
</body>
</html>