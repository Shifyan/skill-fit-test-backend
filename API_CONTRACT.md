# API Contract - Sistem Informasi Administrasi RT

Base URL: `http://127.0.0.1:8000/api`

Standard Response Structure:

```json
{
    "status": "success",
    "message": "Pesan deskriptif",
    "data": {}
}
```

Standard Error Response:

```json
{
    "status": "error",
    "message": "Pesan error / validasi gagal",
    "errors": {}
}
```

---

## 🔐 1. Authentication

### `POST /login`

Login admin RT.

**Request Body:** `application/json`

```json
{
    "email": "admin@rt.com",
    "password": "password123"
}
```

**Response (200 OK):**

```json
{
    "status": "success",
    "message": "Login berhasil",
    "data": {
        "access_token": "1|sanctum_token_string...",
        "token_type": "Bearer",
        "user": {
            "id": "uuid-admin-id",
            "name": "Admin RT",
            "email": "admin@rt.com"
        }
    }
}
```

### `POST /logout`

Revoke token admin yang sedang login. Requires Header: `Authorization: Bearer <token>`

**Response (200 OK):**

```json
{
    "status": "success",
    "message": "Logout berhasil",
    "data": null
}
```

### `GET /me`

Get detail user admin terotentikasi. Requires Header: `Authorization: Bearer <token>`

**Response (200 OK):**

```json
{
    "status": "success",
    "message": "Data profil pengguna",
    "data": {
        "id": "uuid-admin-id",
        "name": "Admin RT",
        "email": "admin@rt.com",
        "created_at": "2026-07-24T08:00:00.000000Z",
        "updated_at": "2026-07-24T08:00:00.000000Z"
    }
}
```

---

## 🏡 2. Houses Management (`/houses`)

Requires Header: `Authorization: Bearer <token>`

### `GET /houses`

Ambil seluruh data rumah beserta penghuni aktif saat ini.

**Response (200 OK):**

```json
{
    "status": "success",
    "message": "Daftar rumah berhasil diambil",
    "data": [
        {
            "id": "uuid-house-id",
            "house_number": "A-01",
            "house_status": "occupied",
            "current_history": {
                "id": "uuid-history-id",
                "house_id": "uuid-house-id",
                "resident_id": "uuid-resident-id",
                "start_date": "2025-07-24",
                "end_date": null,
                "resident": {
                    "id": "uuid-resident-id",
                    "fullname": "Budi Santoso",
                    "resident_status": "settler",
                    "phone_number": "081234567890",
                    "marriage_status": "married"
                }
            }
        }
    ]
}
```

### `POST /houses`

Tambah rumah baru.

**Request Body:** `application/json`

```json
{
    "house_number": "A-21",
    "house_status": "vacant"
}
```

### `GET /houses/{id}`

Detail rumah beserta riwayat penghuni & daftar tagihannya.

**Response (200 OK):**

```json
{
    "status": "success",
    "message": "Detail rumah berhasil diambil",
    "data": {
        "id": "uuid-house-id",
        "house_number": "A-01",
        "house_status": "occupied",
        "current_history": {},
        "histories": [],
        "invoices": []
    }
}
```

### `PUT /houses/{id}`

Update data rumah.

**Request Body:** `application/json`

```json
{
    "house_number": "A-01",
    "house_status": "vacant"
}
```

### `DELETE /houses/{id}`

Hapus data rumah.

---

### 🔑 Assign & Unassign Resident

#### `POST /houses/{id}/assign`

Menempatkan penghuni ke dalam rumah. Otomatis set status rumah menjadi `occupied`, menutup riwayat aktif lama (jika ada), dan membuat record `house_histories` baru.

**Request Body:** `application/json`

```json
{
    "resident_id": "uuid-resident-id",
    "start_date": "2026-07-25"
}
```

**Response (200 OK):**

```json
{
  "status": "success",
  "message": "Penghuni berhasil ditempatkan di rumah ini",
  "data": {
    "id": "uuid-house-id",
    "house_number": "A-01",
    "house_status": "occupied",
    "current_history": { ... }
  }
}
```

#### `POST /houses/{id}/unassign`

Mengosongkan rumah. Otomatis set status rumah menjadi `vacant` dan mengisi `end_date` pada riwayat penghuni aktif.

**Request Body:** `application/json`

```json
{
    "end_date": "2026-07-25"
}
```

---

## 👤 3. Residents Management (`/residents`)

Requires Header: `Authorization: Bearer <token>`

### `GET /residents`

