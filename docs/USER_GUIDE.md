# GreenBytes Asana — User Guide (with screenshots)

This guide explains how to use the GreenBytes Asana web app: log in, create teams/projects/tasks, upload task images, use boards, comment on tasks, and manage users/permissions.

Screenshots for this guide live in: `docs/images/`

For a short 1-page version, see: `docs/QUICK_TRAINING.md`.

---

## 1) Login

1. Open the application URL.
2. You will see only the login screen.
3. After login, the sidebar + menu items appear based on your permissions.

Screenshot:

Screenshot (add later): `docs/images/user-01-login.png`

If you do not have access to a menu item, it will be hidden and direct URL access will return **403 Forbidden**.

---

## 2) Roles & Permissions (Access)

The app uses role-based access control.

### Roles
- **Admin**: Full access, can manage users/roles/permissions.
- **Manager**: Can manage teams/projects/tasks (but not users).
- **Member**: Can view teams/projects/tasks, **create tasks**, and **comment on tasks**.
- **Viewer**: Can view teams/projects/tasks, but cannot create/update/delete.

Screenshot (menus differ by role):

Screenshot (add later): `docs/images/user-02-sidebar-permissions.png`

### Permission names (reference)
Common permissions you may see in the Admin UI:
- `teams.view`, `teams.manage`
- `projects.view`, `projects.manage`
- `tasks.view`, `tasks.manage`
- `comments.view`, `comments.manage`
- `performance.view` (legacy)
- `users.view`, `users.manage`

Some modules also have more granular permissions:
- `*.create` (add)
- `*.update` (edit)
- `*.delete` (delete)

---

## 3) My Profile

Click **Profile** in the top header to open your profile page.

You can:
- Update your **name**
- Change your **password**

Notes:
- Your **email address cannot be changed** from the Profile page.

Screenshot:

Screenshot (add later): `docs/images/user-profile.png`

---

## 4) Teams

Go to **Teams** to:
- View teams you own or are a member of.
- Create a new team (requires `teams.manage`).

Notes:
- Teams have an “owner” and can have additional members.

Screenshot:

Screenshot (add later): `docs/images/user-03-teams.png`

---

## 5) Projects

Go to **Projects** to:
- View projects belonging to teams you can access (requires `projects.view`).
- Create projects (requires `projects.manage`).

Screenshot:

Screenshot (add later): `docs/images/user-04-projects.png`

---

## 6) Tasks

Go to **Tasks** to:
- View tasks in projects you can access (requires `tasks.view`).
- Create tasks (requires `tasks.manage`).

Screenshot (Tasks list):

Screenshot (add later): `docs/images/user-05-tasks-list.png`

### Team membership requirement (important)
The **Assignee** dropdown only shows users who are in the same **Team** as the task’s Project.

If you cannot find someone to assign:
- Ask an Admin to add that user to the Team (see section 10).

### Creating a task
You can create tasks in two ways:
- From **Home**, click **+ Create task** under **My tasks** (if you have task creation permission)
- Or from the **Tasks** page

On the Tasks page, fill:
- Project
- Title
- Status (Todo / Doing / Done)
- Due date (optional)
- Assignee (optional)
- Task image (optional)

Then click **Add task**.

Screenshot (Create task form):

Screenshot (add later): `docs/images/user-06-create-task.png`

### Assignment rules (Asana-style rules)
These rules are enforced by the backend and also reflected in the Assignee dropdown:

- **Admin** can assign to **Admin, Manager, Member** (within accessible teams)
- **Manager** can assign to **Member** only
- **Member** can assign **only to himself**

Screenshots (optional, one per role):

Screenshot (add later): `docs/images/08-assignee-admin.png`

Screenshot (add later): `docs/images/09-assignee-manager.png`

Screenshot (add later): `docs/images/10-assignee-member.png`

### Task image upload
When you attach an image while creating a task:
- The image is stored on the app’s public storage.
- The task list and board cards show a thumbnail.

Screenshot (Task with image):

Screenshot (add later): `docs/images/user-07-task-image.png`

### Task detail drawer (Asana-like)
On the Tasks page, clicking a task opens a **right-side drawer overlay** (it should not shrink the list/board behind it).

Screenshot (Drawer open):

Screenshot (add later): `docs/images/12-drawer-open.png`

---

## 7) Boards (Drag & Drop)

Open a project board from a project’s **Board** view.

- Tasks are shown in columns: **Todo**, **Doing**, **Done**.
- Drag tasks between columns to update status.

Access:
- Viewing boards generally requires `projects.view`.
- Drag/drop updates require `tasks.manage`.

Screenshot (Board view):

Screenshot (add later): `docs/images/user-08-board.png`

---

## 8) Task Comments

Each task has a detail page:
- Open **Tasks**
- Click a task title to open its detail page

### Adding a comment
- Requires `comments.manage`.
- Type your message and click **Post comment**.

Comments show:
- Author name
- Timestamp
- Comment body

Screenshot (Comments):

Screenshot (add later): `docs/images/user-09-comments.png`

---

## 9) Performance Dashboard

Go to **Performance** (**Admin only**) to see:
- Total tasks
- Breakdown by status
- Breakdown by assignee
- Due-soon tasks list

Screenshot:

Screenshot (add later): `docs/images/user-10-performance.png`

---

## 10) Search

Use the top search bar to search:
- Projects
- Tasks

Access:
- Search is available if you can view projects and/or tasks.

Screenshot:

Screenshot (add later): `docs/images/user-11-search.png`

---

## 11) Admin: User Management

Admins can manage users at **Admin → Users**.

You can:
- Create a new user
- Assign roles
- Assign direct permissions

Also (important for task assignment):
- Add users to Teams so they appear as assignees in that Team’s projects.

Screenshot (Admin users list):

Screenshot (add later): `docs/images/06-admin-users-list.png`

Screenshot (Admin edit user → Teams):

Screenshot (add later): `docs/images/07-admin-user-teams.png`

Notes:
- Direct permissions apply in addition to role permissions.

---

## 12) Troubleshooting

### “Page has no styling / CSS missing”
This usually happens when the app thinks the Vite dev server is running.
- Ensure `public/hot` is NOT present unless you are actively running Vite.

### “403 Forbidden”
- Your account does not have the required permission.
- Ask an Admin to grant the permission (or role).

### Images not showing
- Ensure the storage symlink exists:
  - `php artisan storage:link`

If you are on cPanel with File Manager only and symlinks are not allowed:
- Copy `storage/app/public` into `public/storage` (same folder structure).

---

## 12) Screenshot checklist

Save screenshots with these exact filenames (place into `docs/images/`):

- `user-01-login.png`
- `user-02-sidebar-permissions.png`
- `user-03-teams.png`
- `user-04-projects.png`
- `user-05-tasks-list.png`
- `user-06-create-task.png`
- `user-07-task-image.png`
- `user-08-board.png`
- `user-09-comments.png`
- `user-10-performance.png`
- `user-11-search.png`

Optional (role-based assignment / drawer screenshots):

- `08-assignee-admin.png`
- `09-assignee-manager.png`
- `10-assignee-member.png`
- `12-drawer-open.png`

---

## 13) Quick start for admins (local)

Typical setup steps:
```bash
php artisan migrate
php artisan db:seed --class=DatabaseSeeder
```

Then log in with the seeded admin user (if configured by your team).
