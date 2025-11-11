# Catalog CRUD Seed Data

ไฟล์ seeder ในโฟลเดอร์นี้ถูกเตรียมเพื่อให้ทีม Frontend/QA ทดสอบฟีเจอร์ Catalog ตามสเปคใน `specs/main/` ได้สะดวก โดยเฉพาะกรณี CRUD ของ Products, Brands, Categories และการดูยอดคงเหลือสต็อก

## สิ่งที่ถูก seed ไว้

- **Categories**: โครงต้นไม้ 4 กลุ่มหลัก (Furniture, Home Decor, Lighting, Outdoor) พร้อมหมวดย่อยเชิงลึก ใช้ `CategorySeeder`
- **Brands**: 5 แบรนด์ตัวอย่างพร้อมสถานะ active/inactive และข้อมูลเมตา ใช้ `BrandSeeder`
- **Products**: 14 สินค้าตัวอย่างกระจายหลายหมวดและสถานะ (active/draft/archived) พร้อมจับคู่แบรนด์ ใช้ `ProductSeeder`
- **Warehouses + Stocks**: โกดัง 5 แห่งและยอดคงเหลือแยกตามคลัง รวมถึง movement เริ่มต้น ใช้ `WarehouseSeeder` และ `StockSeeder`
- **E2E Fixtures**: ข้อมูลเสริมสำหรับ end-to-end test (ผู้ใช้ admin + สินค้า SKUs เริ่มต้น) ใช้ `E2ETestSeeder`

## วิธีใช้งาน

```bash
# รันทุก seeder (แนะนำสำหรับ dev/test environment)
php artisan migrate:fresh --seed

# หรือ seed เฉพาะ Catalog ชุดหลัก (ไม่รวม E2E)
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=BrandSeeder
php artisan db:seed --class=WarehouseSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=StockSeeder
```

> ทุก seeder ใช้ `updateOrCreate` เพื่อให้ rerun ได้โดยไม่เกิดข้อมูลซ้ำ สามารถรันซ้ำได้ทุกครั้งที่ต้อง sync ข้อมูล

## ข้อมูลสำคัญสำหรับการทดสอบ

- ตัวกรอง category สามารถทดสอบได้ทั้งหมวดย่อยที่ active และ inactive (เช่น `tv-stands`, `floor-lamps`)
- ตัวกรอง brand มีทั้งแบรนด์ active (`alpha-furniture`, `beta-living`, `crafted-comfort`, `nordic-lights`) และ inactive (`urban-retreat`)
- สินค้าตัวอย่างครอบคลุมทุกสถานะในระบบ (`active`, `draft`, `archived`)
- โกดัง `WH-MAIN` ใช้เป็นคลังหลักสำหรับการคำนวณ on-hand; `WH-OLD` ถูกตั้ง inactive เพื่อจำลองกรณีคลังปิดใช้งาน
- ข้อมูล movement ตั้งต้นถูกบันทึกไว้ด้วย `reference_type = SEEDER_INIT` เพื่อให้ระบบแสดงไทม์ไลน์สต็อกได้ทันที

## ตัวอย่างข้อมูลอ้างอิง

| SKU | ชื่อสินค้า | หมวด | แบรนด์ | สถานะ | คลังหลัก (qty) |
| --- | --- | --- | --- | --- | --- |
| `ALPHA-SOFA-001` | Alpha Modular Sofa | `sofas` | `alpha-furniture` | `active` | `WH-MAIN: 14` |
| `CRAFT-BAR-001` | Compact Bar Cabinet | `bar-cabinets` | `crafted-comfort` | `archived` | `WH-OLD: 2` |
| `NORD-LAMP-001` | Scandi Table Lamp | `table-lamps` | `nordic-lights` | `draft` | `WH-MAIN: 10` |
| `URBN-PATIO-001` | Urban Outdoor Lounge | `patio-sets` | `urban-retreat` | `archived` | `WH-OLD: 3` |

> สามารถดูรายละเอียดเพิ่มเติมได้จากไฟล์ seeder แต่ละตัวในโฟลเดอร์นี้
