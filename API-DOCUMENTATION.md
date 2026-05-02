# 🔌 BIKE MARKETPLACE - API DOCUMENTATION

Base URL: `http://localhost:8888/bike-marketplace-complete/api`

All APIs return JSON format.

---

## 🔐 AUTHENTICATION

### 1. Login
**POST** `/auth/login.php`

Request:
```json
{
  "email": "user@example.com",
  "password": "123456"
}
```

Response:
```json
{
  "success": true,
  "message": "Đăng nhập thành công",
  "data": {
    "user_id": 1,
    "role": "buyer",
    "name": "Nguyen Van A",
    "email": "user@example.com"
  }
}
```

---

### 2. Register
**POST** `/auth/register.php`

Request:
```json
{
  "full_name": "Nguyen Van A",
  "email": "user@example.com",
  "password": "123456",
  "phone": "0123456789",
  "role": "buyer"
}
```

Response:
```json
{
  "success": true,
  "message": "Đăng ký thành công",
  "data": {
    "user_id": 1
  }
}
```

---

### 3. Logout
**GET** `/auth/logout.php`

Response:
```json
{
  "success": true,
  "message": "Đăng xuất thành công"
}
```

---

## 🚲 BIKES

### 4. Search Bikes
**GET** `/bikes/search.php`

Query Params:
- `q` - Search term
- `category_id` - Category ID
- `min_price` - Minimum price
- `max_price` - Maximum price
- `city` - City name
- `condition` - Condition status
- `inspected` - 1 for inspected bikes
- `sort` - newest|price_asc|price_desc
- `page` - Page number
- `per_page` - Items per page

Example:
```
GET /bikes/search.php?city=Hà Nội&min_price=5000000&max_price=15000000&page=1
```

Response:
```json
{
  "success": true,
  "data": {
    "bikes": [...],
    "total": 156,
    "page": 1,
    "per_page": 12,
    "total_pages": 13
  }
}
```

---

### 5. Get Bike Detail
**GET** `/bikes/get.php?id={bike_id}`

Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Giant TCR Advanced Pro",
    "price": 12000000,
    "description": "...",
    "images": [...],
    "seller_name": "...",
    "is_inspected": true
  }
}
```

---

### 6. Toggle Favorite
**POST** `/bikes/toggle-favorite.php`

⚠️ Requires authentication

Request:
```json
{
  "bike_id": 1
}
```

Response:
```json
{
  "success": true,
  "is_favorited": true,
  "message": "Đã thêm vào yêu thích"
}
```

---

## 📦 ORDERS

### 7. Create Order
**POST** `/orders/create.php`

⚠️ Requires authentication (buyer role)

Request:
```json
{
  "bike_id": 1,
  "deposit": true,
  "payment_method": "vnpay"
}
```

Response:
```json
{
  "success": true,
  "message": "Đơn hàng đã được tạo",
  "data": {
    "order_id": 123
  }
}
```

---

### 8. Update Order Status
**POST** `/orders/update-status.php`

⚠️ Requires authentication

Request:
```json
{
  "order_id": 123,
  "status": "completed",
  "note": "Đã giao xe thành công"
}
```

Response:
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công"
}
```

---

## 💬 MESSAGES

### 9. Send Message
**POST** `/messages/send.php`

⚠️ Requires authentication

Request:
```json
{
  "receiver_id": 5,
  "message": "Xe còn không bạn?",
  "bike_id": 1
}
```

Response:
```json
{
  "success": true,
  "message": "Tin nhắn đã được gửi",
  "data": {
    "message_id": 456
  }
}
```

---

### 10. Get Conversation
**GET** `/messages/get-conversation.php?partner_id={user_id}`

⚠️ Requires authentication

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sender_id": 2,
      "receiver_id": 5,
      "message": "Xe còn không bạn?",
      "created_at": "2024-04-28 10:30:00",
      "sender_name": "Nguyen Van A"
    }
  ]
}
```

---

## 🔍 INSPECTIONS

### 11. Submit Inspection
**POST** `/inspections/submit.php`

⚠️ Requires authentication (inspector role)

Request:
```json
{
  "inspection_id": 10,
  "frame_condition": "good",
  "brake_condition": "excellent",
  "drivetrain_condition": "good",
  "wheel_condition": "good",
  "overall_rating": 4.5,
  "notes": "Xe trong tình trạng tốt"
}
```

Response:
```json
{
  "success": true,
  "message": "Báo cáo kiểm định đã được gửi"
}
```

---

## 💳 PAYMENT

### 12. VNPay Return
**GET** `/payment/vnpay-return.php`

This endpoint is called by VNPay after payment.

Automatically redirects to order detail page with payment status.

---

## 🔒 AUTHENTICATION

Most APIs require authentication. After login, session is automatically managed.

Check authentication:
```javascript
fetch('/api/bikes/toggle-favorite.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ bike_id: 1 })
})
```

---

## ⚠️ ERROR HANDLING

All APIs return consistent error format:

```json
{
  "success": false,
  "message": "Error description here"
}
```

HTTP Status Codes:
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed

---

## 📝 TESTING

Use Postman, cURL, or browser:

```bash
# Login
curl -X POST http://localhost:8888/bike-marketplace-complete/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456"}'

# Search bikes
curl http://localhost:8888/bike-marketplace-complete/api/bikes/search.php?city=Hanoi
```

---

## 🚀 READY TO USE

All 12 APIs are production-ready with:
- ✅ Input validation
- ✅ Error handling
- ✅ Authentication check
- ✅ JSON responses
- ✅ Security (PDO, CSRF protection)

**Happy coding! 🎉**
