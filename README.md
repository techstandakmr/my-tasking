# 📋 My Tasking

**My Tasking** is a secure and smart Task Management System built using **PHP** and **MySQL**. It enables users to create and manage tasks with full account security, including email verification, two-step authentication, and detailed task control features.

---

## 🚀 Technologies Used

- **Frontend**: HTML, CSS, JavaScript  
- **Styling**: Tailwind CSS (via CDN)  
- **Backend**: PHP  
- **Database**: MySQL  
- **Email System**: PHPMailer with Gmail SMTP  
- **Authentication**: **Custom token-based login** (secure tokens generated using cryptographic functions)  
- **UI**: Fully responsive design (mobile, tablet, desktop)

---

## 🔐 User Features

### 🧾 Account Management

- **Create Account**
  - Email verification with OTP  
  - Success confirmation email after verification

- **Login**
  - Secure custom token-based login  
  - Sends email alert on each login attempt

- **Reset Email**
  1. Verify using old email and password  
  2. Send OTP to old email  
  3. Verify OTP and update email  
  4. Sends confirmation email on success

- **Reset Password**
  1. Verify using old email  
  2. Send OTP to email  
  3. Verify OTP and update password  
  4. Sends confirmation email on success

- **Delete Account**
  1. Verify using email and password  
  2. Send OTP to email  
  3. Verify OTP and delete account  
  4. Sends confirmation email on success

- **Update Profile**
  - Update `name`, `title`, and `avatar`

---

## ✅ Task Features

### 🛠️ Task Management

- **Create**, **Edit**, and **Delete** tasks  
- Each task includes:
  - `Title`, `Description`  
  - `Stage`: Started, Pending, Completed  
  - `Priority`: High, Mid, Low  
  - `Category`

### 📢 Smart Status Messages

- “This task was supposed to start 2 days ago.”  
- “Only 1 day(s) left to complete this task.”  
- “This task has been completed successfully.”

### 🔍 Search & Filter

- **Search** by title, stage, or priority  
- **Filter** by:
  - Stage: Started, Pending, Completed  
  - Priority: High, Mid, Low

### 📅 Calendar & Notifications

- Calendar view for date-based task management  
- Auto-reminders and deadline notifications
