# Inventory Management System

A web-based inventory management system for tracking consumable materials, managing orders, and monitoring stock levels.

## Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/jnui/inventory-management-system.git
   cd inventory-management-system
   ```

2. Set up the database configuration:
   - Copy `db_connection.template.php` to `db_connection.php`
   - Update the database credentials in `db_connection.php`

3. Create a `.env` file in the root directory with the following content:
   ```
   # Database Configuration
   DB_HOST=your_host
   DB_NAME=your_database_name
   DB_USER=your_username
   DB_PASS=your_password
   ```

4. Set up the database:
   - Create a new MySQL database
   - Import the schema from `smccontr_inventory.sql`

5. Configure your web server:
   - Point your web server to the project directory
   - Ensure PHP has write permissions for the `scripts` directory

## Features

- Track consumable materials inventory
- Monitor stock levels and reorder thresholds as well as ideal qty amounts
- Manage orders and order history
- Natural language inventory updates (experimental)
- Print and export inventory reports
- Create POs that can later be compared when receiving shipments
- Bulk recieve using POs

## Security Notes

- Never commit sensitive information (API keys, passwords) to the repository
- Always use environment variables for sensitive data
- Keep your `.env` file secure and never commit it to version control

## Documentation

The system includes detailed instruction manuals:

1. [Entering New Materials](manual_new_materials.html)
2. [Entering Stock Updates](manual_stock_updates.html)
3. [Using the Consumable Materials Page](manual_consumable_materials.html)

## Technologies Used

- PHP
- MySQL
- JavaScript
- Bootstrap
- DataTables
- HTML/CSS

## Installation

1. Clone this repository
2. Import the database schema from `smccontr_inventory.sql`
3. Configure database connection in `db_connection.php`
4. Access the application through your web server

## License

[MIT License](LICENSE)

## Error Logging

Error logging is configured in `db_connection.php`. By default, it is set to log only critical PHP errors to the `error_log` file in the root directory, and to not display errors to the user.

During development, some files had explicit logging statements to debug database queries and other data. These have been commented out to prevent the `error_log` file from becoming too large. If you need to re-enable this verbose logging for debugging purposes, you can find the commented-out `error_log()` calls in the following files:

- `inventory_entry.php`
- `consumable_list.php` 
