-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 04:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_bk_skaju`
--

-- --------------------------------------------------------

--
-- Table structure for table `form_belajar`
--

CREATE TABLE `form_belajar` (
  `ID` int(11) NOT NULL,
  `SISWA_ID` int(11) DEFAULT NULL,
  `RATA_RATA_NILAI` decimal(4,2) DEFAULT NULL,
  `RANKING_KELAS` int(11) DEFAULT NULL,
  `MATA_PELAJARAN_UNGGULAN` varchar(100) DEFAULT NULL,
  `MATA_PELAJARAN_LEMAH` varchar(100) DEFAULT NULL,
  `WAKTU_BELAJAR_PERHARI` enum('<1 jam','1-2 jam','2-3 jam','>3 jam') NOT NULL,
  `TEMPAT_BELAJAR_FAVORIT` varchar(100) DEFAULT NULL,
  `METODE_BELAJAR` varchar(100) DEFAULT NULL,
  `KESULITAN_BELAJAR` text DEFAULT NULL,
  `HAMBATAN_BELAJAR` text DEFAULT NULL,
  `TARGET_NILAI` decimal(4,2) DEFAULT NULL,
  `TARGET_RANKING` int(11) DEFAULT NULL,
  `CITA_CITA_AKADEMIK` varchar(100) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_belajar`
--