Daftar seluruh penghuni. Query parameter opsional: `?status=settler` atau `?status=temporary`.

**Response (200 OK):**

```json
{
    "status": "success",
    "message": "Daftar penghuni berhasil diambil",
    "data": [
        {
            "id": "uuid-resident-id",
            "fullname": "Budi Santoso",
            "ktp_image": "ktp/default_ktp.jpg",
            "ktp_image_url": "http://127.0.0.1:8000/storage/ktp/default_ktp.jpg",
            "resident_status": "settler",
            "phone_number": "081234567890",
            "marriage_status": "married",
            "current_history": {
                "house": {
                    "house_number": "A-01"
                }
            }
        }
    ]
}
```

### `POST /residents`

Tambah data penghuni baru beserta Upload KTP.

**Request Type:** `multipart/form-data`

- `fullname` (string, required)
- `ktp_image` (file: jpg, jpeg, png, max 2MB, required)
- `resident_status` (enum: `settler`, `temporary`, required)
- `phone_number` (string, required)
- `marriage_status` (enum: `single`, `married`, required)

### `GET /residents/{id}`

Detail penghuni beserta histori rumah yang pernah ditempati.

### `PUT /residents/{id}` / `POST /residents/{id}/update`

Update data penghuni (Gunakan `POST /residents/{id}/update` jika menyertakan file `ktp_image` baru).

### `DELETE /residents/{id}`

Hapus data penghuni dan file KTP dari penyimpanan.

---

## 💰 4. Invoices & Payments (`/invoices` & `/payments`)

Requires Header: `Authorization: Bearer <token>`

### `GET /invoices`

List tagihan iuran.
Query Parameters (opsional):

- `month` (int 1-12)
- `year` (int)
- `house_id` (uuid)
- `resident_id` (uuid)
- `status` (`paid` / `unpaid`)

### `POST /invoices/generate`

Generate tagihan iuran bulanan untuk semua rumah berstatus `occupied`.

**Request Body:** `application/json`

```json
{
    "month": 8,
    "year": 2026
}
```

### `POST /payments`

Proses pembayaran iuran (Multi-Bulan / Tahunan).

**Opsi A (Berdasarkan House, Tahun, & Array Bulan):**

```json
{
    "house_id": "uuid-house-id",
    "year": 2026,
    "months": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
    "type": "both",
    "payment_date": "2026-07-25"
}
```

_Note for `type`: `cleaning` (Rp 15.000), `security` (Rp 100.000), atau `both` (Rp 115.000)._

**Opsi B (Berdasarkan Array Invoices):**

```json
{
    "payments": [
        { "invoice_id": "uuid-inv-1", "type": "both" },
        { "invoice_id": "uuid-inv-2", "type": "cleaning" }
    ],
    "payment_date": "2026-07-25"
}
```

---

## 📊 5. Financial Transactions (`/transactions`)

Requires Header: `Authorization: Bearer <token>`

### `GET /transactions`

List transaksi keuangan.
Query Parameters: `type` (`income`/`expenses`), `category`, `month`, `year`, `start_date`, `end_date`.

### `POST /transactions`

Pencatatan manual transaksi pengeluaran / pemasukan operasional.

**Request Body:** `application/json`

```json
{
    "transaction_type": "expenses",
    "category": "gaji_satpam",
    "amount": 1200000,
    "transaction_date": "2026-07-25",
    "description": "Gaji Satpam Pos RT"
}
```

---

## 📈 6. Reports & Summary (`/reports/summary`)

Requires Header: `Authorization: Bearer <token>`

### `GET /reports/summary?year=2026`

Mengambil data agregasi bulanan (12 Bulan) dan metrik statistik dashboard.

**Response (200 OK):**

```json
{
    "status": "success",
    "message": "Laporan ringkasan tahun 2026 berhasil diambil",
    "data": {
        "year": 2026,
        "monthly_summary": [
            {
                "month": 1,
                "month_name": "January",
                "income": 1725000.0,
                "expenses": 1350000.0,
                "balance": 375000.0
            }
        ],
        "annual_income": 17250000.0,
        "annual_expenses": 17050000.0,
        "annual_net_balance": 200000.0,
        "dashboard": {
            "total_houses": 20,
            "occupied_houses": 15,
            "vacant_houses": 5,
            "total_residents": 18,
            "settler_residents": 15,
            "temporary_residents": 3,
            "unpaid_invoices_count": 30,
            "total_income_all_time": 17250000.0,
            "total_expenses_all_time": 17050000.0,
            "net_balance_all_time": 200000.0
        }
    }
}
```
