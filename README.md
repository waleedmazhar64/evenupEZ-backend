# 💸 evenupEZ – Backend

**evenupEZ** is a Laravel-powered backend that makes splitting expenses effortless.  
Create groups, add shared expenses, track who owes what, and settle payments — just like **Splitwise**, but simple and customizable for your needs.


## ✨ Features

✅ **User Authentication** – Secure registration & login  
✅ **Group Management** – Create, join, and manage groups  
✅ **Member Invitations** – Invite friends via email  
✅ **Expense Tracking** – Split equally or by custom shares  
✅ **Payment Settlement** – Mark balances as paid  
✅ **Clean REST API** – Built for easy frontend integration  

---

## 🛠 Tech Stack

| Component         | Technology |
|------------------|-----------|
| **Backend**      | [Laravel](https://laravel.com/) |
| **Database**     | MySQL (or any Laravel-supported DB) |
| **Auth**         | Laravel Passport / Sanctum |
| **API**          | RESTful JSON |
| **Deployment**   | Apache / Nginx |

---

## 📂 Project Structure

    
    evenupEZ-backend/
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/       # API Controllers
    │   │   └── Middleware/        # Auth & Middleware
    │   ├── Models/                # User, Group, Expense models
    │   └── ...
    ├── database/
    │   ├── migrations/            # DB migrations
    │   └── seeders/               # Test data
    ├── routes/
    │   └── api.php                # API routes
    └── ...



## ⚙️ Installation & Setup
### Clone the repo
    git clone https://github.com/your-username/evenupEZ-backend.git
    cd evenupEZ-backend


### Install dependencies

    composer install


### Environment setup

    cp .env.example .env


### Update .env with your DB credentials and other configs.

    Generate app key

    php artisan key:generate


### Run migrations & seeders

    php artisan migrate --seed


### Start the server

 php artisan serve


Backend will run at http://127.0.0.1:8000
