# Smart Warehouse RFID System

## ภาพรวมระบบ (System Overview)
Smart Warehouse RFID System เป็นระบบจัดการคลังสินค้าในรูปแบบเว็บแอปพลิเคชัน ที่พัฒนาขึ้นเพื่อช่วยให้การจัดเก็บสินค้า การติดตามตำแหน่งสินค้า และการบันทึกธุรกรรมภายในคลังมีความเป็นระบบมากขึ้น

ระบบนี้ใช้การเชื่อมโยงข้อมูลสินค้าเข้ากับ RFID Tag, Zone และ Shelf เพื่อให้สามารถตรวจสอบได้ว่าสินค้าแต่ละรายการอยู่ที่ตำแหน่งใด มีจำนวนคงเหลือเท่าใด และมีสถานะอย่างไร เช่น In-Stock, Moving และ Shipped นอกจากนี้ยังสามารถบันทึกรายการรับเข้าและเบิกออก พร้อมเก็บประวัติการทำรายการย้อนหลังได้

---

## เทคโนโลยีและเครื่องมือที่ใช้ (Tech Stack & Tools)

### Frontend
- HTML
- CSS
- JavaScript
- Chart.js

### Backend
- PHP

### Database
- MariaDB / MySQL

### Server / Environment
- XAMPP สำหรับ Windows
- Raspberry Pi + Nginx / PHP / MariaDB สำหรับการใช้งานบนอุปกรณ์จริง

### Development Tools
- Visual Studio Code
- phpMyAdmin
- Nano Editor
- Git / GitHub

### Hardware
- RFID USB Reader
- RFID Tag / RFID Key Card / RFID Keychain

---

## แนวคิดของระบบ (System Concept)
แนวคิดหลักของระบบนี้คือการใช้ RFID เป็นตัวระบุสินค้า และใช้ฐานข้อมูลในการจัดเก็บข้อมูลที่เกี่ยวข้องกับสินค้าแต่ละรายการ เช่น ชื่อสินค้า SKU ราคา จำนวนคงเหลือ และตำแหน่งจัดเก็บ

ระบบใช้โครงสร้างการจัดเก็บแบบ **Zone และ Shelf**
- **Zone** เป็นพื้นที่หลักของคลัง
- **Shelf** เป็นพื้นที่ย่อยภายใน Zone

สินค้าแต่ละรายการจะถูกผูกกับ RFID Tag และ Shelf เพื่อให้สามารถติดตามสินค้าได้ชัดเจนขึ้น ว่าสินค้าอยู่ที่ใดในคลัง และมีสถานะใดในกระบวนการทำงาน

ระบบยังสามารถต่อยอดสู่แนวคิดแบบ Smart System ได้ เช่น
- สแกน RFID เพื่อค้นหาสินค้า
- เปลี่ยนสถานะสินค้าอัตโนมัติจากธุรกรรม
- ตรวจสอบความจุของ Shelf
- แจ้งเตือนเมื่อสินค้าใกล้หมดหรือเกินพื้นที่จัดเก็บ

---

## ขอบเขตการทำงานของระบบ (System Scope)
ระบบมีขอบเขตการทำงานหลักดังนี้

### 1. จัดการข้อมูลสินค้า
- เพิ่มสินค้าใหม่
- แก้ไขข้อมูลสินค้า
- ลบสินค้า
- กำหนด SKU, ราคา, จำนวนคงเหลือ และ Reorder Point

### 2. จัดการ RFID
- ผูก RFID กับสินค้า
- ค้นหาสินค้าจาก RFID
- แสดงข้อมูลสินค้าและตำแหน่งจัดเก็บจากรหัส RFID

### 3. จัดการ Zone และ Shelf
- เพิ่ม Zone
- แก้ไข Zone
- ลบ Zone
- เพิ่ม Shelf ภายใน Zone
- แก้ไข Shelf
- ลบ Shelf
- แสดงความจุรวม ความจุที่ใช้งาน และความจุคงเหลือ

