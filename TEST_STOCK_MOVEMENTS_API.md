# การทดสอบ Stock Movements API

## 🔐 Authentication

API นี้ต้องการ authentication token (Sanctum)

### 1. Login เพื่อรับ Token

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

Response:
```json
{
  "token": "your-auth-token-here",
  "user": { ... }
}
```

### 2. ใช้ Token ใน Header

```
Authorization: Bearer your-auth-token-here
```

---

## 📋 API Endpoints

### 1. รายการ Stock Movements (GET)

**Endpoint:** `GET /api/v1/stock-movements`

**Query Parameters:**
- `warehouse_id` (optional) - Filter by warehouse ID
- `product_id` (optional) - Filter by product ID
- `stock_id` (optional) - Filter by stock ID
- `type` (optional) - Filter by type: `in` or `out`
- `reference_type` (optional) - Filter by reference type (e.g., `App\Models\GoodsReceipt`)
- `reference_id` (optional) - Filter by reference ID
- `user_id` (optional) - Filter by user ID
- `from_date` (optional) - Filter from date (YYYY-MM-DD)
- `to_date` (optional) - Filter to date (YYYY-MM-DD)
- `search` (optional) - Search by product name, SKU, warehouse name, or code
- `per_page` (optional) - Items per page (1-100, default: 15)

**Example Requests:**

```bash
# รายการทั้งหมด
curl -X GET "http://localhost/api/v1/stock-movements" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# กรองตามคลังสินค้า
curl -X GET "http://localhost/api/v1/stock-movements?warehouse_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# กรองตามสินค้า
curl -X GET "http://localhost/api/v1/stock-movements?product_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# กรองตามประเภท (IN)
curl -X GET "http://localhost/api/v1/stock-movements?type=in" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# กรองตามช่วงวันที่
curl -X GET "http://localhost/api/v1/stock-movements?from_date=2025-01-01&to_date=2025-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# ค้นหาตามชื่อสินค้า/คลังสินค้า
curl -X GET "http://localhost/api/v1/stock-movements?search=Alpha" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# รวมหลาย filters
curl -X GET "http://localhost/api/v1/stock-movements?warehouse_id=1&type=in&from_date=2025-01-01&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

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
      "reference": {
        "id": 1,
        "type": "GoodsReceipt"
      },
      "user": {
        "id": 1,
        "name": "Test Admin",
        "email": "admin@example.com"
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
          "name": "Alpha Modular Sofa"
        }
      },
      "created_at": "2025-01-16T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 43,
    "last_page": 3,
    "from": 1,
    "to": 15
  }
}
```

---

### 2. รายละเอียด Stock Movement (GET)

**Endpoint:** `GET /api/v1/stock-movements/{id}`

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/stock-movements/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response Format:**

```json
{
  "data": {
    "id": 1,
    "stock_id": 1,
    "type": "in",
    "quantity": 10,
    "balance_before": 50,
    "balance_after": 60,
    "reference_type": "App\\Models\\GoodsReceipt",
    "reference_id": 1,
    "reference": {
      "id": 1,
      "type": "GoodsReceipt",
      "data": { ... }
    },
    "user": {
      "id": 1,
      "name": "Test Admin",
      "email": "admin@example.com"
    },
    "stock": {
      "id": 1,
      "quantity": 60,
      "warehouse": {
        "id": 1,
        "code": "WH-MAIN",
        "name": "Main Warehouse"
      },
      "product": {
        "id": 1,
        "sku": "ALPHA-SOFA-001",
        "name": "Alpha Modular Sofa",
        "price": 32900.00
      }
    },
    "created_at": "2025-01-16T10:30:00.000000Z",
    "updated_at": "2025-01-16T10:30:00.000000Z"
  }
}
```

---

## 🧪 ทดสอบด้วย Postman/Insomnia

### 1. Import Collection

สร้าง Postman Collection หรือใช้ Insomnia

**Base URL:** `http://localhost/api/v1`

**Environment Variables:**
- `base_url`: `http://localhost/api/v1`
- `token`: (จะได้หลัง login)

