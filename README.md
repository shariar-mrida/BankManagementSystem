# Banking Account & Transaction Management System
### North South University &bull; Department of Electrical and Computer Engineering
**Course:** CSE311L &ndash; Database Management Systems Lab (Section 1)  
**Database Name:** `group4_banking`

---

## 📌 Project Overview
This repository contains the complete implementation for **CSE311L Group Project (Part A: Complete Database & Part B: Frontend Development)**:
1. **Complete Database Implementation (Part A):** Full schema, integrity constraints, realistic sample dataset, and implementation of all assigned SQL questions (including Views, Stored Procedures, Functions, Triggers, and Role-Based Access Control).
2. **Database-Focused Frontend (Part B):** Developed using clean PHP, HTML5, and CSS3, connected directly to MySQL via PDO. Displays database tables and live query results without any hardcoded outputs.

---

## 📁 Repository Structure
```
BankManagementSystem/
├── database/
│   └── group4_banking.sql       # Complete exported database SQL script
├── config/
│   └── db.php                   # Database connection (supports Local XAMPP & Cloud ENV)
├── includes/
│   ├── header.php               # Shared navigation & DB status pill
│   └── footer.php               # System footer
├── public/
│   ├── css/
│   │   └── style.css            # Clean, database-focused banking styling
│   └── js/
│       └── app.js               # Interactive utilities
├── api/
│   └── index.php                # Vercel serverless entrypoint
├── index.php                    # Banking Dashboard & live query feeds
├── tables.php                   # Database Table Explorer (all 8 tables)
├── vercel.json                  # Vercel deployment configuration
├── .gitignore                   # Git ignore file
└── README.md                    # Project documentation
```

---

## 🚀 Local Setup & Installation (XAMPP / PHP)

### Step 1: Import Database into MySQL
1. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open **phpMyAdmin** in your browser (`http://localhost/phpmyadmin`).
3. Click **Import** in the top navigation bar.
4. Select `database/group4_banking.sql` and click **Go** / **Import**.
   *(Alternatively, run via command line: `mysql -u root -p < database/group4_banking.sql`)*.

### Step 2: Run the Web Frontend
#### Option A: Using PHP Built-in Server (Fastest)
Open terminal in the project directory:
```bash
php -S localhost:8000
```
Open your browser and visit: **`http://localhost:8000`**

#### Option B: Using XAMPP `htdocs`
1. Copy or move this `BankManagementSystem` folder into `C:\xampp\htdocs\`.
2. Visit in your browser: **`http://localhost/BankManagementSystem`**

---

## 🌐 Deploying to Vercel via GitHub

### 1. Database in the Cloud
Since Vercel is a serverless platform, it connects to a remote MySQL database. You can host MySQL for free on:
- [Aiven for MySQL](https://aiven.io)
- [TiDB Cloud](https://tidbcloud.com)
- [Railway](https://railway.app)
- [PlanetScale](https://planetscale.com)

After creating the database, import `database/group4_banking.sql` into your remote database instance.

### 2. Push Code to GitHub
```bash
git init
git add .
git commit -m "CSE311L Group 4 Banking System"
git branch -M main
git remote add origin https://github.com/<your-username>/<your-repo-name>.git
git push -u origin main
```

### 3. Deploy on Vercel
1. Log in to [Vercel](https://vercel.com) and click **"Add New Project"**.
2. Select your GitHub repository.
3. In **Environment Variables**, add:
   - `DB_HOST`: Your cloud database host (e.g. `mysql-xxxx.aivencloud.com`)
   - `DB_PORT`: Database port (e.g. `3306` or cloud port)
   - `DB_USER`: Database username
   - `DB_PASS`: Database password
   - `DB_NAME`: `group4_banking`
   *(Or supply `MYSQL_URL` / `DATABASE_URL`)*
4. Click **Deploy**. Vercel will automatically use `vercel.json` with the PHP serverless runtime.
