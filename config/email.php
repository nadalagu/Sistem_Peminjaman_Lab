<?php

/**
 * config/email.php
 * Email Configuration & Functions
 * Sistem notifikasi email H-1 pengembalian barang lab
 */

// ===== LOAD PHPMAILER =====
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ===== EMAIL CONFIGURATION =====
define('MAIL_FROM_ADDRESS', 'nadalagu@gmail.com');
define('MAIL_FROM_NAME',    'Sistem Peminjaman Lab Mesin');
define('MAIL_USERNAME',     'nadalagu54@gmail.com');
define('MAIL_PASSWORD',     'kqvy tibv pdpg bwoh');
define('MAIL_SUBJECT_H1',   'Pengingat: Kembalikan Barang Lab H-1');
define('LAB_ADDRESS',       'Laboratorium Mesin - Universitas Pancasakti Tegal');
define('LAB_PHONE',         '(024) xxxxxxx');
define('EMAIL_ENABLED',     true);

/**
 * Kirim Email H-1 Pengembalian menggunakan PHPMailer
 */
function kirimEmailH1($email_penerima, $nama_mahasiswa, $nama_barang, $kode_barang, $tgl_kembali)
{
    if (empty($email_penerima) || !filter_var($email_penerima, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!EMAIL_ENABLED) {
        return false;
    }

    $tgl_format = formatTanggal($tgl_kembali);
    $body       = kirimEmailH1_Template($nama_mahasiswa, $nama_barang, $kode_barang, $tgl_format);

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($email_penerima, $nama_mahasiswa);
        $mail->addReplyTo(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = MAIL_SUBJECT_H1;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Gagal kirim email ke {$email_penerima}: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML Template Email H-1
 */
function kirimEmailH1_Template($nama_mahasiswa, $nama_barang, $kode_barang, $tgl_format)
{
    $base_url    = BASE_URL;
    $fine_info   = 'Rp10.000 / hari';
    $lab_address = LAB_ADDRESS;
    $lab_phone   = LAB_PHONE;
    $tahun       = date('Y');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Pengembalian Barang</title>
</head>
<body style="margin:0; padding:0; background-color:#f0f2f5; font-family:'Segoe UI', Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f2f5; padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">

                <!-- TOP LABEL -->
                <tr>
                    <td align="center" style="padding-bottom:16px;">
                        <span style="font-size:12px; color:#888; letter-spacing:2px; text-transform:uppercase; font-weight:600;">
                            Sistem Peminjaman Lab Mesin
                        </span>
                    </td>
                </tr>

                <!-- MAIN CARD -->
                <tr>
                    <td style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                        <!-- HEADER -->
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background:linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); padding:40px 40px 36px; text-align:center;">
                                    <div style="display:inline-block; background:rgba(255,255,255,0.1); border-radius:50%; width:64px; height:64px; line-height:64px; font-size:28px; margin-bottom:16px;">
                                        &#9201;
                                    </div>
                                    <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:0.5px;">
                                        Pengingat Pengembalian Barang
                                    </h1>
                                    <p style="margin:8px 0 0; color:rgba(255,255,255,0.6); font-size:13px; letter-spacing:0.5px;">
                                        BATAS WAKTU 1 HARI LAGI
                                    </p>
                                    <!-- Divider line -->
                                    <div style="width:48px; height:3px; background:linear-gradient(90deg, #e94560, #f5a623); border-radius:2px; margin:16px auto 0;"></div>
                                </td>
                            </tr>
                        </table>

                        <!-- BODY -->
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding:36px 40px 0;">

                                    <!-- Greeting -->
                                    <p style="margin:0 0 8px; font-size:15px; color:#888;">Kepada Yth,</p>
                                    <h2 style="margin:0 0 20px; font-size:20px; color:#1a1a2e; font-weight:700;">{$nama_mahasiswa}</h2>
                                    <p style="margin:0 0 28px; font-size:14px; color:#555; line-height:1.7;">
                                        Kami menginformasikan bahwa terdapat barang laboratorium yang Anda pinjam akan jatuh tempo pengembalian pada <strong style="color:#1a1a2e;">{$tgl_format}</strong>. Mohon segera mengembalikan barang tersebut tepat waktu.
                                    </p>

                                    <!-- Deadline Banner -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                        <tr>
                                            <td style="background:linear-gradient(135deg, #fff8e1, #fff3cd); border:1px solid #ffe082; border-radius:8px; padding:16px 20px;">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td style="font-size:20px; width:36px;">&#128197;</td>
                                                        <td style="padding-left:12px;">
                                                            <div style="font-size:11px; color:#92400e; font-weight:700; letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">Batas Pengembalian</div>
                                                            <div style="font-size:18px; color:#92400e; font-weight:700;">{$tgl_format}</div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Item Detail Card -->
                                    <div style="font-size:11px; color:#888; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:12px;">Detail Barang</div>
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e8e8e8; border-radius:8px; overflow:hidden; margin-bottom:24px;">
                                        <tr>
                                            <td style="padding:14px 20px; border-bottom:1px solid #f0f0f0; background:#fafafa;">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td style="font-size:12px; color:#888; font-weight:600; width:140px;">Nama Barang</td>
                                                        <td style="font-size:13px; color:#1a1a2e; font-weight:600;">{$nama_barang}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:14px 20px; border-bottom:1px solid #f0f0f0;">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td style="font-size:12px; color:#888; font-weight:600; width:140px;">Kode Barang</td>
                                                        <td style="font-size:13px; color:#1a1a2e; font-weight:600;">
                                                            <span style="background:#e8f0fe; color:#1a56db; padding:2px 10px; border-radius:20px; font-size:12px;">{$kode_barang}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:14px 20px; background:#fafafa;">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td style="font-size:12px; color:#888; font-weight:600; width:140px;">Tanggal Kembali</td>
                                                        <td style="font-size:13px; color:#e94560; font-weight:700;">{$tgl_format}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Fine Warning -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                                        <tr>
                                            <td style="background:#fff5f5; border:1px solid #fed7d7; border-radius:8px; padding:16px 20px;">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td style="font-size:20px; width:36px; vertical-align:top; padding-top:2px;">&#9888;</td>
                                                        <td style="padding-left:12px;">
                                                            <div style="font-size:12px; color:#c53030; font-weight:700; letter-spacing:0.5px; margin-bottom:6px;">PERHATIAN — KETERLAMBATAN</div>
                                                            <div style="font-size:13px; color:#742a2a; line-height:1.6;">
                                                                Keterlambatan pengembalian akan dikenakan denda sebesar <strong>{$fine_info}</strong>. Akun Anda juga dapat dikunci sehingga tidak dapat melakukan peminjaman berikutnya.
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- CTA Button -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                        <tr>
                                            <td align="center">
                                                <a href="{$base_url}mahasiswa/dashboard.php"
                                                   style="display:inline-block; background:linear-gradient(135deg, #1a1a2e, #0f3460); color:#ffffff; text-decoration:none; padding:14px 36px; border-radius:6px; font-size:14px; font-weight:700; letter-spacing:0.5px;">
                                                    Lihat Status Peminjaman &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Note -->
                                    <p style="margin:0 0 32px; font-size:13px; color:#999; line-height:1.6; border-top:1px solid #f0f0f0; padding-top:24px;">
                                        Jika Anda sudah mengembalikan barang ini atau memiliki pertanyaan, silakan hubungi petugas laboratorium secara langsung. Abaikan email ini apabila sudah tidak relevan.
                                    </p>

                                </td>
                            </tr>
                        </table>

                        <!-- FOOTER -->
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background:#1a1a2e; padding:24px 40px; text-align:center;">
                                    <p style="margin:0 0 4px; color:#ffffff; font-size:14px; font-weight:700; letter-spacing:0.5px;">
                                        Laboratorium Mesin
                                    </p>
                                    <p style="margin:0 0 4px; color:rgba(255,255,255,0.5); font-size:12px;">
                                        {$lab_address}
                                    </p>
                                    <p style="margin:0 0 16px; color:rgba(255,255,255,0.5); font-size:12px;">
                                        Telepon: {$lab_phone}
                                    </p>
                                    <div style="width:32px; height:1px; background:rgba(255,255,255,0.15); margin:0 auto 16px;"></div>
                                    <p style="margin:0; color:rgba(255,255,255,0.3); font-size:11px; letter-spacing:0.5px;">
                                        &copy; {$tahun} Sistem Peminjaman Lab Mesin &mdash; Email otomatis, mohon tidak membalas.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
HTML;

    return $html;
}