### 2. Requests

#### Login Request
- Method: `POST`
- URL: `{{base_url}}/auth/login`
- Body (JSON):
  ```json
  {
    "email": "admin@example.com",
    "password": "password"
  }
  ```
- Save token จาก response ไปที่ environment variable `token`

#### Get Stock Movements
- Method: `GET`
- URL: `{{base_url}}/stock-movements`
- Headers:
  - `Authorization`: `Bearer {{token}}`
  - `Accept`: `application/json`

#### Get Stock Movement by ID
- Method: `GET`
- URL: `{{base_url}}/stock-movements/1`
- Headers:
  - `Authorization`: `Bearer {{token}}`
  - `Accept`: `application/json`

---

## 🧪 ทดสอบด้วย PHP Test Script

```php
<?php

// test-api.php

$baseUrl = 'http://localhost/api/v1';
$email = 'admin@example.com';
$password = 'password';

// 1. Login
$ch = curl_init($baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $email,
    'password' => $password,
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);

$response = curl_exec($ch);
$loginData = json_decode($response, true);
$token = $loginData['token'] ?? null;

if (!$token) {
    die('Failed to get token');
}

echo "Token: $token\n\n";

// 2. Get Stock Movements
$ch = curl_init($baseUrl . '/stock-movements');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);

$response = curl_exec($ch);
$data = json_decode($response, true);

echo "Total Stock Movements: " . ($data['meta']['total'] ?? 0) . "\n";
echo "First Movement:\n";
print_r($data['data'][0] ?? []);

// 3. Test Filters
echo "\n\n=== Testing Filters ===\n";

// Filter by type=in
$ch = curl_init($baseUrl . '/stock-movements?type=in');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
$data = json_decode($response, true);
echo "Type=IN movements: " . ($data['meta']['total'] ?? 0) . "\n";

// Filter by warehouse
$ch = curl_init($baseUrl . '/stock-movements?warehouse_id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
$data = json_decode($response, true);
echo "Warehouse ID=1 movements: " . ($data['meta']['total'] ?? 0) . "\n";
```

---

## ✅ Checklist การทดสอบ

### Basic Tests
- [ ] Login และรับ token สำเร็จ
- [ ] GET /api/v1/stock-movements - ดูรายการทั้งหมด
- [ ] GET /api/v1/stock-movements/{id} - ดูรายละเอียด

### Filter Tests
- [ ] Filter by `warehouse_id`
- [ ] Filter by `product_id`
- [ ] Filter by `stock_id`
- [ ] Filter by `type` (in/out)
- [ ] Filter by `reference_type`
- [ ] Filter by `reference_id`
- [ ] Filter by `user_id`
- [ ] Filter by `from_date` and `to_date`
- [ ] Search by `search` parameter

### Combination Tests
- [ ] รวมหลาย filters
- [ ] Pagination (`per_page`)
- [ ] Response format ถูกต้อง
- [ ] Balance calculations (`balance_before`, `balance_after`)

### Error Tests
- [ ] 401 Unauthorized (ไม่มี token)
- [ ] 404 Not Found (stock movement ไม่มี)
- [ ] 422 Validation Error (invalid parameters)

---

## 📊 ข้อมูลทดสอบ

จากการรัน seeder แล้ว มีข้อมูลทดสอบ:
- **Stock Movements:** 43 รายการ
- **Warehouses:** 4 คลังสินค้า (WH-MAIN, WH-BKK, WH-CNX, WH-HKT)
- **Products:** 45 สินค้า
- **Goods Receipts:** 6 รายการ (Stock IN)
- **Sales Returns:** 3 รายการ (Stock IN)
- **Purchase Returns:** 1 รายการ (Stock OUT)

คุณสามารถใช้ข้อมูลเหล่านี้เพื่อทดสอบ filters ต่างๆ

---

**หมายเหตุ:** ตรวจสอบว่า server กำลังรันอยู่ที่ `http://localhost` หรือเปลี่ยน URL ตาม environment ของคุณ

