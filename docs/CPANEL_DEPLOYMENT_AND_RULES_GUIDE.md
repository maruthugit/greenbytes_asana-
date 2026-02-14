# GreenBytes Asana — cPanel Deployment & Rules Guide (with screenshots)

This document is written for **cPanel File Manager only** deployments (no SSH).

> Add screenshots by placing image files into: `docs/images/`
> 
> Use these exact filenames so the images render automatically.

---

## 1) cPanel Deployment (File Manager only)

### 1.1 Prerequisites
- PHP **8.2+**
- MySQL/MariaDB database + phpMyAdmin access
- `storage/` and `bootstrap/cache/` must be writable

### 1.2 Recommended folder layout
Recommended:
- App code in: `/home/<cpanel-user>/greenbytes_app/`
- Public web root points to: `/home/<cpanel-user>/greenbytes_app/public`

If you cannot change document root:
- Keep the app in `/home/<cpanel-user>/greenbytes_app/`
- Put only the `public/` contents into `public_html/`
- Update `public_html/index.php` paths to point to `/home/<cpanel-user>/greenbytes_app/`

**Screenshot (cPanel document root / File Manager structure)**

Screenshot (add later): `docs/images/01-cpanel-folder-layout.png`

### 1.3 Upload steps (production)
1. Upload your app files to the target folder.
2. Ensure `.env` exists in the app root (same level as `artisan`).
3. Confirm `public/build/` exists (Vite build output).

**Screenshot (uploaded folder in File Manager)**

Screenshot (add later): `docs/images/02-cpanel-uploaded-files.png`

### 1.4 Configure `.env`
Minimum required:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`
- `APP_KEY=base64:...`
- `DB_HOST=localhost`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

#### 1.4.1 Mail settings (required for task assignment emails)
This app’s default mailer is `log` unless you set SMTP in `.env`.

Add/update these in production `.env`:
- `MAIL_MAILER=smtp`
- `MAIL_HOST=...` (your SMTP host, often `mail.your-domain.com`)
- `MAIL_PORT=587` (TLS) or `465` (SSL)
- `MAIL_USERNAME=...`
- `MAIL_PASSWORD=...`
- `MAIL_FROM_ADDRESS=...`
- `MAIL_FROM_NAME="GreenBytes Asana"`

If you use port `465` (SSL), also set:
- `MAIL_SCHEME=smtps`

**If config is cached** (common on production), `.env` mail changes will not apply until you delete:
- `bootstrap/cache/config.php`

**Screenshot (`.env` in File Manager – redact passwords)**

Screenshot (add later): `docs/images/03-env-file.png`

### 1.5 Database setup (no SSH)
Because you cannot run `php artisan migrate/seed` on cPanel without terminal:
1. Run migrations + seeding locally.
2. Export the database as `.sql`.
3. Import in cPanel → phpMyAdmin.

**Screenshot (phpMyAdmin import)**

Screenshot (add later): `docs/images/04-phpmyadmin-import.png`

### 1.6 Clear cached Blade views on cPanel
After uploading Blade files, clear compiled views:
- Delete files inside: `storage/framework/views/` (delete the `*.php` files)

**Screenshot (storage/framework/views before/after delete)**

Screenshot (add later): `docs/images/05-clear-views-cache.png`

---

## 2) Upload list for this update

Upload these files to production (keep the same paths):

### 2.1 Task assignment + team membership
- `app/Http/Controllers/TaskController.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/edit.blade.php`

### 2.2 Asana-style task detail drawer
- `resources/views/tasks/index.blade.php`
- `resources/views/tasks/_detail_panel.blade.php`

### 2.3 Fix 500 error on `/tasks/{id}`
- `resources/views/tasks/show.blade.php`

---

## 3) Team membership (required for task assignment)

### 3.1 How it works
- Each **Project** belongs to a **Team**.
- A user must be in that **Team** to appear in the Assignee list.

### 3.2 Add a user to a team
1. Go to **Admin → Users**
2. Click the user
3. Under **Teams**, tick the team(s)
4. Click **Save**

**Screenshot (Admin → Users list)**

Screenshot (add later): `docs/images/06-admin-users-list.png`

**Screenshot (Admin → Edit user → Teams section)**

Screenshot (add later): `docs/images/07-admin-user-teams.png`

---

## 4) Assignment rules (your rules)

These rules are enforced in the backend and also in the Assignee dropdown.

### 4.1 Rules
- **Admin** can assign to **Admin, Manager, Member** (within accessible teams)
- **Manager** can assign to **Member** only
- **Member** can assign **only to himself**

### 4.2 Expected behavior examples
- Admin user opens a task → Assignee dropdown shows Admin/Manager/Member.
- Manager user opens a task → Assignee dropdown shows only Members.
- Member user opens a task → Assignee dropdown shows only himself.

**Screenshot (Admin view – assignee dropdown)**

Screenshot (add later): `docs/images/08-assignee-admin.png`

**Screenshot (Manager view – assignee dropdown)**

Screenshot (add later): `docs/images/09-assignee-manager.png`

**Screenshot (Member view – assignee dropdown)**

Screenshot (add later): `docs/images/10-assignee-member.png`

---

## 5) Asana-style task detail drawer (UI)

### 5.1 Expected behavior
- Clicking a task opens a **right-side drawer overlay**.
- The task list/board should **not shrink**.
- Drawer background is slightly “glass” (mostly opaque + blur).

**Screenshot (Drawer closed)**

Screenshot (add later): `docs/images/11-drawer-closed.png`

**Screenshot (Drawer open, list not resized)**

Screenshot (add later): `docs/images/12-drawer-open.png`

---

## 6) Troubleshooting

### 6.1 “Page has no styling / CSS missing”
- Ensure `public/hot` does **not** exist in production.
- Ensure `public/build/manifest.json` exists.

### 6.2 500 error after upload
- Clear `storage/framework/views/*.php` (compiled views)
- Confirm the uploaded file paths are correct.

### 6.3 Images not showing
- Ensure `public/storage` is correctly set up.
- If symlinks aren’t allowed, copy `storage/app/public` to `public/storage`.

---

## 7) Screenshot checklist (quick)
Save screenshots with these exact filenames:
- `docs/images/01-cpanel-folder-layout.png`
- `docs/images/02-cpanel-uploaded-files.png`
- `docs/images/03-env-file.png`
- `docs/images/04-phpmyadmin-import.png`
- `docs/images/05-clear-views-cache.png`
- `docs/images/06-admin-users-list.png`
- `docs/images/07-admin-user-teams.png`
- `docs/images/08-assignee-admin.png`
- `docs/images/09-assignee-manager.png`
- `docs/images/10-assignee-member.png`
- `docs/images/11-drawer-closed.png`
- `docs/images/12-drawer-open.png`