### 4. จัดการธุรกรรม
- Buy / Receive สำหรับรับสินค้าเข้า
- Sell / Issue สำหรับเบิกสินค้าออก
- อัปเดต stock อัตโนมัติ
- บันทึกประวัติธุรกรรมย้อนหลัง

### 5. จัดการพนักงาน
- เพิ่มข้อมูลพนักงาน
- แก้ไขข้อมูลพนักงาน
- ลบข้อมูลพนักงาน
- ระบุผู้รับผิดชอบในแต่ละธุรกรรม

### 6. แสดงผล Dashboard
- จำนวนสินค้า
- จำนวน RFID
- จำนวน Zone และ Shelf
- กราฟสถานะสินค้า
- กราฟ Buy / Sell
- รายการกิจกรรมล่าสุด

---

## ตารางหลักภายในระบบ (Database Tables)

### 1. `products`
ใช้เก็บข้อมูลสินค้าหลัก
- `product_id`
- `product_name`
- `sku`
- `price`
- `reorder_point`

### 2. `product_stock`
ใช้เก็บจำนวนสินค้าคงเหลือปัจจุบัน
- `product_id`
- `current_qty`

### 3. `rfid_tags`
ใช้เก็บข้อมูล RFID ที่ผูกกับสินค้า
- `rfid_id`
- `rfid_code`
- `product_id`
- `shelf_id`
- `status`

### 4. `zones`
ใช้เก็บข้อมูล Zone หลัก
- `zone_id`
- `zone_code`
- `zone_name`
- `total_capacity`

### 5. `shelves`
ใช้เก็บข้อมูล Shelf ภายในแต่ละ Zone
- `shelf_id`
- `zone_id`
- `shelf_code`
- `shelf_capacity`

### 6. `employees`
ใช้เก็บข้อมูลพนักงาน
- `employee_id`
- `full_name`
- `position_name`
- `phone`
- `email`

### 7. `stock_transactions`
ใช้เก็บประวัติธุรกรรมรับเข้าและเบิกออก
- `transaction_id`
- `transaction_type`
- `document_no`
- `product_id`
- `rfid_id`
- `shelf_id`
- `quantity`
- `transaction_datetime`
- `employee_id`
- `note`

### 8. `stock_logs`
ใช้เก็บรายละเอียดการเปลี่ยนแปลงจำนวนสินค้า
- `stock_log_id`
- `product_id`
- `transaction_id`
- `qty_before`
- `qty_change`
- `qty_after`
- `created_at`

---

## บัญชีสำหรับทดสอบระบบ (Test Credentials)

### ตัวอย่างบัญชีสำหรับทดสอบ
- **Username / Email:** `test@gmail.com`
- **Password:** `1234`


---

## ขั้นตอนการติดตั้งระบบ (Installation Guide)

### วิธีที่ 1: ติดตั้งบน Windows ด้วย XAMPP

#### 1. ติดตั้ง XAMPP
ติดตั้ง XAMPP ให้เรียบร้อย แล้วเปิด XAMPP Control Panel

#### 2. เปิดบริการที่จำเป็น
กด Start ที่
- Apache
- MySQL

#### 3. วางโฟลเดอร์โปรเจกต์
คัดลอกโฟลเดอร์โปรเจกต์ไปไว้ที่
`C:\xampp\htdocs\smartwarehouse`

#### 4. สร้างฐานข้อมูล
เปิด phpMyAdmin ที่
`http://localhost/phpmyadmin/`
สร้างฐานข้อมูลชื่อ
`smartwarehouse`

#### 5. Import ไฟล์ฐานข้อมูล
เข้า database smartwarehouse
เลือกเมนู Import
เลือกไฟล์ .sql
กด Import

#### 6. ตรวจสอบไฟล์เชื่อมฐานข้อมูล
แก้ไฟล์ config/db.php ให้ตรงกับค่าของเครื่อง

ตัวอย่าง
<?php
$host = "127.0.0.1";
$dbname = "smartwarehouse";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

#### 7. เปิดใช้งานระบบ
เปิดผ่าน browser ที่

` http://localhost/smartwarehouse/ `
