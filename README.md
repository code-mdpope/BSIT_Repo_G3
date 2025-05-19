# IDSC Portal - School Management System

IDSC Portal is a comprehensive web-based school management system for the Integrated Digital School of Computing (IDSC) that allows students, instructors, and administrators to manage various aspects of an educational institution.

## Features

### For Students:
- View personal dashboard with GPA, class schedule, and upcoming assignments
- Access course materials and assignments
- Submit assignments
- View grades
- Check class schedules
- Manage payment information

### For Instructors:
- View teaching dashboard with class statistics
- Manage classes and course materials
- Create and grade assignments
- View class schedules
- Post announcements

### For Administrators:
- Manage users (students, instructors, administrators)
- Create and manage courses and classes
- Handle student enrollments
- Generate reports
- System-wide announcements

## Technologies Used
- PHP 7.4+
- MySQL Database
- HTML5/CSS3
- Tailwind CSS
- JavaScript
- XAMPP (Apache, MySQL, PHP)

## Installation & Setup

### Prerequisites
- XAMPP (or equivalent with PHP 7.4+ and MySQL)
- Web browser

### Setup Instructions

1. **Install XAMPP**:
   - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Start Apache and MySQL services from the XAMPP Control Panel

2. **Clone or download this repository**:
   - Place the files in your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\idsc-portal`)

3. **Database Setup**:
   - Open your web browser and navigate to `http://localhost/idsc-portal/setup.php`
   - This will automatically create the database and populate it with sample data

4. **Access the Portal**:
   - Navigate to `http://localhost/idsc-portal/index.php` in your web browser
   - Use the following login credentials for testing:

     | Role          | User ID        | Password    |
     |---------------|----------------|-------------|
     | Student       | STU-202587     | password123 |
     | Instructor    | INS-2025103    | password123 |
     | Administrator | ADM-2025001    | password123 |

## Project Structure

```
idsc-portal/
├── admin/               # Admin role pages
├── instructor/          # Instructor role pages 
├── student/             # Student role pages
├── Images/              # Image assets
├── index.php            # Login page
├── config.php           # Database and app configuration
├── auth.php             # Authentication functions
├── dashboard_functions.php # Shared functions for dashboards
├── db_setup.sql         # Database schema and sample data
├── setup.php            # Database setup script
└── README.md            # This documentation
```

## Security Notes

- This system uses password hashing for security
- For production use, additional security measures should be implemented
- Change the default credentials before deploying to production

## License

This project is for educational purposes.

## Contact

For support or questions, please contact the system administrator. 