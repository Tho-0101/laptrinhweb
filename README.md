# Bike Marketplace PHP Backend

Backend PHP thuan cho website mua ban xe dap the thao cu.

## Yeu cau

- PHP 8.1+
- MySQL 8+
- Laragon/Apache

## Cai dat nhanh

1. Tao database `bike_marketplace` va import schema/seed.
2. Copy `.env.example` thanh `.env` va cap nhat thong so DB.
3. Dat document root vao thu muc `public`.
4. Goi API:
   - `GET /api/health`
   - `POST /api/register`
   - `POST /api/login`
   - `GET /api/me` (Bearer token)
   - `PUT /api/me` (Bearer token)
   - `PUT /api/me/password` (Bearer token)
   - `POST /api/upload/image` (Bearer token, form-data)
   - `GET /api/brands`
   - `GET /api/conversations` (Bearer token)
   - `GET /api/conversations/unread-count` (Bearer token)
   - `POST /api/conversations` (Bearer token, body: `listing_id`)
   - `POST /api/conversations/{id}/mark-read` (Bearer token)
   - `GET /api/conversations/{id}/messages` (Bearer token)
   - `POST /api/conversations/{id}/messages` (Bearer token)
   - `GET /api/listings?page=1&limit=10&status=published`
   - `GET /api/listings/{id}`
   - `POST /api/listings` (Bearer token)
   - `PUT /api/listings/{id}` (Owner/Admin token)
   - `DELETE /api/listings/{id}` (Owner/Admin token)
   - `GET /api/database/all` (Bearer token admin)

## Response format

- Success:
  - `ok`: true
  - `message`: string
  - `data`: object|array|null
  - `meta`: object|null
- Error:
  - `ok`: false
  - `message`: string
  - `errors`: object|array|null

## Business rules

- `PUT /api/listings/{id}`: chi owner/admin duoc sua.
- `DELETE /api/listings/{id}`: chi owner/admin duoc xoa.
- Listing co `status = sold` chi admin moi duoc sua/xoa.

## Chat body samples

- Tao conversation:
  - `{"listing_id": 1}`
- Gui tin nhan:
  - `{"content":"Xe con khong anh?"}`
  - hoac `{"image_url":"https://example.com/image.jpg"}`
- Danh dau da doc:
  - `POST /api/conversations/{id}/mark-read`
- Dem tong tin chua doc:
  - `GET /api/conversations/unread-count`

## Me/profile body sample

- Cap nhat profile:
  - `{"full_name":"User A","phone":"0901234567","bio":"Nguoi yeu thich xe dap","province":"TP.HCM"}`
- Doi mat khau:
  - `{"current_password":"12345678","new_password":"newpass123","confirm_password":"newpass123"}`

## Register role sample

- Register co the gui them `role`:
  - `nguoi_mua` (buyer)
  - `nguoi_ban` (buyer + seller)
  - `cua_hang` (buyer + seller + shop_owner)
- Vi du:
  - `{"full_name":"Shop A","email":"shopa@bike.vn","password":"12345678","role":"cua_hang"}`

## Upload image sample

- Endpoint: `POST /api/upload/image`
- Header: `Authorization: Bearer <token>`
- Body type: `form-data`
  - key `image` (type File) bat buoc
  - key `target` (Text) tuy chon: `listing|chat|avatar`
- Gioi han:
  - dung luong <= 5MB
  - dinh dang: jpg, png, webp
- Sau khi upload, dung `data.url` de gan vao:
  - `listing_images.image_url`
  - `messages.image_url`
  - `users.avatar_url`

## Cau truc

- `public/index.php`: entrypoint + router
- `public/frontend/index.html`: frontend HTML5 + Bootstrap + JS
- `public/frontend/js/`: tach module frontend (state, api, auth, listings, chat, upload, main)
- `src/`: core classes va controllers

## Chay frontend

- Mo trinh duyet:
  - `http://localhost:8888/bike-shop1/public/frontend/index.html`
- Trang tach rieng:
  - `http://localhost:8888/bike-shop1/public/frontend/login.html`
  - `http://localhost:8888/bike-shop1/public/frontend/listings.html`
  - `http://localhost:8888/bike-shop1/public/frontend/chat.html`
  - `http://localhost:8888/bike-shop1/public/frontend/profile.html`
- Frontend da co:
  - login/register
  - listing list + create + edit/delete + search/filter/sort
  - hien thi hinh san pham trong card listing
  - upload image
  - conversation + send/refresh/read message
  - profile update + change password
  - giao dien theo style web thuong mai (hero, card san pham, bo cuc dashboard)
  - da tinh chinh giao dien theo template tham chieu (navbar trang sticky, hero image, feature section, footer)
