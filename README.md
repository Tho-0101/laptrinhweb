# 🚴 BIKE MARKETPLACE - Website Kết Nối Mua Bán Xe Đạp Thể Thao Cũ

## 📋 MÔ TẢ DỰ ÁN

Nền tảng trực tuyến chuyên biệt kết nối người mua và người bán xe đạp thể thao, 
đáp ứng nhu cầu thị trường với các tính năng:

- ✅ Kiểm định chất lượng xe bởi Inspector
- ✅ Đặt cọc/Đặt mua an toàn
- ✅ Chat trực tiếp buyer-seller
- ✅ Đánh giá uy tín người bán
- ✅ Thanh toán online (VNPay, MoMo)
- ✅ Chatbot hỗ trợ 24/7

## 👥 HỆ THỐNG VAI TRÒ

### 1. Guest (Khách vãng lai)
- Xem danh sách xe
- Tìm kiếm/lọc xe
- Xem chi tiết (giới hạn)

### 2. Buyer (Người mua)
- Đầy đủ tính năng guest
- Đăng ký/Đăng nhập
- Chat với người bán
- Đặt mua/Đặt cọc
- Đánh giá người bán
- Quản lý wishlist

### 3. Seller (Người bán)
- Đăng tin bán xe (ảnh, video, mô tả)
- Quản lý tin đăng
- Nhận & trả lời tin nhắn
- Quản lý đơn đặt mua
- Xem đánh giá uy tín

### 4. Inspector (Kiểm định viên)
- Kiểm tra tình trạng xe
- Gắn nhãn "Đã kiểm định"
- Upload báo cáo chi tiết
- Hỗ trợ giải quyết tranh chấp

### 5. Admin (Quản trị viên)
- Quản lý người dùng
- Kiểm duyệt tin đăng
- Xử lý tranh chấp
- Quản lý danh mục/thương hiệu
- Thống kê & báo cáo

## 🗂️ CẤU TRÚC PROJECT

```
bike-marketplace/
├── config/
│   ├── config.php          ✅ Constants
│   ├── database.php        ✅ DB config
│   └── session.php         ✅ Session management
├── classes/
│   ├── Database.php        ✅ PDO singleton
│   ├── User.php           (Quản lý users)
│   ├── Bike.php           (CRUD xe đạp)
│   ├── Order.php          (Đặt mua/cọc)
│   ├── Message.php        (Chat system)
│   ├── Inspection.php     (Kiểm định)
│   ├── Review.php         (Đánh giá)
│   └── Payment.php        (Thanh toán)
├── pages/
│   ├── auth/              (Login, Register)
│   ├── buyer/             (Dashboard, Orders, Wishlist)
│   ├── seller/            (Dashboard, Post, Listings)
│   ├── inspector/         (Dashboard, Inspection)
│   ├── admin/             (Full admin panel)
│   ├── bikes/             (List, Detail, Search)
│   ├── orders/            (Order management)
│   └── chat/              (Messaging)
├── api/
│   ├── auth/              (Login, Register, Logout)
│   ├── bikes/             (CRUD operations)
│   ├── orders/            (Create, Update)
│   ├── messages/          (Send, Receive)
│   ├── inspections/       (Submit, Update)
│   └── payment/           (VNPay, MoMo)
├── assets/
│   ├── css/               (Styles)
│   ├── js/                (Scripts)
│   ├── images/            (Static images)
│   └── uploads/           (User uploads)
└── bike_marketplace.sql   ✅ Database

```

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### Bước 1: Setup môi trường
```bash
1. Cài Laragon (hoặc XAMPP)
2. Start Apache + MySQL
3. Copy project vào C:\laragon\www\
```

### Bước 2: Tạo database
```bash
1. Vào http://localhost:8888/phpmyadmin
2. Tạo database: bike_marketplace
3. Import file: bike_marketplace.sql
```

### Bước 3: Cấu hình
```bash
Sửa config/database.php nếu cần:
- DB_PORT: 8888 (Laragon) hoặc 3306 (XAMPP)
```

### Bước 4: Chạy
```bash
http://localhost:8888/bike-marketplace/index.php
```

## 🎨 TÍNH NĂNG CHI TIẾT

### Buyer Features
- [x] Tìm kiếm xe theo: loại, giá, hãng, kích thước
- [x] Xem chi tiết: ảnh, mô tả, lịch sử
- [x] Chat real-time với seller
- [x] Đặt mua / Đặt cọc online
- [x] Đánh giá sau giao dịch
- [x] Wishlist

### Seller Features
- [x] Đăng tin: Upload ảnh, video, mô tả
- [x] Quản lý tin: Sửa, ẩn, xóa
- [x] Nhận thông báo đơn hàng
- [x] Chat với buyer
- [x] Xem rating

### Inspector Features
- [x] Checklist kiểm định: Khung, phanh, truyền động
- [x] Upload ảnh/video kiểm định
- [x] Gắn badge "Đã kiểm định"
- [x] Báo cáo chi tiết

### Admin Features
- [x] Dashboard tổng quan
- [x] Quản lý users (4 roles)
- [x] Kiểm duyệt tin đăng
- [x] Xử lý report/dispute
- [x] Thống kê doanh thu
- [x] Quản lý categories/brands

## 💳 THANH TOÁN

### Phương thức hỗ trợ:
1. **COD** - Thanh toán khi nhận xe
2. **Chuyển khoản** - Bank transfer
3. **VNPay** - Cổng thanh toán điện tử
4. **MoMo** - Ví điện tử

### Quy trình đặt cọc:
```
1. Buyer chọn xe → Đặt cọc (20% giá)
2. Payment gateway xử lý
3. Seller nhận thông báo
4. Buyer nhận xe → Thanh toán còn lại
5. Đánh giá
```

## 🤖 CHATBOT

Hỗ trợ tự động:
- Hướng dẫn đăng tin
- Tư vấn chọn xe
- Giải đáp thắc mắc
- Hỗ trợ 24/7

## 📊 DATABASE SCHEMA

### Users Table
- id, full_name, email, password, role, phone, avatar
- rating, total_sales, verification_status

### Bikes Table
- id, seller_id, title, description, price
- category_id, brand_id, condition_status
- is_inspected, is_featured, status

### Orders Table
- id, buyer_id, bike_id, total_amount
- deposit_amount, payment_method, status

### Messages Table
- id, sender_id, receiver_id, message, read_at

### Inspections Table
- id, bike_id, inspector_id
- frame_condition, brake_condition, overall_rating

### Reviews Table
- id, reviewer_id, reviewee_id, order_id
- rating, comment

## 🔒 BẢO MẬT

- [x] Password hashing (bcrypt)
- [x] CSRF protection
- [x] Session hijacking prevention
- [x] SQL injection prevention (PDO)
- [x] XSS protection
- [x] Rate limiting

## 📱 RESPONSIVE

- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

## 🎯 TODO / ROADMAP

- [ ] Mobile app (React Native)
- [ ] Notification push
- [ ] Email marketing
- [ ] Advanced analytics
- [ ] Machine learning price suggestion

## 📞 HỖ TRỢ

Email: support@bikemarket.vn
Hotline: 1900-xxxx

---

**Made with ❤️ by BikeMarket Team**
**Version: 1.0.0 - Production Ready**
