# แผนพัฒนา Stock Management System

## 📋 สรุปสถานะปัจจุบัน

### ✅ โครงสร้างที่มีอยู่แล้ว

1. **Database Structure**
   - ✅ `warehouses` - ตารางคลังสินค้า
   - ✅ `stocks` - ตารางยอดคงเหลือ (product + warehouse)
   - ✅ `stock_movements` - ตารางประวัติการเคลื่อนไหวสินค้า
   - ✅ `goods_receipts`, `goods_receipt_items` - ใบรับสินค้า
   - ✅ `purchase_returns`, `purchase_return_items` - ใบคืนสินค้าซื้อ
   - ✅ `sales_returns`, `sales_return_items` - ใบคืนสินค้าขาย

2. **Models**
   - ✅ `Warehouse` - Model สำหรับคลังสินค้า
   - ✅ `Stock` - Model สำหรับยอดคงเหลือ
   - ✅ `StockMovement` - Model สำหรับการเคลื่อนไหวสินค้า

3. **Services**
   - ✅ `GRNService` - บริการรับสินค้า (เชื่อมต่อกับ stock movements แล้ว)
   - ✅ `ReturnService` - บริการคืนสินค้า (เชื่อมต่อกับ stock movements แล้ว)

4. **Controllers**
   - ✅ `StockController` - มี methods: `index()`, `show()`, `in()`, `out()`
   - ✅ `WarehouseController` - CRUD สำหรับคลังสินค้า

5. **Seeders**
   - ✅ `WarehouseSeeder` - สร้างคลังสินค้าตัวอย่าง
   - ✅ `StockSeeder` - สร้างยอดคงเหลือเริ่มต้น
   - ⚠️ `GoodsReceiptSeeder` - ยังว่างเปล่า (ต้องเพิ่ม)

---

## 🎯 แผนการพัฒนาตาม 3 จุดที่ต้องการ

### 1. จำลองข้อมูลเพื่อทดสอบ เพิ่มลดยอดคงเหลือ (ตัดสต๊อก) จากการ ซื้อ/รับคืน

#### 1.1 สร้าง Seeder สำหรับทดสอบ Goods Receipt
**ไฟล์:** `database/seeders/GoodsReceiptTestSeeder.php`

**หน้าที่:**
- สร้าง Purchase orders ตัวอย่าง
- สร้าง Goods Receipts จาก Purchase orders
- จำลองการรับสินค้าเข้า stock (ผ่าน GRNService)
- สร้าง Stock Movements จากการรับสินค้า

**ข้อมูลที่จะจำลอง:**
- Purchase orders ที่ approved แล้ว
- Goods Receipts จากหลาย Purchase orders
- Stock movements type 'in' จาก Goods Receipts
- ยอดคงเหลือที่อัพเดทแล้ว

#### 1.2 สร้าง Seeder สำหรับทดสอบ Returns
**ไฟล์:** `database/seeders/ReturnsTestSeeder.php`

**หน้าที่:**
- สร้าง Sales Returns ตัวอย่าง
- สร้าง Purchase Returns ตัวอย่าง
- จำลองการ approve returns (ผ่าน ReturnService)
- สร้าง Stock Movements จาก returns:
  - Sales Return → Stock IN (สินค้าคืนเข้า)
  - Purchase Return → Stock OUT (สินค้าคืนออก)

**ข้อมูลที่จะจำลอง:**
- Sales Returns ที่มี status 'approved'
- Purchase Returns ที่มี status 'approved'
- Stock movements type 'in' จาก Sales Returns
- Stock movements type 'out' จาก Purchase Returns

#### 1.3 สร้าง Seeder หลักสำหรับทดสอบ
**ไฟล์:** `database/seeders/StockTestDataSeeder.php`

**หน้าที่:**
- เรียกใช้ WarehouseSeeder (ถ้ายังไม่มี)
- เรียกใช้ ProductSeeder (ถ้ายังไม่มี)
- เรียกใช้ StockSeeder (ถ้ายังไม่มี)
- เรียกใช้ GoodsReceiptTestSeeder
- เรียกใช้ ReturnsTestSeeder
- สร้างข้อมูลทดสอบที่ครอบคลุมทุกสถานการณ์

---

### 2. สอบถามรายการเคลื่อนไหวสินค้า

#### 2.1 สร้าง API Endpoint สำหรับ Stock Movements
**ไฟล์:** `app/Http/Controllers/Api/StockMovementController.php`

**Endpoints ที่ต้องสร้าง:**
- `GET /api/v1/stock-movements` - รายการเคลื่อนไหวสินค้า (พร้อม filter)

