<?php
function buatNotifikasi($user_id, $judul, $pesan, $tipe = 'info') {
    global $koneksi;
    
    $sql = "INSERT INTO notifikasi (USER_ID, JUDUL, PESAN, TIPE, DIBACA) 
            VALUES (?, ?, ?, ?, '0')";
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $judul, $pesan, $tipe);
        return $stmt->execute();
    }
    return false;
}

function dapatkanNotifikasi($user_id, $limit = 10) {
    global $koneksi;
    
    $sql = "SELECT * FROM notifikasi 
            WHERE USER_ID = ? 
            ORDER BY CREATED_AT DESC 
            LIMIT ?";
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function hitungNotifikasiBelumDibaca($user_id) {
    global $koneksi;
    
    $sql = "SELECT COUNT(*) as total FROM notifikasi 
            WHERE USER_ID = ? AND DIBACA = '0'";
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }
    return 0;
}

function tandaiDibaca($notifikasi_id) {
    global $koneksi;
    
    $sql = "UPDATE notifikasi SET DIBACA = '1' WHERE ID = ?";
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $notifikasi_id);
        return $stmt->execute();
    }
    return false;
}

function tandaiSemuaDibaca($user_id) {
    global $koneksi;
    
    $sql = "UPDATE notifikasi SET DIBACA = '1' WHERE USER_ID = ? AND DIBACA = '0'";
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    return false;
}
?>