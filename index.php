<?php
require_once 'config/db.php';

$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'mahasiswa'"))['total'];
$total_mesin     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM barang"))['total'];
$total_peminjaman = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman"))['total'];
$peminjaman_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE MONTH(tgl_pinjam) = MONTH(NOW()) AND YEAR(tgl_pinjam) = YEAR(NOW())"))['total']; ?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Peminjaman Lab Mesin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #f8f8fc;
            --bg-card: #efeef6;
            --bg-card-hover: #e4e2f0;
            --accent: #6c63ff;
            --accent-light: #4f46e5;
            --accent-glow: rgba(108, 99, 255, 0.10);
            --text-primary: #1a1a2e;
            --text-secondary: #5c5a72;
            --text-muted: #8b88a0;
            --border: rgba(20, 20, 40, 0.08);
            --border-accent: rgba(108, 99, 255, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ─── NAV ─────────────────────────────── */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 5%;
            background: rgba(248, 248, 252, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo-icon {
            width: 36px;
            height: 36px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-logo-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
        }

        .nav-logo-text {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .nav-logo-text span {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: var(--text-secondary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--text-primary);
        }

        .nav-cta {
            background: var(--accent);
            color: white !important;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s, transform 0.15s !important;
        }

        .nav-cta:hover {
            background: var(--accent-light) !important;
            color: white !important;
            transform: translateY(-1px);
        }

        /* ─── HERO ────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 6rem 5% 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(108, 99, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(108, 99, 255, 0.04) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .hero-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(108, 99, 255, 0.12) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 780px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--accent-glow);
            border: 1px solid var(--border-accent);
            border-radius: 50px;
            padding: 5px 14px;
            font-size: 12px;
            color: var(--accent-light);
            font-weight: 500;
            margin-bottom: 1.5rem;
            letter-spacing: 0.3px;
        }

        .hero-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--accent-light);
            border-radius: 50%;
        }

        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin-bottom: 1.2rem;
        }

        .hero h1 .highlight {
            color: var(--accent-light);
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 560px;
            margin: 0 auto 2.5rem;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            padding: 13px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-primary:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .btn-primary svg {
            width: 18px;
            height: 18px;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            color: var(--text-primary);
            text-decoration: none;
            padding: 13px 28px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 15px;
            border: 1px solid var(--border);
            transition: border-color 0.2s, background 0.2s;
        }

        .btn-secondary:hover {
            border-color: var(--border-accent);
            background: var(--accent-glow);
        }

        .hero-stats {
            display: flex;
            gap: 2.5rem;
            justify-content: center;
            margin-top: 3.5rem;
            flex-wrap: wrap;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-num {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent-light);
        }

        .hero-stat-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .hero-stat-divider {
            width: 1px;
            background: var(--border);
            align-self: stretch;
        }

        /* ─── SECTIONS ────────────────────────── */
        section {
            padding: 5rem 5%;
        }

        .section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--accent-light);
            margin-bottom: 0.8rem;
        }

        .section-title {
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 0.8rem;
        }

        .section-desc {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 520px;
            line-height: 1.7;
        }

        .section-header {
            margin-bottom: 3rem;
        }

        /* ─── FEATURES ────────────────────────── */
        #fitur {
            background: var(--bg-card);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.75rem;
            transition: border-color 0.25s, background 0.25s, transform 0.2s;
        }

        .feature-card:hover {
            border-color: var(--border-accent);
            background: var(--bg-card-hover);
            transform: translateY(-3px);
        }

        .feature-icon {
            width: 46px;
            height: 46px;
            background: var(--accent-glow);
            border: 1px solid var(--border-accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
        }

        .feature-icon svg {
            width: 22px;
            height: 22px;
            stroke: var(--accent-light);
            fill: none;
            stroke-width: 1.8;
        }

        .feature-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.65;
        }

        /* ─── GALERI ──────────────────────────── */
        #galeri {
            background: var(--bg-dark);
            margin-top: 2rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.25rem;
        }

        .gallery-item {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border);
            aspect-ratio: 4 / 5;
            box-shadow: 0 1px 3px rgba(20, 20, 40, 0.06);
            transition: border-color 0.25s, transform 0.2s, box-shadow 0.25s;
        }

        .gallery-item:hover {
            border-color: var(--border-accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 99, 255, 0.12);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 70%;
            display: block;
            transition: transform 0.4s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-caption {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 12px 14px;
            background: linear-gradient(to top, rgba(10, 10, 15, 0.9), transparent);
            font-size: 13px;
            font-weight: 500;
            color: #ffffff;
        }

        /* ─── HOW IT WORKS ────────────────────── */
        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0;
            position: relative;
        }

        .steps-container::before {
            content: '';
            position: absolute;
            top: 28px;
            left: 10%;
            right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-accent), transparent);
        }

        .step {
            text-align: center;
            padding: 0 1rem;
            position: relative;
        }

        .step-num {
            width: 56px;
            height: 56px;
            background: var(--bg-card);
            border: 1px solid var(--border-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--accent-light);
            margin: 0 auto 1.2rem;
            position: relative;
            z-index: 1;
        }

        .step h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .step-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .step-badge.mahasiswa {
            background: rgba(108, 99, 255, 0.15);
            color: var(--accent-light);
        }

        .step-badge.admin {
            background: rgba(29, 158, 117, 0.15);
            color: #178a64;
        }

        /* ─── STATS ───────────────────────────── */
        #statistik {
            background: var(--bg-card);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.75rem;
            text-align: center;
            transition: border-color 0.2s;
        }

        .stat-card:hover {
            border-color: var(--border-accent);
        }

        .stat-card-icon {
            width: 40px;
            height: 40px;
            background: var(--accent-glow);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .stat-card-icon svg {
            width: 20px;
            height: 20px;
            stroke: var(--accent-light);
            fill: none;
            stroke-width: 1.8;
        }

        .stat-card-num {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-card-label {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* ─── CTA ─────────────────────────────── */
        .cta-section {
            text-align: center;
            padding: 5rem 5%;
        }

        .cta-box {
            max-width: 680px;
            margin: 0 auto;
            background: var(--bg-card);
            border: 1px solid var(--border-accent);
            border-radius: 20px;
            padding: 3.5rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .cta-box::before {
            content: '';
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            width: 400px;
            height: 200px;
            background: radial-gradient(ellipse, rgba(108, 99, 255, 0.12), transparent 70%);
        }

        .cta-box h2 {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 700;
            margin-bottom: 0.8rem;
            position: relative;
        }

        .cta-box p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            position: relative;
        }

        .cta-box .hero-actions {
            position: relative;
        }

        /* ─── FOOTER ──────────────────────────── */
        footer {
            border-top: 1px solid var(--border);
            padding: 2rem 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo-icon {
            width: 30px;
            height: 30px;
            background: var(--accent);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-logo-icon svg {
            width: 16px;
            height: 16px;
            fill: white;
        }

        .footer-left span {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .footer-right {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* ─── SCROLL ANIMATION ────────────────── */
        .fade-in {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ─── MOBILE ──────────────────────────── */
        @media (max-width: 1100px) {
            .gallery-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 700px) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }

        .steps-container::before {
            display: none;
        }

        footer {
            text-align: center;
            justify-content: center;
        }
        }
    </style>
</head>

<body>

    <!-- NAV -->
    <nav>
        <a href="#" class="nav-logo">
            <div class="nav-logo-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8v8l-8 4-8-4V8l8-3.82z" />
                </svg>
            </div>
            <div class="nav-logo-text">
                Lab Mesin
                <span>Sistem Peminjaman</span>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="#fitur">Fitur</a></li>
            <li><a href="#galeri">Galeri</a></li>
            <li><a href="#cara-pakai">Cara Pakai</a></li>
            <li><a href="#statistik">Statistik</a></li>
            <li><a href="daftar.php" style="color: #0f172a; background: white; padding: 8px 20px; border-radius: 8px; font-weight: 500;">Daftar</a></li>
            <li><a href="login.php" class="nav-cta">Masuk</a></li>
        </ul>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-grid-bg"></div>
        <div class="hero-glow"></div>
        <div class="hero-content">
            <div class="hero-badge">Laboratorium Mesin Universitas Pancasakti Tegal</div>
            <h1>Sistem Peminjaman Alat<br>Laboratorium Teknik Mesin <span class="highlight">Universitas Pancasakti Tegal</span></h1>
            <p>Ajukan peminjaman mesin lab kapan saja, pantau status persetujuan secara langsung, dan kelola pengembalian dengan mudah.</p>
            <div class="hero-actions">
                <a href="mahasiswa/dashboard.php" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z" />
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                    </svg>
                    Mulai Pinjam Sekarang
                </a>
                <a href="login.php" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                    </svg>
                    Masuk ke Sistem
                </a>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="fitur">
        <div class="section-header fade-in">
            <div class="section-label">Fitur Unggulan</div>
            <h2 class="section-title">Semua yang Anda butuhkan<br>ada di sini</h2>
            <p class="section-desc">Dirancang khusus untuk mempermudah proses administrasi peminjaman mesin di laboratorium.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3>Pengajuan Online</h3>
                <p>Mahasiswa dapat mengajukan peminjaman mesin kapan saja dan dari mana saja melalui sistem online tanpa perlu datang langsung.</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3>Persetujuan Cepat</h3>
                <p>Admin dan teknisi dapat mereview dan menyetujui permintaan peminjaman secara langsung melalui dashboard khusus.</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <h3>Notifikasi Otomatis</h3>
                <p>Pengguna mendapatkan notifikasi langsung ketika status pengajuan berubah, termasuk pengingat pengembalian mesin.</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3>Laporan & Statistik</h3>
                <p>Dashboard analitik lengkap memudahkan admin memantau frekuensi penggunaan, riwayat peminjaman, dan status inventaris mesin.</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3>Riwayat Lengkap</h3>
                <p>Semua data peminjaman tersimpan dengan rapi. Mahasiswa dan admin dapat mengakses riwayat lengkap peminjaman kapan saja.</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                </div>
                <h3>Inventaris Mesin</h3>
                <p>Pengelolaan data inventaris mesin laboratorium secara terpusat, termasuk status ketersediaan dan kondisi setiap alat.</p>
            </div>
        </div>


        <!-- GALERI -->
        <section id="galeri">
            <div class="section-header fade-i
        n">
                <div class="section-label">Galeri</div>
                <h2 class="section-title">Galeri Laboratorium<br>Teknik Mesin
                </h2>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item fade-in">
                    <img src="assets/img/galeri/workshop-cnc-router.jpg" alt="Workshop Kreatifitas Mahasiswa" loading="lazy">
                    <div class="gallery-caption">CNC Router & Mesin Bor</div>
                </div>
                <div class="gallery-item fade-in">
                    <img src="assets/img/galeri/mesin-milling-cnc.jpg" alt="Mesin Milling CNC" loading="lazy">
                    <div class="gallery-caption">Mesin Milling CNC</div>
                </div>
                <div class="gallery-item fade-in">
                    <img src="assets/img/galeri/mesin-bubut-cnc.jpg" alt="Mesin Bubut CNC" loading="lazy">
                    <div class="gallery-caption">Mesin Bubut CNC</div>
                </div>
                <div class="gallery-item fade-in">
                    <img src="assets/img/galeri/mesin-machining-center.jpg" alt="Mesin Machining Center" loading="lazy">
                    <div class="gallery-caption">Mesin Machining Center</div>
                </div>
                <div class="gallery-item fade-in">
                    <img src="assets/img/galeri/trainer-panel-surya.jpg" alt="Trainer Panel Surya" loading="lazy">
                    <div class="gallery-caption">Trainer Panel Surya</div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section id="cara-pakai">
            <div class="section-header fade-in">
                <div class="section-label">Cara Penggunaan</div>
                <h2 class="section-title">Proses Peminjaman<br>dalam 4 Langkah</h2>
                <p class="section-desc">Alur yang sederhana dan transparan dari pengajuan hingga pengembalian mesin.</p>
            </div>
            <div class="steps-container">
                <div class="step fade-in">
                    <div class="step-num">01</div>
                    <div class="step-badge mahasiswa">Mahasiswa</div>
                    <h3>Login & Pilih Mesin</h3>
                    <p>Masuk ke akun dan pilih mesin yang tersedia sesuai kebutuhan praktikum atau penelitian.</p>
                </div>
                <div class="step fade-in">
                    <div class="step-num">02</div>
                    <div class="step-badge mahasiswa">Mahasiswa</div>
                    <h3>Isi Formulir Pengajuan</h3>
                    <p>Lengkapi formulir peminjaman dengan tanggal, waktu, dan keperluan penggunaan mesin.</p>
                </div>
                <div class="step fade-in">
                    <div class="step-num">03</div>
                    <div class="step-badge admin">Admin</div>
                    <h3>Verifikasi & Persetujuan</h3>
                    <p>Admin mereview pengajuan dan memberikan persetujuan atau penolakan beserta alasannya.</p>
                </div>
                <div class="step fade-in">
                    <div class="step-num">04</div>
                    <div class="step-badge mahasiswa">Mahasiswa</div>
                    <h3>Gunakan & Kembalikan</h3>
                    <p>Gunakan mesin sesuai jadwal yang disetujui dan laporkan pengembalian melalui sistem.</p>
                </div>
            </div>
        </section>

        <!-- STATS -->
        <section id="statistik">
            <div class="section-header fade-in">
                <div class="section-label">Statistik Sistem</div>
                <h2 class="section-title">Mendukung Kegiatan<br>Laboratorium Secara Efisien</h2>
                <p class="section-desc">Sistem ini dirancang untuk mengelola kebutuhan peminjaman mesin laboratorium secara menyeluruh.</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <div class="stat-card-num"><?php
                                                echo $total_mahasiswa;
                                                ?></div>
                    <div class="stat-card-label">Total Mahasiswa Terdaftar</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                            <line x1="8" y1="21" x2="16" y2="21" />
                            <line x1="12" y1="17" x2="12" y2="21" />
                        </svg>
                    </div>
                    <div class="stat-card-num"><?php
                                                echo $total_mesin;
                                                ?></div>
                    <div class="stat-card-label">Total Mesin Tersedia</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <polyline points="10 9 9 9 8 9" />
                        </svg>
                    </div>
                    <div class="stat-card-num"><?php
                                                echo $total_peminjaman;
                                                ?></div>
                    <div class="stat-card-label">Total Peminjaman</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                            <polyline points="17 6 23 6 23 12" />
                        </svg>
                    </div>
                    <div class="stat-card-num"><?php
                                                echo $peminjaman_aktif;
                                                ?></div>
                    <div class="stat-card-label">Peminjaman Aktif Bulan Ini</div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <div class="cta-section">
            <div class="cta-box fade-in">
                <h2>Siap Memulai?</h2>
                <p>Masuk ke sistem atau daftar sebagai pengguna baru untuk mengakses semua fitur peminjaman lab mesin.</p>
                <div class="hero-actions">
                    <a href="login.php" class="btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" y1="12" x2="3" y2="12" />
                        </svg>
                        Masuk ke Sistem
                    </a>
                    <a href="daftar.php" class="btn-secondary">Daftar Akun Baru</a>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <footer>
            <div class="footer-left">
                <div class="footer-logo-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8v8l-8 4-8-4V8l8-3.82z" />
                    </svg>
                </div>
                <span>Sistem Peminjaman Lab Mesin &copy; <?php echo date('Y'); ?></span>
            </div>
            <div class="footer-right">Dibangun dengan PHP Native</div>
        </footer>

        <script>
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, i) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, 80 * (entry.target.dataset.delay || 0));
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12
            });

            document.querySelectorAll('.fade-in').forEach((el, i) => {
                el.dataset.delay = i % 4;
                observer.observe(el);
            });
        </script>

</body>

</html>