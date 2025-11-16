# Sample Quizzes for LSQuiz

This folder contains sample quiz files that you can import into LSQuiz to get started.

## Available Sample Quizzes

### 1. General Knowledge Quiz (`general_knowledge.json`)
- **Questions:** 8 questions (mix of MCQ and Enumeration)
- **Topics:** Geography, Science, Literature, Math
- **Difficulty:** Easy to Medium

### 2. PHP Basics Quiz (`php_basics.json`)
- **Questions:** 8 questions (mix of MCQ and Enumeration)
- **Topics:** PHP programming fundamentals
- **Difficulty:** Beginner to Intermediate

### 3. Web Development Fundamentals (`web_development.json`)
- **Questions:** 8 questions (mix of MCQ and Enumeration)
- **Topics:** HTML, CSS, JavaScript, Web Development
- **Difficulty:** Beginner
- **Note:** This quiz is set as collaborative (participants can add questions)

## CSV Template

### `quiz_template.csv`
A ready-to-use CSV template showing the correct format. You can:
1. Open it in Excel or Google Sheets
2. Edit the questions
3. Save as CSV
4. Import into LSQuiz

## How to Import

### Importing JSON Files:
1. Log in as a **Host**
2. Navigate to **Import Quiz** (from the main page)
3. Click **Choose File** and select one of the `.json` files
4. Click **Import Quiz**
5. The quiz will be created (initially inactive)
6. Go to the quiz edit page to activate it

### Importing CSV Files:
1. Log in as a **Host**
2. Navigate to **Import Quiz**
3. Click **Choose File** and select a `.csv` file
4. Click **Import Quiz**
5. Follow the same steps as JSON import

## CSV Format Guide

See `CSV_IMPORT_GUIDE.md` for detailed instructions on creating your own CSV files.

## Quick Start

1. **Try importing a sample quiz:**
   - Import `general_knowledge.json` to see how it works
   - Activate it and take the quiz as a participant

2. **Create your own quiz:**
   - Use `quiz_template.csv` as a starting point
   - Edit it in Excel/Google Sheets
   - Import your custom quiz

3. **Learn the format:**
   - Open the JSON files in a text editor to see the structure
   - Read `CSV_IMPORT_GUIDE.md` for CSV formatting rules

## Notes

- All imported quizzes start as **inactive** - you need to activate them manually
- You can edit imported quizzes after importing
- CSV files are easier to create in Excel/Sheets
- JSON files are better for programmatic generation

Happy quizzing! 🎯

