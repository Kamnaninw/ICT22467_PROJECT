<?php
if (!isset($pageTitle)) $pageTitle = "Smart Warehouse RFID System";
if (!isset($activePage)) $activePage = "";
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: #eaf0f7;
      color: #17324f;
    }
    a { text-decoration: none; }

    .app {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 240px 1fr;
    }
    .sidebar {
      background: #fff;
      border-right: 1px solid #dfe7f1;
      padding: 18px 16px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 8px 4px 16px;
    }
    .logo {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(180deg, #1f4e98, #356fd0);
      color: #fff;
      font-weight: 800;
      font-size: 24px;
    }
    .brand-text strong {
      display: block;
      color: #17324f;
      font-size: 16px;
    }
    .brand-text span {
      color: #6f839b;
      font-size: 13px;
    }

    .nav {
      display: grid;
      gap: 8px;
    }
    .nav a {
      padding: 14px 14px;
      border-radius: 16px;
      color: #17324f;
      font-size: 17px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .nav a:hover,
    .nav a.active {
      background: #eef4ff;
      color: #1f4e98;
    }

    .main {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .topbar {
      background: linear-gradient(180deg, #214d93, #2e63bb);
      color: #fff;
      padding: 18px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 18px;
      flex-wrap: wrap;
    }
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .mini-logo {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: rgba(255,255,255,.16);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 20px;
    }
    .topbar-right {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .topbar-right a {
      display: inline-block;
      color: #1f4e98;
      background: #fff;
      padding: 10px 14px;
      border-radius: 10px;
      font-weight: 700;
    }

    .content {
      padding: 28px;
    }
    .page-title {
      font-size: 46px;
      color: #1f4e98;
      margin: 0 0 8px;
      font-weight: 800;
    }
    .page-sub {
      color: #6f839b;
      line-height: 1.8;
      margin-bottom: 22px;
    }

    .card {
      background: #fff;
      border: 1px solid #dfe7f1;
      border-radius: 22px;
      box-shadow: 0 10px 24px rgba(25,52,77,.05);
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0,1fr));
      gap: 14px;
      margin-bottom: 24px;
    }
    .summary-card {
      padding: 18px;
    }
    .label {
      font-size: 14px;
      color: #6f839b;
      font-weight: 700;
    }
    .value {
      font-size: 46px;
      color: #1f4e98;
      font-weight: 800;
      margin-top: 8px;
    }
    .note {
      margin-top: 8px;
      font-size: 13px;
      color: #6f839b;
      font-weight: 700;
      line-height: 1.6;
    }

    .table-wrap { overflow: auto; }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }
    th {
      text-align: left;
      background: #eef4fb;
      color: #2b4e7c;
      font-size: 14px;
      font-weight: 800;
      padding: 14px;
      border-bottom: 1px solid #dfe7f1;
    }
    td {
      padding: 14px;
      border-bottom: 1px solid #edf2f7;
      vertical-align: middle;
    }
    tr:last-child td { border-bottom: 0; }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
      white-space: nowrap;
    }
    .ok { background: #eef4ff; color: #1f4e98; }
    .warn { background: #fff4e5; color: #cf7f00; }
    .danger { background: #fff1f1; color: #d94f4f; }

    .btn-light {
      display: inline-block;
      background: #fff;
      border: 1px solid #d6e1ed;
      color: #1f4e98;
      padding: 12px 16px;
      border-radius: 12px;
      font-weight: 700;
    }
    .btn-primary {
      display: inline-block;
      background: linear-gradient(180deg, #1f4e98, #356fd0);
      color: #fff;
      padding: 12px 16px;
      border-radius: 12px;
      font-weight: 700;
      border: 0;
    }

    .search-box {
      background: #f5f8fc;
      border: 1px solid #e1e9f3;
      border-radius: 999px;
      padding: 10px 14px;
      min-width: 280px;
    }
    .search-box input {
      width: 100%;
      border: 0;
      outline: 0;
      background: transparent;
      font-size: 14px;
      color: #17324f;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 700;
      color: #1f4e98;
    }

    .form-control {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #cfd8e3;
      border-radius: 12px;
      background: #fff;
      font-size: 15px;
      outline: none;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
      width: 100%;
    }

    input.form-control:focus,
    select.form-control:focus,
    textarea.form-control:focus {
      border-color: #8fb2ee;
      box-shadow: 0 0 0 4px rgba(91,149,255,.12);
    }

    .mt-20 {
      margin-top: 20px;
    }

    .alert {
      margin-bottom: 18px;
      padding: 12px 14px;
      border-radius: 12px;
      font-weight: 700;
    }
    .alert-success {
      background: #eef7ff;
      color: #1f4e98;
    }
    .alert-error {
      background: #fff3f3;
      color: #c0392b;
    }

    @media (max-width: 1100px) {
      .app { grid-template-columns: 1fr; }
      .sidebar { display: none; }
      .summary-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 640px) {
      .summary-grid { grid-template-columns: 1fr; }
      .form-grid { grid-template-columns: 1fr; }
      .content { padding: 18px; }
      .page-title { font-size: 34px; }
    }
  </style>
</head>
<body>
<div class="app">