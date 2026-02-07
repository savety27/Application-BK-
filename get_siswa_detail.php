<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak");
}

$koneksi = null;
$koneksi_paths = ['koneksi.php', '../koneksi.php', './koneksi.php'];
foreach ($koneksi_paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

if ($koneksi === null) {
    $koneksi = new mysqli("localhost", "root", "", "db_bk_skaju");
}

if (isset($_POST['siswa_id'])) {
    $siswa_id = intval($_POST['siswa_id']);
    
    try {
        $sql_siswa = "SELECT s.*, u.NAMA_LENGKAP, u.EMAIL, u.NO_TELEPON
                      FROM siswa s 
                      JOIN users u ON s.USER_ID = u.ID 
                      WHERE s.ID = ?";
        $stmt_siswa = $koneksi->prepare($sql_siswa);
        $stmt_siswa->bind_param("i", $siswa_id);
        $stmt_siswa->execute();
        $siswa = $stmt_siswa->get_result()->fetch_assoc();
        
        if ($siswa) {
            $kepribadian = null;
            $sql_kepribadian = "SELECT * FROM form_kepribadian WHERE SISWA_ID = ?";
            if ($stmt_kepribadian = $koneksi->prepare($sql_kepribadian)) {
                $stmt_kepribadian->bind_param("i", $siswa_id);
                $stmt_kepribadian->execute();
                $kepribadian = $stmt_kepribadian->get_result()->fetch_assoc();
            }
            
            $belajar = null;
            $sql_belajar = "SELECT * FROM form_belajar WHERE SISWA_ID = ?";
            if ($stmt_belajar = $koneksi->prepare($sql_belajar)) {
                $stmt_belajar->bind_param("i", $siswa_id);
                $stmt_belajar->execute();
                $belajar = $stmt_belajar->get_result()->fetch_assoc();
            }
            
            $karir = null;
            $sql_karir = "SELECT * FROM form_karir WHERE SISWA_ID = ?";
            if ($stmt_karir = $koneksi->prepare($sql_karir)) {
                $stmt_karir->bind_param("i", $siswa_id);
                $stmt_karir->execute();
                $karir = $stmt_karir->get_result()->fetch_assoc();
            }
            
            $sosial = null;
            $sql_sosial = "SELECT * FROM form_sosial WHERE SISWA_ID = ?";
            if ($stmt_sosial = $koneksi->prepare($sql_sosial)) {
                $stmt_sosial->bind_param("i", $siswa_id);
                $stmt_sosial->execute();
                $sosial = $stmt_sosial->get_result()->fetch_assoc();
            }
            ?>
            
            <style>
                .detail-container {
                    font-family: 'Poppins', sans-serif;
                    color: #2d3748;
                }
                
                .detail-section {
                    background: rgba(255, 255, 255, 0.95);
                    border-radius: 15px;
                    padding: 25px;
                    margin-bottom: 25px;
                    border: 1px solid rgba(49, 130, 206, 0.1);
                    box-shadow: 0 8px 25px rgba(49, 130, 206, 0.1);
                }
                
                .section-header {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px solid rgba(49, 130, 206, 0.1);
                }
                
                .section-header h3 {
                    color: #2d3748;
                    font-size: 22px;
                    font-weight: 700;
                    margin: 0;
                }
                
                .section-icon {
                    font-size: 24px;
                    color: #3182ce;
                }
                
                .info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                }
                
                .info-group {
                    background: rgba(49, 130, 206, 0.05);
                    padding: 18px;
                    border-radius: 12px;
                    border: 1px solid rgba(49, 130, 206, 0.1);
                }
                
                .info-label {
                    font-size: 13px;
                    color: #718096;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-bottom: 8px;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                
                .info-value {
                    font-size: 16px;
                    color: #2d3748;
                    font-weight: 600;
                    line-height: 1.5;
                }
                
                .no-data {
                    text-align: center;
                    padding: 40px 20px;
                    color: #718096;
                    font-style: italic;
                    background: rgba(49, 130, 206, 0.05);
                    border-radius: 12px;
                    border: 2px dashed rgba(49, 130, 206, 0.2);
                }

                .form-status {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 20px;
                    padding: 15px;
                    background: rgba(49, 130, 206, 0.05);
                    border-radius: 10px;
                    border-left: 4px solid #3182ce;
                }

                .status-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 16px;
                    background: white;
                    border-radius: 8px;
                    border: 1px solid rgba(49, 130, 206, 0.2);
                }

                .status-filled {
                    color: #48bb78;
                    font-weight: 600;
                }

                .status-empty {
                    color: #e53e3e;
                    font-weight: 600;
                }

                .form-section {
                    margin-bottom: 30px;
                }

                .form-title {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 20px;
                    padding: 15px;
                    background: linear-gradient(135deg, #3182ce, #2b6cb0);
                    color: white;
                    border-radius: 10px;
                    font-size: 18px;
                    font-weight: 600;
                }

                .form-title i {
                    font-size: 20px;
                }

                @keyframes fadeIn {
                    from { 
                        opacity: 0; 
                        transform: translateY(20px); 
                    }
                    to { 
                        opacity: 1; 
                        transform: translateY(0); 
                    }
                }

                .form-content {
                    animation: fadeIn 0.5s ease-out;
                }
            </style>
            
            <div class="detail-container">
                <div class="detail-section">
                    <div class="section-header">
                        <i class='bx bx-user-circle section-icon'></i>
                        <h3>Data Diri Siswa</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-id-card'></i> NAMA LENGKAP</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-hash'></i> NIS</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['NIS'] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-hash'></i> NISN</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['NISN'] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-male-female'></i> JENIS KELAMIN</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['JENIS_KELAMIN'] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-building'></i> KELAS</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['KELAS'] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-book'></i> JURUSAN</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['JURUSAN'] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-envelope'></i> EMAIL</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['EMAIL']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-phone'></i> NO TELEPON</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['NO_TELEPON'] ?? '-'); ?></div>
                        </div>
                        <?php if (!empty($siswa['ALAMAT'])): ?>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-map'></i> ALAMAT</div>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['ALAMAT']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="section-header">
                        <i class='bx bx-clipboard section-icon'></i>
                        <h3>Status Pengisian Form Assesmen</h3>
                    </div>
                    
                    <div class="form-status">
                        <div class="status-item">
                            <i class='bx bx-user'></i> Kepribadian:
                            <span class="<?php echo $kepribadian ? 'status-filled' : 'status-empty'; ?>">
                                <?php echo $kepribadian ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <i class='bx bx-book'></i> Belajar:
                            <span class="<?php echo $belajar ? 'status-filled' : 'status-empty'; ?>">
                                <?php echo $belajar ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <i class='bx bx-briefcase'></i> Karir:
                            <span class="<?php echo $karir ? 'status-filled' : 'status-empty'; ?>">
                                <?php echo $karir ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <i class='bx bx-group'></i> Sosial:
                            <span class="<?php echo $sosial ? 'status-filled' : 'status-empty'; ?>">
                                <?php echo $sosial ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="detail-section form-content">
                    <div class="form-title">
                        <i class='bx bx-user'></i>
                        Form Kepribadian
                        <span style="margin-left: auto; font-size: 14px; opacity: 0.9;">
                            <?php echo $kepribadian ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                        </span>
                    </div>
                    
                    <?php if ($kepribadian): ?>
                        <div class="info-grid">
                            <?php if (!empty($kepribadian['TIPE_KEPRIBADIAN'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-brain'></i> TIPE KEPRIBADIAN</div>
                                <div class="info-value"><?php echo htmlspecialchars($kepribadian['TIPE_KEPRIBADIAN']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($kepribadian['KELEBIHAN'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-trending-up'></i> KELEBIHAN</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($kepribadian['KELEBIHAN'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($kepribadian['KELEMAHAN'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-trending-down'></i> KELEMAHAN</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($kepribadian['KELEMAHAN'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($kepribadian['MINAT_BAKAT'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-target-lock'></i> MINAT BAKAT</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($kepribadian['MINAT_BAKAT'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($kepribadian['CATATAN_TAMBAHAN'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-note'></i> CATATAN TAMBAHAN</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($kepribadian['CATATAN_TAMBAHAN'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php 
                            $custom_fields = array_diff_key($kepribadian, array_flip(['ID', 'SISWA_ID', 'CREATED_AT', 'TANGGAL_PENGISIAN', 'TIPE_KEPRIBADIAN', 'KELEBIHAN', 'KELEMAHAN', 'MINAT_BAKAT', 'CATATAN_TAMBAHAN']));
                            foreach ($custom_fields as $key => $value):
                                if (!empty($value)):
                            ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-list-check'></i> <?php echo htmlspecialchars(str_replace('_', ' ', $key)); ?></div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($value)); ?></div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class='bx bx-clipboard' style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h4>Form Kepribadian Belum Diisi</h4>
                            <p>Siswa belum mengisi form kepribadian</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-section form-content">
                    <div class="form-title">
                        <i class='bx bx-book'></i>
                        Form Belajar
                        <span style="margin-left: auto; font-size: 14px; opacity: 0.9;">
                            <?php echo $belajar ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                        </span>
                    </div>
                    
                    <?php if ($belajar): ?>
                        <div class="info-grid">
                            <?php if (!empty($belajar['GAYA_BELAJAR'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-bar-chart'></i> GAYA BELAJAR</div>
                                <div class="info-value"><?php echo htmlspecialchars($belajar['GAYA_BELAJAR']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($belajar['WAKTU_BELAJAR_EFEKTIF'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-time'></i> WAKTU BELAJAR EFEKTIF</div>
                                <div class="info-value"><?php echo htmlspecialchars($belajar['WAKTU_BELAJAR_EFEKTIF']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($belajar['MATA_PELAJARAN_FAVORIT'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-book-open'></i> MATA PELAJARAN FAVORIT</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($belajar['MATA_PELAJARAN_FAVORIT'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($belajar['MATA_PELAJARAN_SULIT'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-book'></i> MATA PELAJARAN SULIT</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($belajar['MATA_PELAJARAN_SULIT'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($belajar['TARGET_NILAI'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-target-lock'></i> TARGET NILAI</div>
                                <div class="info-value"><?php echo htmlspecialchars($belajar['TARGET_NILAI']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($belajar['HAMBATAN_BELAJAR'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-error-circle'></i> HAMBATAN BELAJAR</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($belajar['HAMBATAN_BELAJAR'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php 
                            $custom_fields = array_diff_key($belajar, array_flip(['ID', 'SISWA_ID', 'CREATED_AT', 'TANGGAL_PENGISIAN', 'GAYA_BELAJAR', 'WAKTU_BELAJAR_EFEKTIF', 'MATA_PELAJARAN_FAVORIT', 'MATA_PELAJARAN_SULIT', 'TARGET_NILAI', 'HAMBATAN_BELAJAR']));
                            foreach ($custom_fields as $key => $value):
                                if (!empty($value)):
                            ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-list-check'></i> <?php echo htmlspecialchars(str_replace('_', ' ', $key)); ?></div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($value)); ?></div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class='bx bx-book' style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h4>Form Belajar Belum Diisi</h4>
                            <p>Siswa belum mengisi form belajar</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-section form-content">
                    <div class="form-title">
                        <i class='bx bx-briefcase'></i>
                        Form Karir
                        <span style="margin-left: auto; font-size: 14px; opacity: 0.9;">
                            <?php echo $karir ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                        </span>
                    </div>
                    
                    <?php if ($karir): ?>
                        <div class="info-grid">
                            <?php if (!empty($karir['MINAT_KARIR'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-briefcase'></i> MINAT KARIR</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($karir['MINAT_KARIR'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($karir['JURUSAN_PILIHAN'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-building'></i> JURUSAN PILIHAN</div>
                                <div class="info-value"><?php echo htmlspecialchars($karir['JURUSAN_PILIHAN']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($karir['PERGURUAN_TINGGI'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-graduation'></i> PERGURUAN TINGGI</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($karir['PERGURUAN_TINGGI'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($karir['TARGET_KARIR'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-target-lock'></i> TARGET KARIR</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($karir['TARGET_KARIR'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($karir['KETERAMPILAN_DIMILIKI'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-cog'></i> KETERAMPILAN DIMILIKI</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($karir['KETERAMPILAN_DIMILIKI'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($karir['KETERAMPILAN_DIBUTUHKAN'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-wrench'></i> KETERAMPILAN DIBUTUHKAN</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($karir['KETERAMPILAN_DIBUTUHKAN'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php 
                            $custom_fields = array_diff_key($karir, array_flip(['ID', 'SISWA_ID', 'CREATED_AT', 'TANGGAL_PENGISIAN', 'MINAT_KARIR', 'JURUSAN_PILIHAN', 'PERGURUAN_TINGGI', 'TARGET_KARIR', 'KETERAMPILAN_DIMILIKI', 'KETERAMPILAN_DIBUTUHKAN']));
                            foreach ($custom_fields as $key => $value):
                                if (!empty($value)):
                            ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-list-check'></i> <?php echo htmlspecialchars(str_replace('_', ' ', $key)); ?></div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($value)); ?></div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class='bx bx-briefcase' style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h4>Form Karir Belum Diisi</h4>
                            <p>Siswa belum mengisi form karir</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-section form-content">
                    <div class="form-title">
                        <i class='bx bx-group'></i>
                        Form Sosial
                        <span style="margin-left: auto; font-size: 14px; opacity: 0.9;">
                            <?php echo $sosial ? '✓ Sudah Diisi' : '✗ Belum Diisi'; ?>
                        </span>
                    </div>
                    
                    <?php if ($sosial): ?>
                        <div class="info-grid">
                            <?php if (!empty($sosial['HUBUNGAN_KELUARGA'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-group'></i> HUBUNGAN KELUARGA</div>
                                <div class="info-value"><?php echo htmlspecialchars($sosial['HUBUNGAN_KELUARGA']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($sosial['HUBUNGAN_TEMAN'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-user-voice'></i> HUBUNGAN TEMAN</div>
                                <div class="info-value"><?php echo htmlspecialchars($sosial['HUBUNGAN_TEMAN']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($sosial['KEMAMPUAN_KOMUNIKASI'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-chat'></i> KEMAMPUAN KOMUNIKASI</div>
                                <div class="info-value"><?php echo htmlspecialchars($sosial['KEMAMPUAN_KOMUNIKASI']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($sosial['AKTIVITAS_SOSIAL'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-run'></i> AKTIVITAS SOSIAL</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($sosial['AKTIVITAS_SOSIAL'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($sosial['MASALAH_SOSIAL'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-note'></i> MASALAH SOSIAL</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($sosial['MASALAH_SOSIAL'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($sosial['UPAYA_MENGATASI'])): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-bulb'></i> UPAYA MENGATASI</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($sosial['UPAYA_MENGATASI'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php 
                            $custom_fields = array_diff_key($sosial, array_flip(['ID', 'SISWA_ID', 'CREATED_AT', 'TANGGAL_PENGISIAN', 'HUBUNGAN_KELUARGA', 'HUBUNGAN_TEMAN', 'KEMAMPUAN_KOMUNIKASI', 'AKTIVITAS_SOSIAL', 'MASALAH_SOSIAL', 'UPAYA_MENGATASI']));
                            foreach ($custom_fields as $key => $value):
                                if (!empty($value)):
                            ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-list-check'></i> <?php echo htmlspecialchars(str_replace('_', ' ', $key)); ?></div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($value)); ?></div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class='bx bx-group' style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h4>Form Sosial Belum Diisi</h4>
                            <p>Siswa belum mengisi form sosial</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
        } else {
            echo '<div class="no-data">❌ Data siswa tidak ditemukan</div>';
        }
    } catch (Exception $e) {
        echo '<div class="no-data">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="no-data">❌ ID siswa tidak valid</div>';
}
?>