INSERT INTO `form_belajar` (`ID`, `SISWA_ID`, `RATA_RATA_NILAI`, `RANKING_KELAS`, `MATA_PELAJARAN_UNGGULAN`, `MATA_PELAJARAN_LEMAH`, `WAKTU_BELAJAR_PERHARI`, `TEMPAT_BELAJAR_FAVORIT`, `METODE_BELAJAR`, `KESULITAN_BELAJAR`, `HAMBATAN_BELAJAR`, `TARGET_NILAI`, `TARGET_RANKING`, `CITA_CITA_AKADEMIK`, `CREATED_AT`, `UPDATED_AT`) VALUES
(5, 8, 90.00, 1, 'Konsentrasi Keahlian', 'Sejarah', '>3 jam', 'Kamar', 'Mandiri', 'Tidak Ada ', 'Tidak Ada', 95.00, 1, 'Kuliah di Universitas Universal', '2025-11-16 11:31:31', '2025-11-16 11:31:31'),
(6, 9, 90.00, 1, 'Konsentrasi Keahlian', 'Mandarin', '>3 jam', 'Kamar', 'Kelompok ', 'Tidak Ada ', 'Tidak Ada ', 95.00, 1, 'Kuliah di Universitas Stanford', '2025-11-24 07:07:52', '2025-11-24 07:07:52'),
(7, 12, 90.00, 1, 'Pemograman Web ', 'Bahasa Mandarin ', '>3 jam', 'Kamar ', 'Mandiri ', 'Belum ada ', 'Belum ada ', 95.00, 1, 'Lulus dengan nilai terbaik ', '2026-01-07 02:57:48', '2026-01-07 02:57:48'),
(8, 14, 90.00, 1, 'Pemograman Web ', 'Bahasa Mandarin ', '>3 jam', 'Kamar ', 'Mandiri ', 'Tidak Ada ', 'Tidak Ada ', 92.00, 1, 'Kuliah di Universitas Universal ', '2026-01-12 03:42:50', '2026-01-12 03:42:50'),
(9, 15, 90.00, 1, 'Pemograman Web ', 'Bahasa Mandarin ', '>3 jam', 'Kamar ', 'Mandiri ', 'Tidak Ada ', 'Tidak Ada ', 95.00, 1, 'Kuliah di Universitas Universal ', '2026-01-12 03:58:07', '2026-01-12 03:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `form_karir`
--

CREATE TABLE `form_karir` (
  `ID` int(11) NOT NULL,
  `SISWA_ID` int(11) DEFAULT NULL,
  `MINAT_KARIR` varchar(100) NOT NULL,
  `BIDANG_KARIR` varchar(100) NOT NULL,
  `ALASAN_PEMILIHAN_KARIR` text DEFAULT NULL,
  `KETERAMPILAN_YANG_DIMILIKI` text DEFAULT NULL,
  `KURSUS_PELATIHAN` text DEFAULT NULL,
  `RENCANA_PENDIDIKAN_LANJUT` varchar(100) DEFAULT NULL,
  `DUKUNGAN_ORANG_TUA` enum('Sangat Mendukung','Mendukung','Netral','Tidak Mendukung') NOT NULL,
  `INFORMASI_KARIR_DARI` varchar(100) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_karir`
--

INSERT INTO `form_karir` (`ID`, `SISWA_ID`, `MINAT_KARIR`, `BIDANG_KARIR`, `ALASAN_PEMILIHAN_KARIR`, `KETERAMPILAN_YANG_DIMILIKI`, `KURSUS_PELATIHAN`, `RENCANA_PENDIDIKAN_LANJUT`, `DUKUNGAN_ORANG_TUA`, `INFORMASI_KARIR_DARI`, `CREATED_AT`, `UPDATED_AT`) VALUES
(13, 8, 'Progammer ', 'IT ', 'Tetarik ', 'Progamming & Public Speaking ', 'Tidak Ada ', 'Universitas Universal Batam ', 'Sangat Mendukung', 'Dari diri sendiri dan Internet ', '2025-11-16 11:32:56', '2025-11-16 11:32:56'),
(14, 9, 'Pengusaha ', 'Ekonomi/Bisnis', 'Melanjutkan tugas ortu ', 'Banyak ', 'Tidka ada / Otodidak', 'Kuliah di Universitas Stanford', 'Sangat Mendukung', 'Orang Tua', '2025-11-24 07:08:50', '2025-11-24 07:08:50'),
(15, 12, 'Progammer ', 'IT ', 'Saya senang dengan hal yang berhubungan dengan teknologi ', 'Progamming ', 'Tidak Ada ', 'Kuliah di Universitas Universal ', 'Sangat Mendukung', 'Media Sosial ', '2026-01-07 02:59:13', '2026-01-07 02:59:13'),
(16, 13, 'Dokter ', 'Kesehatan', 'Karena keluarga saya pada jadi dokter semua... ', 'Menghapal dengan cepat ', 'Les Biologi, Fisika, dan Kimia ', 'Universitas Gadjah Mada', 'Sangat Mendukung', 'Kerabat saya', '2026-01-11 12:22:07', '2026-01-11 12:22:07'),
(17, 15, 'Progammer ', 'Teknik', 'Karena dari saya kecil saya udah berminat di bidang teknologi ', 'Progamming ', 'Les Coding ', 'Kuliah di Universitas Universal ', 'Sangat Mendukung', 'Dari kerabat ', '2026-01-12 03:59:17', '2026-01-12 03:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `form_kepribadian`
--

CREATE TABLE `form_kepribadian` (
  `ID` int(11) NOT NULL,
  `SISWA_ID` int(11) DEFAULT NULL,
  `NAMA_AYAH` varchar(100) DEFAULT NULL,
  `PEKERJAAN_AYAH` varchar(50) DEFAULT NULL,
  `PENDIDIKAN_AYAH` varchar(50) DEFAULT NULL,
  `PENGHASILAN_AYAH` decimal(15,2) DEFAULT NULL,
  `NAMA_IBU` varchar(100) DEFAULT NULL,
  `PEKERJAAN_IBU` varchar(50) DEFAULT NULL,
  `PENDIDIKAN_IBU` varchar(50) DEFAULT NULL,
  `PENGHASILAN_IBU` decimal(15,2) DEFAULT NULL,
  `STATUS_RUMAH` enum('Milik Sendiri','Kontrak','Kost','Lainnya') DEFAULT NULL,
  `KENDARAAN` text DEFAULT NULL,
  `STATUS_KELUARGA` enum('Lengkap','Orang Tua Bercerai','Yatim','Piatu','Yatim Piatu') NOT NULL,
  `JUMLAH_ANGGOTA_KELUARGA` int(11) DEFAULT NULL,
  `ANAK_KE` int(11) DEFAULT NULL,
  `HUBUNGAN_DENGAN_ORTU` enum('Sangat Baik','Baik','Cukup','Kurang') NOT NULL,
  `MASALAH_KELUARGA` text DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_kepribadian`
--

INSERT INTO `form_kepribadian` (`ID`, `SISWA_ID`, `NAMA_AYAH`, `PEKERJAAN_AYAH`, `PENDIDIKAN_AYAH`, `PENGHASILAN_AYAH`, `NAMA_IBU`, `PEKERJAAN_IBU`, `PENDIDIKAN_IBU`, `PENGHASILAN_IBU`, `STATUS_RUMAH`, `KENDARAAN`, `STATUS_KELUARGA`, `JUMLAH_ANGGOTA_KELUARGA`, `ANAK_KE`, `HUBUNGAN_DENGAN_ORTU`, `MASALAH_KELUARGA`, `CREATED_AT`, `UPDATED_AT`) VALUES
(3, 8, 'Jack', 'Wiraswasta', 'S1', 10000000.00, 'Melly', 'Ibu Rumah Tangga', 'SMA', 0.00, 'Milik Sendiri', 'Motor 2, Mobil 2, Sepeda 2 ', 'Lengkap', 4, 2, 'Sangat Baik', 'Tidak Ada ', '2025-11-16 11:27:04', '2025-11-16 11:27:04'),
(4, 9, 'Chael', 'Wirausaha ', 'S3', 50000000.00, 'Chynt', 'IRT', 'S2', 0.00, 'Milik Sendiri', 'Mobil 5, Motor 5, Sepeda 5 ', 'Lengkap', 5, 3, 'Sangat Baik', 'Tidak Ada ', '2025-11-24 07:06:44', '2025-11-24 07:06:44'),
(5, 12, 'Viriya ', 'Karyawan Swasta ', 'S1', 25000000.00, 'Sutina ', 'Karyawan Swasta ', 'S1', 25000000.00, 'Milik Sendiri', 'Mobil 2 \r\nMotor 2\r\nSepeda 2 \r\nSkuter 2 ', 'Lengkap', 4, 2, 'Sangat Baik', 'Tidak Ada ', '2026-01-07 02:55:59', '2026-01-07 02:55:59'),
(6, 14, 'Michael ', 'Karyawan Swasta ', 'S1', 20000000.00, 'Ani ', 'Ibu Rumah Tangga', 'S1', 10000000.00, 'Milik Sendiri', 'Motor 2, Mobil 2, Sepeda 2 ', 'Lengkap', 4, 2, 'Sangat Baik', 'Tidak Ada ', '2026-01-12 03:41:37', '2026-01-12 03:41:37'),
(7, 15, 'John ', 'Karyawan Swasta ', 'S1', 20000000.00, 'Ani ', 'Ibu Rumah Tangga ', 'D1/D2/D3', 10000000.00, 'Milik Sendiri', 'Motor 2, Mobil 2, Sepeda 2 ', 'Lengkap', 4, 2, 'Sangat Baik', 'Tidak Ada ', '2026-01-12 03:57:05', '2026-01-12 03:57:05');

-- --------------------------------------------------------

--
-- Table structure for table `form_sosial`
--

CREATE TABLE `form_sosial` (
  `ID` int(11) NOT NULL,
  `SISWA_ID` int(11) DEFAULT NULL,
  `TIPE_KEPRIBADIAN` enum('Introvert','Ekstrovert','Ambivert') NOT NULL,
  `TINGKAT_KEPERCAYAAN_DIRI` enum('Tinggi','Sedang','Rendah') NOT NULL,
  `JUMLAH_TEMAN_DEKAT` int(11) DEFAULT NULL,
  `AKTIF_ORGANISASI` enum('Ya','Tidak') NOT NULL,
  `ORGANISASI_YANG_DIIKUTI` text DEFAULT NULL,
  `PERAN_DI_ORGANISASI` varchar(100) DEFAULT NULL,
  `KEMAMPUAN_KOMUNIKASI` enum('Baik','Cukup','Kurang') NOT NULL,
  `KEMAMPUAN_KERJA_TIM` enum('Baik','Cukup','Kurang') NOT NULL,
  `MASALAH_SOSIAL` text DEFAULT NULL,
  `CARA_MENGATASI_STRES` text DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_sosial`
--

INSERT INTO `form_sosial` (`ID`, `SISWA_ID`, `TIPE_KEPRIBADIAN`, `TINGKAT_KEPERCAYAAN_DIRI`, `JUMLAH_TEMAN_DEKAT`, `AKTIF_ORGANISASI`, `ORGANISASI_YANG_DIIKUTI`, `PERAN_DI_ORGANISASI`, `KEMAMPUAN_KOMUNIKASI`, `KEMAMPUAN_KERJA_TIM`, `MASALAH_SOSIAL`, `CARA_MENGATASI_STRES`, `CREATED_AT`, `UPDATED_AT`) VALUES
(3, 8, 'Ambivert', 'Sedang', 3, 'Tidak', 'Tidak Ada ', 'Tidak Ada', 'Baik', 'Baik', 'Tidak Ada ', 'Mengistirahatkan diri', '2025-11-16 11:33:52', '2025-11-16 11:33:52'),
(4, 9, 'Ekstrovert', 'Tinggi', 100, 'Tidak', 'Tidak Ada ', 'Tidak Ada ', 'Baik', 'Baik', 'Tidak Ada ', 'Enjoy aja', '2025-11-24 07:09:35', '2025-11-24 07:09:35'),
(5, 12, 'Ekstrovert', 'Tinggi', 3, 'Tidak', 'Ekstrakulikuler Robotic ', 'Senior ', 'Baik', 'Baik', 'Tidak Ada ', 'Refreshing diri (pergi jalan, ke pantai, dll)', '2026-01-07 03:01:12', '2026-01-07 03:01:12'),
(6, 15, 'Ambivert', 'Tinggi', 3, 'Tidak', 'Tidak Ada ', 'Tidak Ada ', 'Baik', 'Baik', 'Tidak Ada ', 'Tidak Ada ', '2026-01-12 04:00:08', '2026-01-12 04:00:08');

-- --------------------------------------------------------

--
-- Table structure for table `guru_bk`
--

CREATE TABLE `guru_bk` (
  `ID` int(11) NOT NULL,
  `USER_ID` int(11) DEFAULT NULL,
  `NIP` varchar(20) NOT NULL,
  `JENIS_KELAMIN` enum('L','P') NOT NULL,
  `PENGALAMAN_MENGAJAR` varchar(100) DEFAULT NULL,
  `DESKRIPSI` text DEFAULT NULL,
  `NO_TELEPON` varchar(15) DEFAULT NULL,
  `ALAMAT` text DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru_bk`
--

INSERT INTO `guru_bk` (`ID`, `USER_ID`, `NIP`, `JENIS_KELAMIN`, `PENGALAMAN_MENGAJAR`, `DESKRIPSI`, `NO_TELEPON`, `ALAMAT`, `CREATED_AT`, `UPDATED_AT`) VALUES
(1, 11, '19651215199203100', 'P', '5 tahun', 'Guru Bimbingan dan Konseling yang berpengalaman dalam membantu siswa mengembangkan potensi diri, mengatasi masalah belajar, serta membangun karakter positif.', '081234567891', 'SMKN 7 BATAM', '2025-11-09 14:44:31', '2025-11-09 14:44:31'),
(2, 12, '198204152005052001', 'P', '5 tahun', 'Guru Bimbingan dan Konseling yang berpengalaman dalam membantu siswa mengembangkan potensi diri, mengatasi masalah belajar, serta membangun karakter positif.', '081234567894', 'Jl. Engku Putri No. 25, Batam Center, Kota Batam', '2025-11-09 14:45:42', '2025-11-09 14:45:42'),
(3, 13, '197003201995122001', 'P', '5 tahun', 'Guru Bimbingan dan Konseling yang berpengalaman dalam membantu siswa mengembangkan potensi diri, mengatasi masalah belajar, serta membangun karakter positif.', '081234567892', 'Jl. Engku Putri No. 25, Batam Center, Kota Batam', '2025-11-09 14:46:54', '2025-11-09 14:46:54'),
(4, 14, '197508102000031002', 'P', '5 tahun', 'Guru Bimbingan dan Konseling yang berpengalaman dalam membantu siswa mengembangkan potensi diri, mengatasi masalah belajar, serta membangun karakter positif.', '081234567893', 'Jl. Engku Putri No. 25, Batam Center, Kota Batam', '2025-11-09 14:48:54', '2025-11-09 14:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_konsultasi`
--

CREATE TABLE `jadwal_konsultasi` (
  `ID` int(11) NOT NULL,
  `GURU_BK_ID` int(11) DEFAULT NULL,
  `HARI` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `JAM_MULAI` time NOT NULL,
  `JAM_SELESAI` time NOT NULL,
  `KUOTA` int(11) DEFAULT 5,
  `AKTIF` enum('Ya','Tidak') DEFAULT 'Ya',
  `KETERANGAN` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_konsultasi`
--

INSERT INTO `jadwal_konsultasi` (`ID`, `GURU_BK_ID`, `HARI`, `JAM_MULAI`, `JAM_SELESAI`, `KUOTA`, `AKTIF`, `KETERANGAN`) VALUES
(38, 2, 'Rabu', '08:00:00', '15:00:00', 20, 'Ya', ''),
(39, 1, 'Jumat', '08:00:00', '10:00:00', 5, 'Ya', 'Silahkan, bagi yang berminat');

-- --------------------------------------------------------

--
-- Table structure for table `konsultasi`
--

CREATE TABLE `konsultasi` (
  `ID` int(11) NOT NULL,
  `SISWA_ID` int(11) DEFAULT NULL,
  `GURU_BK_ID` int(11) DEFAULT NULL,
  `KODE_KONSULTASI` varchar(20) NOT NULL,
  `TANGGAL_PENGAJUAN` date NOT NULL,
  `TOPIK_KONSULTASI` varchar(200) NOT NULL,
  `DESKRIPSI_MASALAH` text DEFAULT NULL,
  `PRIORITAS` enum('Rendah','Sedang','Tinggi','Darurat') DEFAULT 'Sedang',
  `PILIHAN_GURU_1` int(11) DEFAULT NULL,
  `PILIHAN_GURU_2` int(11) DEFAULT NULL,
  `STATUS` enum('Menunggu','Disetujui','Ditolak','Selesai','Dibatalkan') DEFAULT 'Menunggu',
  `MODE_KONSULTASI` enum('Offline','Online') DEFAULT 'Offline',
  `TANGGAL_DISETUJUI` date DEFAULT NULL,
  `TANGGAL_KONSULTASI` date DEFAULT NULL,
  `JAM_KONSULTASI` time DEFAULT NULL,
  `TEMPAT_KONSULTASI` varchar(100) DEFAULT 'Ruang BK SMK 7 Batam',
  `MEETING_LINK` varchar(255) DEFAULT NULL,
  `KOMENTAR_GURU` text DEFAULT NULL,
  `SARAN_GURU` text DEFAULT NULL,
  `CATATAN_KONSULTASI` text DEFAULT NULL,
  `PERLU_TINDAK_LANJUT` enum('Ya','Tidak') DEFAULT 'Tidak',
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konsultasi`
--

INSERT INTO `konsultasi` (`ID`, `SISWA_ID`, `GURU_BK_ID`, `KODE_KONSULTASI`, `TANGGAL_PENGAJUAN`, `TOPIK_KONSULTASI`, `DESKRIPSI_MASALAH`, `PRIORITAS`, `PILIHAN_GURU_1`, `PILIHAN_GURU_2`, `STATUS`, `TANGGAL_DISETUJUI`, `TANGGAL_KONSULTASI`, `JAM_KONSULTASI`, `TEMPAT_KONSULTASI`, `KOMENTAR_GURU`, `SARAN_GURU`, `CATATAN_KONSULTASI`, `PERLU_TINDAK_LANJUT`, `CREATED_AT`, `UPDATED_AT`) VALUES
(14, 8, 1, 'KONS20251116003', '2025-11-16', 'Karir', 'Saya bingung tentang karir saya di masa yang akan datang', 'Rendah', 1, 4, 'Selesai', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'Oke,Sip...', 's', 's', 'Tidak', '2025-11-16 11:34:49', '2025-12-25 14:38:42'),
(15, 8, 4, 'KONS20251117001', '2025-11-17', 'Coding', 'Saya minta saran project', 'Tinggi', 4, 2, 'Selesai', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'oke', 'Semangat', 'Semangat', 'Tidak', '2025-11-17 07:15:36', '2026-01-07 03:05:39'),
(17, 8, 1, 'KONS20251117003', '2025-11-17', 'Coding', 'Nanti...', 'Darurat', 1, 2, 'Selesai', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'Oke', '-', '-', 'Tidak', '2025-11-17 08:08:12', '2026-01-11 13:12:40'),
(18, 8, 1, 'KONS20251117004', '2025-11-17', 'Coding', 'Stress...', 'Sedang', 1, 2, 'Selesai', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'Oke', '-', '-', 'Tidak', '2025-11-17 09:38:25', '2026-01-11 13:12:47'),
(20, 8, 4, 'KONS20251124001', '2025-11-24', 'belum tau', 'nanti', 'Rendah', 4, 1, 'Selesai', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'oke deng', NULL, NULL, 'Tidak', '2025-11-24 06:25:26', '2025-11-24 06:26:47'),
(21, 9, 1, 'KONS20251124002', '2025-11-24', 'Perjalanan Karir', 'Nope', 'Sedang', 1, 2, 'Selesai', '2025-11-24', NULL, NULL, 'Ruang BK SMK 7 Batam', 'Oke, Aman jaaa', 'ok', 'ok', 'Ya', '2025-11-24 07:10:27', '2025-11-24 07:50:28'),
(22, 9, 1, 'KONS20251124003', '2025-11-24', 'My KARIR', 'Nanti Aja', 'Darurat', 1, 3, 'Selesai', '2025-11-24', '2025-11-24', '15:00:00', 'Ruang BK SMK 7 Batam', 'sipp', 'semangat slaluu', 'tidak ada', 'Tidak', '2025-11-24 07:52:29', '2025-11-24 07:54:09'),
(23, 9, NULL, 'KONS20251224001', '2025-12-24', 'teman', 'nnati aja ya', 'Darurat', 1, 2, 'Ditolak', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'masih libur sekolah nak...', NULL, NULL, 'Tidak', '2025-12-24 14:39:04', '2025-12-24 14:39:42'),
(24, 9, 2, 'KONS20251224002', '2025-12-24', 'coding', 'ntah lha', 'Rendah', 3, 2, 'Disetujui', '2025-12-24', '2025-12-24', '23:00:00', 'Ruang BK SMK 7 Batam', 'astagfirullah...libur nak', NULL, NULL, 'Tidak', '2025-12-24 15:08:29', '2025-12-24 15:08:58'),
(25, 9, NULL, 'KONS20251224003', '2025-12-24', 'teman', 'p', 'Darurat', 1, 3, 'Ditolak', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'hadeh', NULL, NULL, 'Tidak', '2025-12-24 15:12:44', '2025-12-24 15:13:08'),
(26, 9, 1, 'KONS20251225001', '2025-12-25', 'coding', 'p', 'Rendah', 1, 2, 'Selesai', '2025-12-25', '2025-12-25', '20:00:00', 'Ruang BK SMK 7 Batam', 'astagfirullah....liburr ya nakk', '-', '-', 'Tidak', '2025-12-25 12:53:16', '2026-01-11 13:12:57'),
(27, 9, NULL, 'KONS20251225002', '2025-12-25', 'teman', 'p', 'Rendah', 1, 2, 'Ditolak', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'nope', NULL, NULL, 'Tidak', '2025-12-25 13:14:18', '2025-12-25 13:14:37'),
(28, 9, 1, 'KONS20251225003', '2025-12-25', 'coding', 'test aja', 'Darurat', 1, 3, 'Selesai', '2025-12-25', '2025-12-25', '21:00:00', 'Ruang BK SMK 7 Batam', 'bentaran yak', 'd', 'd', 'Tidak', '2025-12-25 13:15:20', '2025-12-25 14:39:23'),
(29, 9, NULL, 'KONS20251225004', '2025-12-25', 'masalah keluarga', 'nanti aja', 'Sedang', 1, 3, 'Ditolak', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', '-', NULL, NULL, 'Tidak', '2025-12-25 13:35:54', '2025-12-25 13:46:21'),
(30, 9, NULL, 'KONS20251225005', '2025-12-25', 'p', 'p', 'Rendah', 1, 2, 'Ditolak', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'jangan ya', NULL, NULL, 'Tidak', '2025-12-25 13:45:07', '2025-12-25 14:29:56'),
(31, 9, 1, 'KONS20251225006', '2025-12-25', 'teman', 'y', 'Sedang', 1, 4, 'Selesai', '2025-12-25', '2025-12-26', '22:00:00', 'Ruang BK SMK 7 Batam', 'ok', '-', '-', 'Tidak', '2025-12-25 14:29:08', '2026-01-11 13:13:03'),
(32, 9, 1, 'KONS20251225007', '2025-12-25', 'y', 'x', 'Darurat', 1, 2, 'Selesai', '2025-12-25', '2025-12-26', '22:00:00', 'Ruang BK SMK 7 Batam', 'ok', '-', '-', 'Tidak', '2025-12-25 14:31:07', '2026-01-11 13:13:11'),
(33, 9, 1, 'KONS20251225008', '2025-12-25', 't', 'r', 'Sedang', 1, 2, 'Disetujui', '2025-12-25', '2025-12-26', '22:00:00', 'Ruang BK SMK 7 Batam', 'ok', '', '', 'Tidak', '2025-12-25 14:31:42', '2026-01-07 03:18:03'),
(34, 12, 1, 'KONS20260107001', '2026-01-07', 'Karir', 'Saya lagi bingung, setelah tamat sekolah jenjang karir yang sebaiknya saya ambil yang mana satu...', 'Sedang', 1, 3, 'Selesai', '2026-01-07', '2026-01-07', '09:00:00', 'Ruang BK SMK 7 Batam', 'Oke, Jangan lupa ya....', 'Banyak baca dan menggali semua informasi', 'Dah oke kokkk', 'Ya', '2026-01-07 03:03:33', '2026-01-07 03:14:38'),
(36, 9, NULL, 'KONS20260108001', '2026-01-08', 'Coding', 'y', 'Rendah', 1, 4, 'Ditolak', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', '', NULL, NULL, 'Tidak', '2026-01-08 13:43:28', '2026-01-11 12:25:01'),
(37, 13, 3, 'KONS20260111001', '2026-01-11', 'Konfilk Keluarga', 'Keluarga saya memaksa saya masuk ke Kedokteran', 'Darurat', 3, 1, 'Selesai', '2026-01-11', '2026-01-11', '20:00:00', 'Ruang BK SMK 7 Batam', 'Oke', 'Tetap semangat selalu ya...', 'Belum ada', 'Tidak', '2026-01-11 12:23:52', '2026-01-11 12:29:45'),
(38, 13, NULL, 'KONS20260111002', '2026-01-11', 'Konfilk', 'Nanti saja', 'Tinggi', 3, 1, 'Ditolak', NULL, NULL, NULL, 'Ruang BK SMK 7 Batam', 'Jangan dlu ya..', NULL, NULL, 'Tidak', '2026-01-11 13:06:25', '2026-01-11 13:11:44'),
(39, 15, 1, 'KONS20260112001', '2026-01-12', 'Masalah Coding', 'Nanti saja..', 'Sedang', 1, 3, 'Selesai', '2026-01-12', '2026-01-12', '12:00:00', 'Ruang BK SMK 7 Batam', 'Okey Boleh...', 'Tetap semangat, jika masih ad ayang dibahas silahkan konsultasi kembali', 'Membahas masalah masalah tentang dunia teknologi, terutama coding', 'Tidak', '2026-01-12 04:01:52', '2026-01-12 04:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `ID` int(11) NOT NULL,
  `USER_ID` int(11) NOT NULL,
  `JUDUL` varchar(255) NOT NULL,
  `PESAN` text NOT NULL,
  `TIPE` enum('info','success','warning','danger') DEFAULT 'info',
  `DIBACA` enum('0','1') DEFAULT '0',
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`ID`, `USER_ID`, `JUDUL`, `PESAN`, `TIPE`, `DIBACA`, `CREATED_AT`) VALUES
(1, 17, 'Konsultasi Ditolak ⚠️', 'Maaf, konsultasi Anda ditolak oleh Helmidah, S.Pd. Komentar: masih libur sekolah nak...', 'danger', '1', '2025-12-24 14:39:42'),
(2, 17, 'Konsultasi Disetujui ????', 'Konsultasi Anda telah disetujui oleh Putri, S.Pd. Tanggal konsultasi: 2025-12-24 23:00', 'success', '1', '2025-12-24 15:08:58'),
(3, 17, 'Konsultasi Ditolak ⚠️', 'Maaf, konsultasi Anda ditolak oleh Helmidah, S.Pd. Komentar: hadeh', 'danger', '1', '2025-12-24 15:13:08'),
(4, 17, 'Konsultasi Ditolak ❌', 'Konsultasi Anda dengan topik \'teman\' telah ditolak oleh Helmidah, S.Pd. Komentar: nope', 'danger', '1', '2025-12-25 13:14:37'),
(5, 17, 'Konsultasi Disetujui! ✅', 'Konsultasi Anda dengan topik \'coding\' telah disetujui oleh Helmidah, S.Pd. Tanggal: 25/12/2025 Jam: 21:00 di Ruang BK SMK 7 Batam.', 'success', '1', '2025-12-25 13:15:48'),
(6, 17, 'Konsultasi Ditolak ❌', 'Konsultasi Anda dengan topik \'masalah keluarga\' telah ditolak oleh Helmidah, S.Pd. Komentar: -', 'danger', '1', '2025-12-25 13:46:21'),
(7, 17, 'Konsultasi Disetujui! ✅', 'Konsultasi Anda dengan topik \'teman\' telah disetujui oleh Helmidah, S.Pd. Tanggal: 26/12/2025 Jam: 22:00 di Ruang BK SMK 7 Batam.', 'success', '1', '2025-12-25 14:29:44'),
(8, 17, 'Konsultasi Ditolak ❌', 'Konsultasi Anda dengan topik \'p\' telah ditolak oleh Helmidah, S.Pd. Alasan: jangan ya', 'danger', '0', '2025-12-25 14:29:56'),
(9, 17, 'Konsultasi Disetujui! ✅', 'Konsultasi Anda dengan topik \'y\' telah disetujui oleh Helmidah, S.Pd. Tanggal: 26/12/2025 Jam: 22:00 di Ruang BK SMK 7 Batam.', 'success', '0', '2025-12-25 14:31:57'),
(10, 17, 'Konsultasi Disetujui! ✅', 'Konsultasi Anda dengan topik \'t\' telah disetujui oleh Helmidah, S.Pd. Tanggal: 26/12/2025 Jam: 22:00 di Ruang BK SMK 7 Batam.', 'success', '0', '2025-12-25 14:32:02'),
(11, 23, 'Konsultasi Disetujui! ✅', 'Konsultasi Anda dengan topik \'Karir\' telah disetujui oleh Helmidah, S.Pd. Tanggal : 07/01/2026 Jam : 09:00 di Ruang BK SMK 7 Batam.', 'success', '0', '2026-01-07 03:08:54'),
(12, 17, 'Konsultasi Ditolak ❌', 'Konsultasi Anda dengan topik \'Coding\' telah ditolak oleh Sylviana Dessy, S.Pd, M.Pd. Tidak ada komentar', 'danger', '0', '2026-01-11 12:25:01'),
(13, 24, 'Konsultasi Disetujui! ✅', 'Konsultasi Anda dengan topik \'Konfilk Keluarga\' telah disetujui oleh Siti Hariani, S.pd. Tanggal : 11/01/2026 Jam : 20:00 di Ruang BK SMK 7 Batam.', 'success', '0', '2026-01-11 12:25:35'),
(14, 24, 'Konsultasi Ditolak ❌', 'Konsultasi Anda dengan topik \'Konfilk\' telah ditolak oleh Siti Hariani, S.pd. Komentar : Jangan dlu ya..', 'danger', '0', '2026-01-11 13:11:45'),
(15, 27, 'Konsultasi Disetujui! ✅', 'Konsultasi Anda dengan topik \'Masalah Coding\' telah disetujui oleh Helmidah, S.Pd. Tanggal : 12/01/2026 Jam : 12:00 di Ruang BK SMK 7 Batam.', 'success', '1', '2026-01-12 04:04:08');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `ID` int(11) NOT NULL,
  `USER_ID` int(11) NOT NULL,
  `ALASAN` text NOT NULL,
  `STATUS` enum('pending','approved','rejected') DEFAULT 'pending',
  `OTP_CODE` varchar(6) DEFAULT NULL,
  `OTP_EXPIRES` datetime DEFAULT NULL,
  `CATATAN_ADMIN` text DEFAULT NULL,
  `USED_AT` datetime DEFAULT NULL,
  `CREATED_AT` datetime DEFAULT current_timestamp(),
  `UPDATED_AT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`ID`, `USER_ID`, `ALASAN`, `STATUS`, `OTP_CODE`, `OTP_EXPIRES`, `CATATAN_ADMIN`, `USED_AT`, `CREATED_AT`, `UPDATED_AT`) VALUES
(38, 16, 'Salah Masukin Pasword', 'approved', '547243', '2025-11-16 19:43:38', NULL, '2025-11-16 18:44:40', '2025-11-16 18:29:43', '2025-11-16 18:44:40'),
(39, 17, 'Mau unik', 'approved', '153471', '2025-12-21 21:55:15', NULL, NULL, '2025-11-24 14:09:46', '2025-12-21 20:55:15'),
(41, 23, 'password ny suka lupa', 'approved', '533800', '2026-01-07 11:23:04', NULL, '2026-01-07 10:26:00', '2026-01-07 10:01:37', '2026-01-07 10:26:00'),
(42, 23, 'Test', 'rejected', NULL, NULL, 'Nope', NULL, '2026-01-07 10:28:52', '2026-01-07 11:14:37'),
(43, 17, 'Test', 'rejected', NULL, NULL, 'Terlalu sering ganti password kamu', NULL, '2026-01-07 11:19:16', '2026-01-11 19:31:53'),
(44, 23, 'Test', 'approved', '892862', '2026-01-07 12:28:28', NULL, NULL, '2026-01-07 11:19:38', '2026-01-07 11:28:28'),
(45, 24, 'Ada yang salah', 'approved', '635179', '2026-01-11 20:31:29', NULL, NULL, '2026-01-11 19:24:06', '2026-01-11 19:31:29'),
(46, 27, 'Saya mau ganti password saya supaya mudah diingat', 'approved', '417540', '2026-01-12 12:10:59', NULL, '2026-01-12 11:14:39', '2026-01-12 11:02:47', '2026-01-12 11:14:39');

-- --------------------------------------------------------

--
-- Table structure for table `review_siswa`
--

CREATE TABLE `review_siswa` (
  `ID` int(11) NOT NULL,
  `SISWA_ID` int(11) NOT NULL,
  `GURU_BK_ID` int(11) NOT NULL,
  `CATATAN_REVIEW` text NOT NULL,
  `REKOMENDASI` text NOT NULL,
  `TANGGAL_REVIEW` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_siswa`
--

INSERT INTO `review_siswa` (`ID`, `SISWA_ID`, `GURU_BK_ID`, `CATATAN_REVIEW`, `REKOMENDASI`, `TANGGAL_REVIEW`) VALUES
(3, 8, 1, 'Udah Bagus Sih...', 'Tingkat prestasi-mu ya...', '2025-11-16 11:37:19'),
(6, 15, 1, 'Sudah bagus sih, tidak ada masalah', 'Tetap semangat, usaha mu tidak akan mengkhianati hasil... \r\nBanyak banyak belajar ya, supaya nilai semakin meningkat dan mendapat banyak ilmu baru', '2026-01-12 04:09:07');

-- --------------------------------------------------------

--
-- Table structure for table `sesi_konsultasi`
--

CREATE TABLE `sesi_konsultasi` (
  `ID` int(11) NOT NULL,
  `KONSULTASI_ID` int(11) DEFAULT NULL,
  `TANGGAL_SESI` date NOT NULL,
  `JAM_MULAI` time NOT NULL,
  `JAM_SELESAI` time NOT NULL,
  `POKOK_PEMBAHASAN` text DEFAULT NULL,
  `CATATAN_SESI` text DEFAULT NULL,
  `TINDAK_LANJUT` text DEFAULT NULL,
  `REKOMENDASI` text DEFAULT NULL,
  `STATUS_SESI` enum('Terlaksana','Dibatalkan','Ditunda') DEFAULT 'Terlaksana',
  `SESI_KE` int(11) DEFAULT 1,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sesi_konsultasi`
--

INSERT INTO `sesi_konsultasi` (`ID`, `KONSULTASI_ID`, `TANGGAL_SESI`, `JAM_MULAI`, `JAM_SELESAI`, `POKOK_PEMBAHASAN`, `CATATAN_SESI`, `TINDAK_LANJUT`, `REKOMENDASI`, `STATUS_SESI`, `SESI_KE`, `CREATED_AT`) VALUES
(6, 14, '2025-11-16', '08:00:00', '09:00:00', '1. Pengembangan karir untuk kedepannya \n2. Persiapan diri', 'Siswi sudah cukup baik dalam mempersiapkan dirinya untuk masa yang akan datang', 'Tetap yakinkan diri...', 'Semangat selalu, masa depan menantimu', 'Terlaksana', 1, '2025-11-16 11:39:43'),
(7, 17, '2025-11-24', '23:00:00', '01:24:00', '', '', '', '', 'Terlaksana', 1, '2025-11-24 05:43:24'),
(8, 20, '2025-11-24', '02:00:00', '03:00:00', 'Intinya aja', 'sugoii', 'nopee', 'nopee', 'Terlaksana', 1, '2025-11-24 06:26:47'),
(9, 21, '2025-11-24', '14:47:00', '15:47:00', 'Masalah KARIR', 'Nope', 'Nope', 'Nope', 'Terlaksana', 1, '2025-11-24 07:48:37'),
(10, 22, '2025-11-24', '14:53:00', '15:53:00', 'Rintangan untuk mencapai karir', 'tidak perlu', 'tidak perlu', 'belajar dengan sungguh sungguh yaa....', 'Terlaksana', 1, '2025-11-24 07:54:08'),
(11, 34, '2026-01-07', '09:00:00', '10:00:00', 'Masalah karir karir pendukung', 'Pembahasannya cukup mendalam, dan menambah pengetahuan', 'Perlu dibahaskan lagi supaya lebih matang', 'Banyak cari cari info di Media Sosial', 'Terlaksana', 1, '2026-01-07 03:14:02'),
(12, 33, '2026-01-07', '10:17:00', '11:17:00', '', '', '', '', 'Terlaksana', 1, '2026-01-07 03:18:03'),
(13, 37, '2026-01-11', '19:26:00', '20:26:00', 'Masalah - masalah yang dihadapi', 'Dia merasa tertekan karena dipaksa oleh keluarga nya', 'Silahkan konsul kembali ke kami... guru-guru BK', 'Tenagkan diri dulu..', 'Terlaksana', 1, '2026-01-11 12:28:10'),
(14, 37, '2026-01-11', '19:28:00', '20:28:00', 'Lebih lanjut', 'Lanjutan kemarin', 'lanjutan kemarin', 'lanjutan kemarin', 'Terlaksana', 2, '2026-01-11 12:29:10'),
(15, 39, '2026-01-12', '11:04:00', '12:04:00', 'Pembahasan lanjut mengenai dunia teknologi', 'Membahas masalah masalah yang dihadapi', 'Tidak Ada', 'Banyak banyak lihat konten konten di internet untuk menambah pengetahuan', 'Terlaksana', 1, '2026-01-12 04:05:58');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `ID` int(11) NOT NULL,
  `USER_ID` int(11) DEFAULT NULL,
  `NIS` varchar(20) NOT NULL,
  `NISN` varchar(20) NOT NULL,
  `JENIS_KELAMIN` enum('L','P') NOT NULL,
  `TEMPAT_LAHIR` varchar(50) DEFAULT NULL,
  `TANGGAL_LAHIR` date DEFAULT NULL,
  `ALAMAT` text DEFAULT NULL,
  `AGAMA` varchar(20) DEFAULT NULL,
  `KELAS` varchar(10) DEFAULT NULL,
  `JURUSAN` varchar(50) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ANGKATAN` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`ID`, `USER_ID`, `NIS`, `NISN`, `JENIS_KELAMIN`, `TEMPAT_LAHIR`, `TANGGAL_LAHIR`, `ALAMAT`, `AGAMA`, `KELAS`, `JURUSAN`, `CREATED_AT`, `UPDATED_AT`, `ANGKATAN`) VALUES
(8, 16, '1342075', '0084057844', 'P', 'Batam ', '2008-08-27', 'Batam Centre ', 'Buddha', 'XI RPL 2', 'Rekayasa Perangkat Lunak', '2025-11-16 11:24:54', '2025-11-16 11:24:54', 2024),
(9, 17, '7653465434', '34568543457', 'L', 'Kalimantan ', '2008-12-08', 'Jalan Merak ', 'Buddha', 'XII RPL 2', 'Rekayasa Perangkat Lunak', '2025-11-24 07:05:26', '2025-11-24 07:05:26', 2022),
(12, 23, '65639096867', '75642', 'L', 'Batam ', '2008-06-17', 'Batam Centre ', 'Konghucu', 'XI RPL 2', 'Rekayasa Perangkat Lunak', '2026-01-07 02:48:47', '2026-01-07 02:48:47', 2023),
(13, 24, '456896', '02387486527287', 'L', 'Kalimantan ', '2008-02-21', 'Batam Centre ', 'Buddha', 'XII RPL 2', 'Rekayasa Perangkat Lunak', '2026-01-11 12:20:17', '2026-01-11 12:20:17', 2023),
(14, 25, '362673', '736275372567', 'P', 'Kalimantan ', '2008-10-07', 'Batam Centre ', 'Buddha', 'XII RPL 2', 'Rekayasa Perangkat Lunak', '2026-01-12 03:40:22', '2026-01-12 03:40:22', 2023),
(15, 27, '467367', '3478267627', 'P', 'Kalimantan ', '2008-06-10', 'Batam Centre ', 'Katolik', 'XI RPL 2', 'Rekayasa Perangkat Lunak', '2026-01-12 03:55:48', '2026-01-12 03:55:48', 2024);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `USERNAME` varchar(100) NOT NULL,
  `PASSWORD` varchar(100) NOT NULL,
  `ROLE` enum('Admin','Guru_BK','Siswa') DEFAULT NULL,
  `NAMA_LENGKAP` varchar(100) NOT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  `NO_TELEPON` varchar(15) DEFAULT NULL,
  `STATUS` enum('Aktif','Tidak_Aktif') DEFAULT 'Aktif',
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`ID`, `USERNAME`, `PASSWORD`, `ROLE`, `NAMA_LENGKAP`, `EMAIL`, `NO_TELEPON`, `STATUS`, `CREATED_AT`, `UPDATED_AT`) VALUES
(9, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Administrator', 'admin@sekolah.sch.id', '08123456789', 'Aktif', '2025-11-09 14:27:14', '2025-12-21 15:31:11'),
(11, 'helmidah', '$2y$10$keLCXfXtxVVlj.pYKF6T3OEkcqK0M.wsFaYglL4hdBU0QB37AkeGm', 'Guru_BK', 'Helmidah, S.Pd', 'helmidah@gmail.com', '0812345678912', 'Aktif', '2025-11-09 14:44:31', '2025-11-10 00:56:40'),
(12, 'putri', '$2y$10$gFiqegz2OiXi/y/utEyrduQnNqeeOhnx/NxIyK6PnMLa7QdEwT75K', 'Guru_BK', 'Putri, S.Pd', 'putri@gmail.com', '081234567894', 'Aktif', '2025-11-09 14:45:42', '2025-11-09 14:45:42'),
(13, 'siti hariani', '$2y$10$afD1CF0fteWjqOgVjrfJu.TqYKKNd4nnlWFFeX6wFbmuTXUX3XK/q', 'Guru_BK', 'Siti Hariani, S.pd', 'sitihariani@gmail.com', '081234567892', 'Aktif', '2025-11-09 14:46:54', '2025-11-09 14:46:54'),
(14, 'sylviana', '$2y$10$TIiC3lwERu8w4zRjI/aFFOw0fLerCNtvGLmqkPw9vJTDXPyMxm8A.', 'Guru_BK', 'Sylviana Dessy, S.Pd, M.Pd', 'sylviana@gmail.com', '081234567893', 'Aktif', '2025-11-09 14:48:54', '2025-11-09 14:48:54'),
(16, 'Chell', '$2y$10$GRLPlOP3bTWkVQxFqXO4nun3GaRymnmePSQIxtWFwuQeHcwABg4dS', 'Siswa', 'Michell', 'Michel0002@gmail.com', '0878 9895 4988', 'Tidak_Aktif', '2025-11-16 11:18:02', '2026-01-07 03:22:14'),
(17, 'lim', '$2y$10$PNdr9JoaOLFZF8JTr4AKsed2VgWdJIk7OvFMdHcVOPzBAAKU5CcDK', 'Siswa', 'Lim', 'limm@gmail.com', '0878 9895 4988', 'Aktif', '2025-11-24 07:04:13', '2025-12-21 14:41:06'),
(23, 'dar_renn', '$2y$10$6H./pYuY45zOT4JfCYv5xufxGCgd1B5dA8IC/LegF790Uf.64OQfq', 'Siswa', 'Darren Alfhat', 'darren@gmail.com', '087898954988', 'Aktif', '2026-01-07 02:45:14', '2026-01-07 03:26:00'),
(24, 'nicholasss', '$2y$10$IVB6Ir14w1vWGrTfPnbb5u5n.qSKZTJz93XZ7e0fIFELydh0Tclgu', 'Siswa', 'Nicholas ', 'nicholas@gmail.com', '0878 9895 4988 ', 'Aktif', '2026-01-11 12:14:39', '2026-01-11 12:33:18'),
(25, 'carolimmm', '$2y$10$5cFn85DrVqOI8zNySxygCeprmAtucx14qO2U44sPZ2ropzEyLs2Jm', 'Siswa', 'Carolim ', 'carolim@gmail.com', '0878 9895 4988', 'Tidak_Aktif', '2026-01-12 03:38:37', '2026-01-12 04:10:28'),
(26, 'cheloss', '$2y$10$5SXxTE0vZFFUvG.pu/97A.rcCGQvYOi.6b0Is.IhiEJgICRVAUR7O', 'Siswa', 'Chelos ', 'chelos@gmail.com', '0878 9895 4988', 'Aktif', '2026-01-12 03:48:29', '2026-01-12 04:10:41'),
(27, 'mikailaaazz', '$2y$10$sXgRQLJgsVi2N9bVBsZFqutWYtAbf4/zYXegcOZ4Mf1r.2J7GVMeC', 'Siswa', 'Mikaila', 'mikaila@gmail.com', '0878 9895 4988', 'Aktif', '2026-01-12 03:54:21', '2026-01-12 04:14:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `form_belajar`
--
ALTER TABLE `form_belajar`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SISWA_ID` (`SISWA_ID`);

--
-- Indexes for table `form_karir`
--
ALTER TABLE `form_karir`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SISWA_ID` (`SISWA_ID`);

--
-- Indexes for table `form_kepribadian`
--
ALTER TABLE `form_kepribadian`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SISWA_ID` (`SISWA_ID`);

--
-- Indexes for table `form_sosial`
--
ALTER TABLE `form_sosial`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SISWA_ID` (`SISWA_ID`);

--
-- Indexes for table `guru_bk`
--
ALTER TABLE `guru_bk`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `NIP` (`NIP`),
  ADD KEY `IDX_GURU_USER` (`USER_ID`);

--
-- Indexes for table `jadwal_konsultasi`
--
ALTER TABLE `jadwal_konsultasi`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IDX_JADWAL_GURU` (`GURU_BK_ID`);

--
-- Indexes for table `konsultasi`
--
ALTER TABLE `konsultasi`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `KODE_KONSULTASI` (`KODE_KONSULTASI`),
  ADD KEY `IDX_KONSULTASI_SISWA` (`SISWA_ID`),
  ADD KEY `IDX_KONSULTASI_GURU` (`GURU_BK_ID`),
  ADD KEY `IDX_KONSULTASI_STATUS` (`STATUS`),
  ADD KEY `IDX_KONSULTASI_KODE` (`KODE_KONSULTASI`),
  ADD KEY `konsultasi_ibfk_3` (`PILIHAN_GURU_1`),
  ADD KEY `konsultasi_ibfk_4` (`PILIHAN_GURU_2`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `USER_ID` (`USER_ID`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `USER_ID` (`USER_ID`);

--
-- Indexes for table `review_siswa`
--
ALTER TABLE `review_siswa`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `unique_review` (`SISWA_ID`,`GURU_BK_ID`),
  ADD KEY `GURU_BK_ID` (`GURU_BK_ID`);

--
-- Indexes for table `sesi_konsultasi`
--
ALTER TABLE `sesi_konsultasi`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `KONSULTASI_ID` (`KONSULTASI_ID`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `NIS` (`NIS`),
  ADD UNIQUE KEY `NISN` (`NISN`),
  ADD KEY `IDX_SISWA_USER` (`USER_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `USERNAME` (`USERNAME`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `form_belajar`
--
ALTER TABLE `form_belajar`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `form_karir`
--
ALTER TABLE `form_karir`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `form_kepribadian`
--
ALTER TABLE `form_kepribadian`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `form_sosial`
--
ALTER TABLE `form_sosial`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `guru_bk`
--
ALTER TABLE `guru_bk`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jadwal_konsultasi`
--
ALTER TABLE `jadwal_konsultasi`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `konsultasi`
--
ALTER TABLE `konsultasi`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `review_siswa`
--
ALTER TABLE `review_siswa`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sesi_konsultasi`
--
ALTER TABLE `sesi_konsultasi`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `form_belajar`
--
ALTER TABLE `form_belajar`
  ADD CONSTRAINT `form_belajar_ibfk_1` FOREIGN KEY (`SISWA_ID`) REFERENCES `siswa` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `form_karir`
--
ALTER TABLE `form_karir`
  ADD CONSTRAINT `form_karir_ibfk_1` FOREIGN KEY (`SISWA_ID`) REFERENCES `siswa` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `form_kepribadian`
--
ALTER TABLE `form_kepribadian`
  ADD CONSTRAINT `form_kepribadian_ibfk_1` FOREIGN KEY (`SISWA_ID`) REFERENCES `siswa` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `form_sosial`
--
ALTER TABLE `form_sosial`
  ADD CONSTRAINT `form_sosial_ibfk_1` FOREIGN KEY (`SISWA_ID`) REFERENCES `siswa` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `guru_bk`
--
ALTER TABLE `guru_bk`
  ADD CONSTRAINT `guru_bk_ibfk_1` FOREIGN KEY (`USER_ID`) REFERENCES `users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_konsultasi`
--
ALTER TABLE `jadwal_konsultasi`
  ADD CONSTRAINT `jadwal_konsultasi_ibfk_1` FOREIGN KEY (`GURU_BK_ID`) REFERENCES `guru_bk` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `konsultasi`
--
ALTER TABLE `konsultasi`
  ADD CONSTRAINT `konsultasi_ibfk_1` FOREIGN KEY (`SISWA_ID`) REFERENCES `siswa` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `konsultasi_ibfk_2` FOREIGN KEY (`GURU_BK_ID`) REFERENCES `guru_bk` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `konsultasi_ibfk_3` FOREIGN KEY (`PILIHAN_GURU_1`) REFERENCES `guru_bk` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `konsultasi_ibfk_4` FOREIGN KEY (`PILIHAN_GURU_2`) REFERENCES `guru_bk` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`USER_ID`) REFERENCES `users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `password_reset_requests_ibfk_1` FOREIGN KEY (`USER_ID`) REFERENCES `users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `review_siswa`
--
ALTER TABLE `review_siswa`
  ADD CONSTRAINT `review_siswa_ibfk_1` FOREIGN KEY (`SISWA_ID`) REFERENCES `siswa` (`ID`),
  ADD CONSTRAINT `review_siswa_ibfk_2` FOREIGN KEY (`GURU_BK_ID`) REFERENCES `guru_bk` (`ID`);

--
-- Constraints for table `sesi_konsultasi`
--
ALTER TABLE `sesi_konsultasi`
  ADD CONSTRAINT `sesi_konsultasi_ibfk_1` FOREIGN KEY (`KONSULTASI_ID`) REFERENCES `konsultasi` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`USER_ID`) REFERENCES `users` (`ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
