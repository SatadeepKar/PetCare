# PetCare

PetCare is a project designed to assist pet owners, with a primary focus on locating veterinary shops using the Foursquare API. The project currently includes a Veterinary Shops Locator module that helps users find nearby veterinary shops efficiently.

## Features

- **Veterinary Shops Locator**: Quickly locate veterinary shops in your area utilizing the Foursquare API.
- **REST API Backend**: Built with Express.js for fast and reliable server operations.

## Technologies Used

- **Node.js**
- **Express.js**
- **node-fetch**
- **dotenv**
- **Foursquare API**

## Getting Started

### Prerequisites

- PHP 8+ with MySQL/MariaDB (XAMPP recommended for the main app)
- Node.js (v14 or higher) for the Veterinary Shops Locator API

### Database setup (PHP app)

1. Start MySQL and import `PetCare/schema.sql` in phpMyAdmin (creates the `petcare` database and tables).
2. Serve the PHP folder `PetCare/` via Apache (e.g. `http://localhost/PetCare/`).
3. Sign up at `signup.php`, then use the dashboard and features below.

### Main app features (PHP)

- Landing page with login, signup, and vet locator link
- Dashboard: pets, reminders, notes, FullCalendar
- Add pet (multi-step), pet profile, diet tracker, gallery, settings
- Set reminders (general, medication, vet appointment) with recurrence

### Run everything (Windows)

From the repo root, double-click **`start-petcare.bat`** or run:

```powershell
.\start-petcare.ps1
```

This starts:

- **PHP app** at `http://localhost:8888` (landing, dashboard, pets, diet, reminders)
- **Vet Locator API + UI** at `http://localhost:3002`

Requires **PHP** and **Node.js** in your PATH, and **MySQL** running (e.g. XAMPP) with `schema.sql` imported.

Options: `.\start-petcare.ps1 -NoBrowser` to skip opening the browser.

### Vet locator (Node)

### Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/rohit-2059/PetCare.git
    cd PetCare/VetShopsLocator
    ```

2. Install dependencies:
    ```bash
    npm install
    ```

3. Set up environment variables:
    - Create a `.env` file in the `VetShopsLocator` directory.
    - Add your Foursquare API credentials and any other required configuration.

4. Start the backend server:
    ```bash
    npm start
    ```

## Usage

Once the server is running, you can use the provided API endpoints to search for nearby veterinary shops.

## Project Structure

```
PetCare/
└── VetShopsLocator/
    ├── backend_server.js
    ├── package.json
    └── ... (other supporting files)
```

## Contributing

Contributions are welcome! Please fork this repository and submit a pull request for any enhancements or bug fixes.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Author

- [rohit-2059](https://github.com/rohit-2059)

---

> _Note: This README was generated based on available project information. [View more files or details in the GitHub repository.](https://github.com/rohit-2059/PetCare)_