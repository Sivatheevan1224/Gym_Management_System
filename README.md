# Gym_Management_System
🧰 What the project is

It’s a web-based “Gym Management System” built using PHP, CSS, and HTML (languages noted in the repo: PHP ~48.9%, CSS ~48.0%, HTML ~3.1%). 
GitHub

The code structure includes directories like classes/, components/, images/, style/, and pages like login, logout, member, trainer, payment, which suggest it handles different user roles and modules. 
GitHub

Also included: a SQL file gym.sql which likely sets up the database schema or initial data for the system. 
GitHub

🔍 Key features & modules (inferred)

User authentication: Presence of login, logout pages → implies users must log in and log out.

Member management: The member page/folder suggests a section for managing gym members (add/edit/delete/search).

Trainer management: Similarly, the trainer folder indicates a module for managing trainers.

Payments/payments management: The payment folder suggests tracking payments, perhaps membership fees.

Dashboard or home page: The home folder/page suggests a landing/dashboard page after login.

Components and classes: The use of classes/ means the developer structured the code in object-oriented (or at least modular) PHP to manage data and actions; components/ likely handles reusable UI elements (nav bar, footer, etc.).

Styling and images: With style/ and images/ directories, it shows UI/UX consideration — is not purely backend.

🎯 Purpose & Value

The system is designed to help a gym or fitness centre manage operations like member enrolment, trainer assignments, and payment tracking in one unified interface.

It simplifies administrative tasks: instead of spreadsheets or manual logs, you have a web system.

It enables better record-keeping: member status, trainer data, payments, etc., are all organised.

Good for small/medium gyms: If you run a gym, this system could form the basis of your digital admin platform (and you'd customise further).

For learning: For developers, this is a great project to inspect — shows how to build a real-world CRUD web application with PHP, database, user roles.

✅ Strengths

Clear modular structure (classes, components) which aids maintainability.

Use of standard web stack (PHP/HTML/CSS) which is widely supported and easy to deploy.

Presence of database schema (gym.sql) means it’s almost ready to be set up and used by others.

It addresses a real-world domain (gym management) — so it has practical applicability rather than being purely toy/demonstration.


🛠 How to get started (based on what’s available)

Here’s how you might set it up:

Clone the repository:

git clone https://github.com/Sivatheevan1224/Gym_Management_System.git

Set up a web server environment (e.g., Apache with PHP + MySQL) or use something like XAMPP/WAMP.

Import the gym.sql into your MySQL/MariaDB database — this will create the necessary tables/data.

Configure the database connection details in the project (look for a config.php, db.php, or similar in classes/ or components/).

Place the project folder in your web server’s root (or configure virtual host).

Access via browser, go to login page, and explore functions (members, trainers, payments).

Modify as needed: update UI, add roles, adjust modules etc.

