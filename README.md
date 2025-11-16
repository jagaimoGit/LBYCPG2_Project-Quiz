# LSQuiz - Quiz Management System

LSQuiz is a PHP + MySQL web application for creating and managing quizzes. Hosts can create quiz sets with multiple-choice and enumeration questions, and participants can take quizzes at any time.

## Features

- **User Management**: Registration and login for hosts and participants
- **Quiz Creation**: Hosts can create quizzes with MCQ and enumeration questions
- **Theme Support**: Three themes (light, dark, classic) that apply to quiz pages
- **Import/Export**: Export quizzes as JSON and import them back
- **Collaborative Quizzes**: Allow participants to suggest questions (with host approval)
- **Results Dashboard**: View detailed results for participants and aggregated statistics for hosts
- **Access Codes**: Join quizzes using short access codes

## Requirements

- PHP 8.x
- MySQL/MariaDB
- Apache web server (or compatible)
- MySQLi extension enabled

## Installation

1. **Configuration**:
   - Update database credentials in `config/db.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'lsquiz');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

2. **Automatic Database Setup**:
   - The database and tables are automatically created on first use!
   - Just make sure MySQL/MariaDB is running and the credentials in `config/db.php` are correct
   - When you first access any page, the system will:
     - Create the database if it doesn't exist
     - Create all required tables if they don't exist
   - No manual SQL import needed!

3. **Web Server Setup**:
   - Point your web server document root to the `public` directory
   - For XAMPP: Place the project in `htdocs` and access via `http://localhost/php/FinalProject/public/`
   - Or configure Apache virtual host to use `public` as document root

4. **File Permissions**:
   - Ensure PHP can write to session directory
   - No special file permissions needed for this application

## Project Structure

```
FinalProject/
├── assets/
│   ├── css/
│   │   └── styles.css          # Base styles and theme classes
│   └── js/
│       └── main.js             # Client-side enhancements
├── config/
│   └── db.php                   # Database connection
├── includes/
│   ├── auth.php                 # Authentication utilities
│   ├── footer.php               # Shared footer
│   ├── header.php               # Shared header
│   └── helpers.php              # Helper functions
├── models/
│   ├── AttemptModel.php         # Quiz attempt operations
│   ├── QuestionModel.php        # Question operations
│   ├── QuizModel.php            # Quiz operations
│   └── UserModel.php            # User operations
├── public/
│   ├── add_question.php         # Add question (collaborative)
│   ├── create_quiz.php          # Create/edit quiz (host)
│   ├── export_quiz.php          # Export quiz as JSON (host)
│   ├── import_quiz.php          # Import quiz from JSON (host)
│   ├── index.php                # Main page
│   ├── login.php                # Login page
│   ├── logout.php               # Logout handler
│   ├── play_quiz.php            # Take quiz
│   ├── profile.php              # User profile
│   ├── register.php             # Registration page
│   └── results_dashboard.php    # Results view
├── sql/
│   └── schema.sql               # Database schema
└── README.md                    # This file
```

## Usage

### For Hosts

1. **Register/Login** as a host
2. **Create Quiz**: Click "Create Quiz" and fill in quiz details
3. **Add Questions**: After creating, add MCQ or enumeration questions
4. **Activate Quiz**: Check "Active" to make it visible to participants
5. **Export/Import**: Export quizzes as JSON or import existing JSON files
6. **View Results**: See aggregated statistics and participant performance

### For Participants

1. **Register/Login** as a participant
2. **Browse Quizzes**: View all active quizzes on the main page
3. **Join by Code**: Enter an access code to join a specific quiz
4. **Take Quiz**: Answer all questions and submit
5. **View Results**: See your score and question-by-question feedback

## Design Decisions

### Database
- **MySQLi**: Used MySQLi with prepared statements for all database operations
- **Auto-Setup**: Database and tables are automatically created on first use
- **Foreign Keys**: CASCADE deletes ensure data integrity
- **Indexes**: Added on frequently queried columns (quiz_id, user_id, access_code, etc.)

### Authentication
- **Sessions**: PHP sessions for login state
- **Password Hashing**: `password_hash()` and `password_verify()` for secure password storage
- **Role-based Access**: Server-side checks for host-only pages

### Collaborative Questions
- **Approval System**: Participant-submitted questions start with `is_approved = 0`
- **Host Review**: Hosts can approve or delete pending questions in the quiz management page
- **Immediate Inclusion**: Once approved, questions appear in the quiz for all participants

### Multiple Attempts
- **One Active Attempt**: Users can have one incomplete attempt per quiz
- **Retakes Allowed**: After completing a quiz, users can start a new attempt
- **History Preserved**: All completed attempts are saved and viewable

### Themes
- **Quiz-level Themes**: Each quiz has a theme (light, dark, classic)
- **Applied on Play**: Theme is applied to `play_quiz.php` and `results_dashboard.php`
- **CSS Classes**: Theme classes modify background, text, and accent colors

### Security
- **Input Validation**: Server-side validation for all inputs
- **Output Escaping**: `htmlspecialchars()` used via `e()` helper function
- **SQL Injection Prevention**: Prepared statements for all queries
- **Access Control**: Role checks and ownership verification for all operations

## Testing Checklist

- [ ] Register as host and participant
- [ ] Login/logout functionality
- [ ] Host creates quiz with mixed question types
- [ ] Host edits quiz and questions
- [ ] Host exports and imports quiz JSON
- [ ] Host duplicates (recycles) a quiz
- [ ] Participant views available quizzes
- [ ] Participant takes quiz and sees results
- [ ] Participant joins quiz by access code
- [ ] Participant suggests question in collaborative quiz
- [ ] Host approves/rejects participant questions
- [ ] Host views results dashboard with statistics
- [ ] Themes apply correctly to quiz pages
- [ ] Profile editing works (name and password)

## Notes

- The application uses relative paths for assets (CSS/JS)
- Database connection is configured in `config/db.php` - update for your environment
- All user-generated content is escaped before display
- Flash messages auto-dismiss after 5 seconds (JavaScript)
- The system supports unlimited quizzes and questions

## License

This project is created for educational purposes.

