# Engineering MCQ Quiz Application

A comprehensive and interactive Multiple Choice Quiz (MCQ) application designed for testing and improving engineering knowledge. This web-based quiz platform features dynamic question selection, customizable quiz options, preset quizzes, and an admin dashboard for content management.

## Features

### User Features
- **Customizable Quizzes**: Select topics of interest and configure quiz length
- **Preset Quizzes**: Choose from pre-configured quiz collections
- **Time Limits**: Optional timed quizzes to simulate exam conditions
- **Randomized Questions**: Different question order and option arrangement for each attempt
- **Immediate Feedback**: View results and correct answers after completing a quiz
- **Progress Tracking**: Monitor performance over time

### Admin Features
- **Dashboard Interface**: Manage all aspects of the quiz system
- **Question Management**: Add, edit, and delete questions through an intuitive interface
- **Preset Configuration**: Create and modify quiz presets for different skill levels and topics
- **Analytics**: View quiz statistics and user performance metrics
- **Tag Management**: Organize questions by customizable topic tags

## Installation

1. **Prerequisites**
   - PHP 7.4 or higher
   - MySQL database
   - Web server (Apache/Nginx)

2. **Setup Steps**
   ```
   # Clone the repository
   git clone https://github.com/Upendra237/development.git
   
   # Navigate to the project directory
   cd development/websites
   
   # Configure database connection
   # Edit includes/config.php with your database credentials
   ```

3. **Configuration**
   - Open `includes/config.php` and update the following:
     - Admin password
     - Application settings

## Usage

### Taking a Quiz
1. Enter your name on the home page
2. Select quiz topics or choose a preset quiz
3. Configure quiz options (time limit, number of questions)
4. Complete the quiz by answering all questions
5. Review your results and correct answers

### Managing Content (Admin)
1. Access the admin section via `/pages/analytics.php`
2. Enter the admin password
3. Use the dashboard to manage questions, presets, and view analytics
4. Add new questions with multiple options and tag them appropriately
5. Create preset quizzes by selecting topic combinations and difficulty levels

## Project Structure

```
engineering-quiz/
├── api/                # API endpoints
├── assets/             # CSS, JavaScript, and images
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── img/            # Image assets
├── data/               # Database files and samples
├── includes/           # Core PHP functions and utilities
├── pages/              # Application pages
│   ├── analytics.php   # Stats and admin dashboard
│   ├── manage_presets.php  # Preset management
│   ├── manage_quizes.php   # Question management
│   ├── quiz.php        # Quiz interface
│   ├── results.php     # Quiz results page
│   └── select.php      # Quiz options selection
└── index.php           # Application entry point
```

## Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP
- **Database**: JSON
- **Design**: Responsive layout with mobile-first approach
- **Architecture**: Modular code organization with separation of concerns

## Contributing

Contributions to improve the Engineering Quiz application are welcome. Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Screenshots

*[Home Page Screenshot Placeholder]*

*[Quiz Selection Screenshot Placeholder]*

*[Quiz Interface Screenshot Placeholder]*

*[Results Page Screenshot Placeholder]*

*[Admin Dashboard Screenshot Placeholder]*

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Quiz content contributors
- Engineering faculty advisors
- Open-source community for libraries and tools used in this project 