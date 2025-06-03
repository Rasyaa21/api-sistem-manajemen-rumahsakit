# 🏥 Hospital Management System API

Sistem Manajemen Rumah Sakit berbasis Laravel dengan RESTful API yang lengkap untuk mengelola pasien, dokter, pendaftaran, rekam medis, dan obat-obatan.

## 📋 Fitur Utama

### 🔐 Manajemen Pengguna
- **Multi-Role Authentication** (Admin, Dokter, Pasien)
- **Registrasi Pasien** langsung dengan akun aktif
- **Aplikasi Dokter** dengan persetujuan admin
- **Profile Management** untuk semua role

### 👩‍⚕️ Manajemen Dokter
- Pendaftaran dokter dengan upload dokumen (CV, Ijazah)
- Sistem persetujuan oleh admin
- Manajemen spesialisasi dan jadwal praktek
- Penetapan tarif konsultasi

### 👤 Manajemen Pasien
- Registrasi mudah dan cepat
- Profil lengkap (NIK, tanggal lahir, golongan darah, dll)
- Riwayat kunjungan dan rekam medis

### 📅 Sistem Pendaftaran
- Pendaftaran kunjungan pasien ke dokter
- Manajemen status (pending, confirmed, completed, cancelled)
- Keluhan dan jadwal kunjungan

### 📋 Rekam Medis Elektronik
- Pencatatan diagnosis dan treatment
- Resep obat dengan instruksi penggunaan
- Manajemen stok obat otomatis
- Akses rekam medis untuk pasien dan dokter

### 💊 Manajemen Obat
- Inventori obat lengkap
- Manajemen stok dan stock alerting
- Resep obat terintegrasi dengan rekam medis

### 📊 Pelaporan
- Laporan harian dokter (jumlah pasien, pendapatan)
- Rekapitulasi data kunjungan

---

## 🔄 Flow Aplikasi

### 1. 👤 **Registrasi & Autentikasi**

#### **Pasien:**
```
1. POST /api/v1/auth/patient/register
   ↓
2. Akun langsung aktif dengan role 'patient'
   ↓
3. POST /api/v1/auth/patient/login
   ↓
4. Terima token authentication
```

#### **Dokter:**
```
1. POST /api/v1/auth/doctor/register 
   ↓ (Upload CV & Ijazah via POST /api/v1/upload/document)
2. Aplikasi masuk dengan status 'pending'
   ↓
3. Admin review aplikasi
   ↓
4. PUT /api/v1/admin/doctor-applications/{id}/approve
   ↓
5. Akun berubah role menjadi 'doctor'
   ↓
6. POST /api/v1/auth/doctor/login
```

#### **Admin:**
```
1. Admin account di-seed langsung
   ↓
2. POST /api/v1/auth/admin/login
```

---

### 2. 📝 **Profil Management**

#### **Pasien melengkapi profil:**
```
PUT /api/v1/patient/profile
- NIK, tanggal lahir, gender, alamat, golongan darah
```

#### **Dokter melengkapi profil:**
```
PUT /api/v1/doctor/profile  
- Spesialisasi, jadwal praktek, tarif konsultasi
```

---

### 3. 📅 **Proses Pendaftaran Kunjungan**

```
1. GET /api/v1/patient/doctors
   ↓ (Pasien lihat daftar dokter)
2. POST /api/v1/patient/registration
   ↓ (Pasien daftar ke dokter tertentu)
3. Registration dibuat dengan status 'pending'
   ↓
4. PUT /api/v1/doctor/registrations/{id}/status
   ↓ (Dokter konfirmasi -> status 'confirmed')
5. Pasien datang untuk konsultasi
```

---

### 4. 🩺 **Proses Konsultasi & Rekam Medis**

```
1. GET /api/v1/doctor/registrations
   ↓ (Dokter lihat daftar pasien hari ini)
2. Dokter melakukan pemeriksaan
   ↓
3. POST /api/v1/doctor/records
   ↓ (Input diagnosis, treatment, resep obat)
4. Sistem otomatis:
   - Update stok obat
   - Update status registration -> 'completed'
   ↓
5. GET /api/v1/patient/medical-records
   ↓ (Pasien bisa akses rekam medisnya)
```

---

### 5. 💊 **Manajemen Obat**

#### **Admin mengelola stok:**
```
POST /api/v1/admin/medicines        # Tambah obat baru
PUT /api/v1/admin/medicines/{id}    # Update info obat
PUT /api/v1/admin/medicines/{id}/stock  # Update stok
```

#### **Dokter prescribe obat:**
```
GET /api/v1/doctor/medicines        # Lihat obat tersedia
POST /api/v1/doctor/records         # Resepkan obat dalam rekam medis
```

---

### 6. 📊 **Pelaporan**

```
GET /api/v1/doctor/report          # Generate laporan harian
GET /api/v1/doctor/reports         # Lihat semua laporan
```

---

