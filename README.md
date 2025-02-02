### [Live Link](http://event-management.fisheryhut.com)

## Features
- Custom MVC (Laravel-like) Project setup from scratch with Bootstrap
- User registration and login with proper validations
- Event CRUD
- Event entry registration
- User management (Only admin can manage users)
- User role (User & Admin)
- Pagination and search

##### Note: Certain features are not yet properly finished like CSV download and others as doing MVC from scratch took longer than expected. Will be done in upcoming commits. 

# Setup Guide 
### ENV Setup (For MySQL)
- DB_HOST=production
- DB_DATABASE=5000
- DB_USERNAME=
- DB_PASSWORD=
- DB_PORT=

### Install dependencies
```php
  composer install
```
### Create MySQL database from database.sql file
```sql
  mysql -u root -p
  use database_name;
  sourse database.sql;
  exti;
```