**Filter Parameters:**
- `warehouse_id` - กรองตามคลังสินค้า
- `product_id` - กรองตามสินค้า
- `stock_id` - กรองตาม stock record
- `type` - กรองตามประเภท ('in' หรือ 'out')
- `reference_type` - กรองตาม reference type (เช่น 'App\Models\GoodsReceipt')
- `reference_id` - กรองตาม reference ID
- `user_id` - กรองตามผู้สร้างรายการ
- `from_date` - วันที่เริ่มต้น
- `to_date` - วันที่สิ้นสุด
- `search` - ค้นหา (product name, warehouse name, etc.)

**Response Format:**
```json
{
  "data": [
    {
      "id": 1,
      "stock_id": 1,
      "type": "in",
      "quantity": 10,
      "balance_before": 50,
      "balance_after": 60,
      "reference_type": "App\\Models\\GoodsReceipt",
      "reference_id": 1,
      "user": {
        "id": 1,
        "name": "John Doe"
      },
      "stock": {
        "id": 1,
        "warehouse": {
          "id": 1,
          "code": "WH-MAIN",
          "name": "Main Warehouse"
        },
        "product": {
          "id": 1,
          "sku": "ALPHA-SOFA-001",
          "name": "Alpha Sofa 001"
        }
      },
      "created_at": "2025-01-15T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

#### 2.2 ปรับปรุง StockMovement Model
**ไฟล์:** `app/Models/StockMovement.php`

**เพิ่ม Relationships:**
- `reference()` - Polymorphic relationship สำหรับ reference_type และ reference_id

**เพิ่ม Accessors:**
- `balance_before` - ยอดคงเหลือก่อนรายการนี้
- `balance_after` - ยอดคงเหลือหลังรายการนี้

**เพิ่ม Scopes:**
- `filterByWarehouse($warehouseId)`
- `filterByProduct($productId)`
- `filterByType($type)`
- `filterByDateRange($fromDate, $toDate)`
- `filterByReference($referenceType, $referenceId)`

#### 2.3 เพิ่ม Migration สำหรับ balance tracking (Optional)
**ไฟล์:** `database/migrations/YYYY_MM_DD_HHMMSS_add_balance_fields_to_stock_movements_table.php`

**Optional:** เพิ่ม fields สำหรับเก็บ balance_before และ balance_after เพื่อความรวดเร็วในการ query (denormalized)

```php
$table->unsignedBigInteger('balance_before')->nullable();
$table->unsignedBigInteger('balance_after')->nullable();
```

> **หมายเหตุ:** ถ้าไม่อยากเพิ่ม field อาจคำนวณจากยอดคงเหลือปัจจุบัน - (sum of movements after this movement)

#### 2.4 เพิ่ม Routes
**ไฟล์:** `routes/api.php`

```php
Route::prefix('stock-movements')->group(function () {
    Route::get('/', [StockMovementController::class, 'index'])->name('stock-movements.index');
    Route::get('/{stockMovement}', [StockMovementController::class, 'show'])->name('stock-movements.show');
});
```

#### 2.5 สร้าง Request Validation
**ไฟล์:** `app/Http/Requests/Inventory/StockMovementIndexRequest.php`

**Validation Rules:**
- `warehouse_id` - optional, exists:warehouses,id
- `product_id` - optional, exists:products,id
- `type` - optional, in:in,out
- `from_date` - optional, date
- `to_date` - optional, date, after_or_equal:from_date
- `per_page` - optional, integer, min:1, max:100

---

### 3. แสดงยอดคงเหลือตามคลังสินค้าที่มีในระบบ

#### 3.1 ปรับปรุง StockController.index()
**ไฟล์:** `app/Http/Controllers/Api/StockController.php`

**ปรับปรุง Filter Parameters:**
- ✅ `warehouse_id` - มีอยู่แล้ว
- ✅ `product_id` - มีอยู่แล้ว
- ✅ `min_quantity` - มีอยู่แล้ว
- ➕ เพิ่ม `warehouse_code` - กรองตามรหัสคลังสินค้า
- ➕ เพิ่ม `product_sku` - กรองตาม SKU สินค้า
- ➕ เพิ่ม `product_name` - ค้นหาตามชื่อสินค้า
- ➕ เพิ่ม `has_stock` - กรองเฉพาะที่มีสต๊อก (quantity > 0)
- ➕ เพิ่ม `zero_stock` - กรองเฉพาะที่สต๊อกเป็นศูนย์

**Response Format ปรับปรุง:**
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "product_id": 1,
      "quantity": 50,
      "warehouse": {
        "id": 1,
        "code": "WH-MAIN",
        "name": "Main Warehouse",
        "is_active": true
      },
      "product": {
        "id": 1,
        "sku": "ALPHA-SOFA-001",
        "name": "Alpha Sofa 001",
        "price": 15000.00
      },
      "last_movement": {
        "id": 10,
        "type": "in",
        "quantity": 10,
        "created_at": "2025-01-15T10:30:00.000000Z"
      },
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
    "summary": {
      "total_warehouses": 4,
      "total_products": 15,
      "total_quantity": 200
    }
  }
}
```