## 📱 **User Journey Flow**

### 🎯 **Pasien Journey:**
1. **Register** → Akun langsung aktif
2. **Login** → Dapat token
3. **Complete Profile** → Isi data lengkap (optional)
4. **Browse Doctors** → Lihat dokter tersedia
5. **Make Registration** → Daftar ke dokter
6. **Visit Doctor** → Konsultasi (dokter yang input)
7. **View Medical Records** → Akses rekam medis sendiri

### 🎯 **Dokter Journey:**
1. **Apply** → Submit aplikasi + upload documents
2. **Wait Approval** → Admin review
3. **Login** → Setelah approved
4. **Complete Profile** → Set jadwal & tarif
5. **Manage Registrations** → Confirm/cancel appointments
6. **Conduct Consultation** → Input medical records
7. **Prescribe Medicine** → Pilih obat dari inventory
8. **Generate Reports** → Laporan harian

### 🎯 **Admin Journey:**
1. **Login** → Access admin panel
2. **Review Applications** → Approve/reject doctor applications
3. **Manage Users** → CRUD users & roles
4. **Manage Medicine** → CRUD medicine inventory
5. **Monitor System** → View all data

---

## 🔗 **API Endpoints Summary**

### **Authentication:**
- `POST /api/v1/auth/patient/register`
- `POST /api/v1/auth/patient/login`
- `POST /api/v1/auth/doctor/register`
- `POST /api/v1/auth/doctor/login`
- `POST /api/v1/auth/admin/login`

### **Profile Management:**
- `GET /api/v1/patient/me`
- `PUT /api/v1/patient/profile`
- `GET /api/v1/doctor/me`
- `PUT /api/v1/doctor/profile`

### **Registration System:**
- `GET /api/v1/patient/doctors`
- `POST /api/v1/patient/registration`
- `GET /api/v1/patient/registrations`
- `GET /api/v1/doctor/registrations`
- `PUT /api/v1/doctor/registrations/{id}/status`

### **Medical Records:**
- `GET /api/v1/doctor/records`
- `POST /api/v1/doctor/records`
- `GET /api/v1/doctor/records/{id}`
- `GET /api/v1/patient/medical-records`

### **Medicine Management:**
- `GET /api/v1/doctor/medicines` (available stock)
- `GET /api/v1/admin/medicines` (all medicines)
- `POST /api/v1/admin/medicines`
- `PUT /api/v1/admin/medicines/{id}`
- `DELETE /api/v1/admin/medicines/{id}`

### **File Upload:**
- `POST /api/v1/upload/document`
- `GET /api/v1/download/document/{filename}`
- `DELETE /api/v1/delete/document/{filename}` (admin)

### **Admin Functions:**
- `GET /api/v1/admin/users`
- `PUT /api/v1/admin/users/{id}/role`
- `DELETE /api/v1/admin/users/{id}`
- `GET /api/v1/admin/doctor-applications`
- `PUT /api/v1/admin/doctor-applications/{id}/approve`
- `PUT /api/v1/admin/doctor-applications/{id}/reject`

### **Reports:**
- `GET /api/v1/doctor/report` (generate daily)
- `GET /api/v1/doctor/reports` (view all)

---

## 🛠 **Technology Stack**

- **Backend:** Laravel 11
- **Database:** MySQL
- **Authentication:** Laravel Sanctum
- **Documentation:** Swagger/OpenAPI
- **File Storage:** Laravel Storage
- **Validation:** Laravel Form Requests

---

## 📄 **API Documentation**

Akses Swagger Documentation di: `http://localhost:8000/api/documentation`

---

## 🚀 **Getting Started**

1. **Clone & Install:**
```bash
git clone <repository-url>
cd api-manajemen-rumah-sakit
composer install
```

2. **Environment Setup:**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Setup:**
```bash
php artisan migrate
php artisan db:seed
```

4. **Storage Setup:**
```bash
php artisan storage:link
```

5. **Start Server:**
```bash
php artisan serve
```

6. **Generate API Docs:**
```bash
php artisan l5-swagger:generate
```

---

## 👥 **Default Users**

### Admin:
- **Email:** admin@hospital.com
- **Password:** password123

### Test User:
- **Email:** test@example.com
- **Password:** password

---

## 🔒 **Security Features**

- Token-based authentication
- Role-based access control
- File upload validation (PDF only, max 5MB)
- Input validation & sanitization
- CORS protection
- SQL injection prevention

---

## 📈 **Future Enhancements**

- [ ] Real-time notifications
- [ ] Appointment scheduling calendar
- [ ] Payment integration
- [ ] SMS/Email notifications
- [ ] Medical imaging support
- [ ] Analytics dashboard
- [ ] Mobile app support

---

## 📞 **Support**

Untuk pertanyaan atau bantuan, silakan buat issue di repository ini.

---

*Sistem Manajemen Rumah Sakit - Solusi digital untuk healthcare management* 🏥