#### 3.2 สร้าง API Endpoint สำหรับ Summary by Warehouse
**ไฟล์:** `app/Http/Controllers/Api/StockController.php`

**เพิ่ม Method:**
- `summaryByWarehouse(Request $request)` - สรุปยอดคงเหลือตามคลังสินค้า

**Endpoint:** `GET /api/v1/stocks/summary/by-warehouse`

**Response Format:**
```json
{
  "data": [
    {
      "warehouse_id": 1,
      "warehouse": {
        "id": 1,
        "code": "WH-MAIN",
        "name": "Main Warehouse"
      },
      "total_products": 25,
      "total_quantity": 150,
      "total_value": 2250000.00,
      "products_with_stock": 20,
      "products_zero_stock": 5,
      "top_products": [
        {
          "product_id": 1,
          "product": {
            "sku": "ALPHA-SOFA-001",
            "name": "Alpha Sofa 001"
          },
          "quantity": 50
        }
      ]
    }
  ],
  "meta": {
    "total_warehouses": 4,
    "grand_total_quantity": 500,
    "grand_total_value": 7500000.00
  }
}
```

#### 3.3 สร้าง API Endpoint สำหรับ Stock by Warehouse Detail
**ไฟล์:** `app/Http/Controllers/Api/WarehouseController.php`

**เพิ่ม Method:**
- `stocks(Warehouse $warehouse, Request $request)` - แสดงยอดคงเหลือของคลังสินค้าเฉพาะ

**Endpoint:** `GET /api/v1/warehouses/{warehouse}/stocks`

**Filter Parameters:**
- `product_id` - กรองตามสินค้า
- `product_sku` - กรองตาม SKU
- `min_quantity` - ยอดคงเหลือขั้นต่ำ
- `has_stock` - มีสต๊อกหรือไม่

**Response Format:**
```json
{
  "data": {
    "warehouse": {
      "id": 1,
      "code": "WH-MAIN",
      "name": "Main Warehouse",
      "is_active": true
    },
    "stocks": [
      {
        "id": 1,
        "product_id": 1,
        "quantity": 50,
        "product": {
          "id": 1,
          "sku": "ALPHA-SOFA-001",
          "name": "Alpha Sofa 001",
          "price": 15000.00
        },
        "last_movement": {
          "id": 10,
          "type": "in",
          "quantity": 10,
          "created_at": "2025-01-15T10:30:00.000000Z"
        }
      }
    ],
    "summary": {
      "total_products": 25,
      "total_quantity": 150,
      "products_with_stock": 20,
      "products_zero_stock": 5
    }
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 25,
    "last_page": 2
  }
}
```

#### 3.4 เพิ่ม Routes
**ไฟล์:** `routes/api.php`

```php
// ใน Inventory routes section
Route::get('stocks/summary/by-warehouse', [StockController::class, 'summaryByWarehouse'])->name('stocks.summary.by-warehouse');

// ใน Warehouse routes section
Route::get('warehouses/{warehouse}/stocks', [WarehouseController::class, 'stocks'])->name('warehouses.stocks');
```

---

## 📝 ขั้นตอนการพัฒนา

### Phase 1: สร้าง Seeders สำหรับทดสอบ (1-2 วัน)

1. ✅ สร้าง `GoodsReceiptTestSeeder.php`
   - จำลอง Purchase orders
   - จำลอง Goods Receipts
   - สร้าง Stock Movements จาก Goods Receipts

2. ✅ สร้าง `ReturnsTestSeeder.php`
   - จำลอง Sales Returns
   - จำลอง Purchase Returns
   - สร้าง Stock Movements จาก Returns

3. ✅ สร้าง `StockTestDataSeeder.php`
   - รวม seeders ทั้งหมด
   - ครอบคลุมทุกสถานการณ์

### Phase 2: API สำหรับ Stock Movements (2-3 วัน)

1. ✅ สร้าง `StockMovementController.php`
   - Method `index()` - รายการเคลื่อนไหวสินค้า
   - Method `show()` - รายละเอียดการเคลื่อนไหว

2. ✅ ปรับปรุง `StockMovement` Model
   - เพิ่ม Relationships
   - เพิ่ม Scopes
   - เพิ่ม Accessors

3. ✅ สร้าง Request Validation
   - `StockMovementIndexRequest.php`

4. ✅ เพิ่ม Routes

### Phase 3: API สำหรับ Stock Summary (2-3 วัน)

1. ✅ ปรับปรุง `StockController.index()`
   - เพิ่ม Filter Parameters
   - ปรับปรุง Response Format

2. ✅ เพิ่ม Method `summaryByWarehouse()`
   - สรุปยอดคงเหลือตามคลังสินค้า

3. ✅ ปรับปรุง `WarehouseController`
   - เพิ่ม Method `stocks()`
   - แสดงยอดคงเหลือของคลังสินค้าเฉพาะ

4. ✅ เพิ่ม Routes

---

## 🧪 การทดสอบ

### 1. ทดสอบ Seeders
```bash
php artisan db:seed --class=StockTestDataSeeder
```

**ตรวจสอบ:**
- ✅ มี Goods Receipts ถูกสร้าง
- ✅ มี Stock Movements type 'in' จาก Goods Receipts
- ✅ ยอดคงเหลือถูกอัพเดทถูกต้อง
- ✅ มี Returns ถูกสร้าง
- ✅ มี Stock Movements จาก Returns

### 2. ทดสอบ API Endpoints

#### ทดสอบ Stock Movements
```bash
# รายการทั้งหมด
GET /api/v1/stock-movements

# กรองตามคลังสินค้า
GET /api/v1/stock-movements?warehouse_id=1

# กรองตามสินค้า
GET /api/v1/stock-movements?product_id=1

# กรองตามประเภท
GET /api/v1/stock-movements?type=in

# กรองตามช่วงวันที่
GET /api/v1/stock-movements?from_date=2025-01-01&to_date=2025-01-31
```

#### ทดสอบ Stock Summary
```bash
# รายการยอดคงเหลือทั้งหมด
GET /api/v1/stocks

# สรุปตามคลังสินค้า
GET /api/v1/stocks/summary/by-warehouse

# ยอดคงเหลือของคลังสินค้าเฉพาะ
GET /api/v1/warehouses/1/stocks
```

---

## 📚 เอกสารเพิ่มเติม

### Database Schema Summary

**warehouses**
- `id`, `code`, `name`, `is_active`, `timestamps`

**stocks**
- `id`, `warehouse_id`, `product_id`, `quantity`, `timestamps`
- Unique: `(warehouse_id, product_id)`

**stock_movements**
- `id`, `stock_id`, `type` (enum: in/out), `quantity`, `reference_type`, `reference_id`, `user_id`, `timestamps`

### Business Rules

1. **Stock Movement Creation**
   - เมื่อรับสินค้า (Goods Receipt) → Stock IN
   - เมื่อคืนสินค้าซื้อ (Purchase Return) → Stock OUT
   - เมื่อคืนสินค้าขาย (Sales Return) → Stock IN

2. **Stock Quantity Update**
   - Stock IN → เพิ่ม `quantity`
   - Stock OUT → ลด `quantity` (ต้องตรวจสอบว่ามีพอ)

3. **Reference Tracking**
   - `reference_type` = Model class name (เช่น 'App\Models\GoodsReceipt')
   - `reference_id` = ID ของ record ที่อ้างอิง

---

## ✅ Checklist

### Phase 1: Seeders
- [x] สร้าง `GoodsReceiptTestSeeder.php`
- [x] สร้าง `ReturnsTestSeeder.php`
- [x] สร้าง `StockTestDataSeeder.php`
- [ ] ทดสอบ seeders

### Phase 2: Stock Movements API
- [x] สร้าง `StockMovementController.php`
- [x] ปรับปรุง `StockMovement` Model
- [x] สร้าง `StockMovementIndexRequest.php`
- [x] เพิ่ม Routes
- [ ] ทดสอบ API endpoints

### Phase 3: Stock Summary API
- [x] ปรับปรุง `StockController.index()`
- [x] เพิ่ม `StockController.summaryByWarehouse()`
- [x] ปรับปรุง `WarehouseController.stocks()`
- [x] เพิ่ม Routes
- [ ] ทดสอบ API endpoints

---

## 🚀 ขั้นตอนต่อไป

หลังจากพัฒนาเสร็จแล้ว:

1. ✅ ทดสอบ Integration Tests
2. ✅ ทดสอบ Performance (query optimization)
3. ✅ สร้าง API Documentation
4. ✅ Frontend Integration

---

**สร้างเมื่อ:** 2025-01-16  
**อัพเดทล่าสุด:** 2025-01-16

