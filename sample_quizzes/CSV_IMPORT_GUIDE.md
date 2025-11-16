# CSV Import Guide for LSQuiz

This guide explains how to format CSV files to import quizzes into LSQuiz.

## CSV File Structure

Your CSV file must follow this exact structure:

### Row 1: Quiz Metadata
The first row contains information about the quiz itself:
```
QUIZ_TITLE,QUIZ_DESCRIPTION,DIFFICULTY,IS_COLLABORATIVE
```

**Fields:**
- **QUIZ_TITLE** (required): The name of your quiz
- **QUIZ_DESCRIPTION** (optional): A description of the quiz
- **DIFFICULTY** (optional): `easy`, `medium`, or `hard` (default: `medium`)
- **IS_COLLABORATIVE** (optional): `true` or `false` (default: `false`)

**Example:**
```
General Knowledge Quiz,Test your knowledge on various topics,medium,false
```

### Row 2: Question Headers
The second row defines the column structure for questions:
```
type,question_text,option1,option2,option3,option4,correct_answer,points
```

**Note:** This row is required but the actual values don't matter - it's just headers.

### Row 3 and Beyond: Questions
Each subsequent row represents one question.

## Question Types

### Multiple Choice (MCQ)

For MCQ questions, fill in all columns:

```
mcq,What is the capital of France?,Paris,London,Berlin,Madrid,Paris,1
```

**Format:**
- `type`: Must be `mcq`
- `question_text`: The question
- `option1`, `option2`, `option3`, `option4`: The four answer choices
- `correct_answer`: Must exactly match one of the options (case-sensitive)
- `points`: Points for this question (default: 1)

**Important:** 
- You must provide at least 2 options
- The `correct_answer` must exactly match one of the options
- You can leave unused option columns empty if you have fewer than 4 options

### Enumeration

For enumeration questions, leave option columns empty and put the answer in the correct_answer column:

```
enum,What is the chemical symbol for gold?,,,,,Au,1
```

**Format:**
- `type`: Must be `enum`
- `question_text`: The question
- `option1`, `option2`, `option3`, `option4`: Leave empty (just commas)
- `correct_answer`: The correct answer text (in column 7)
- `points`: Points for this question (default: 1)

**Example:**
```
enum,What is the chemical symbol for gold?,,,,,Au,1
enum,Who wrote Romeo and Juliet?,,,,,William Shakespeare,2
```

## Complete Example

Here's a complete CSV file example:

```csv
General Knowledge Quiz,Test your knowledge,medium,false
type,question_text,option1,option2,option3,option4,correct_answer,points
mcq,What is the capital of France?,Paris,London,Berlin,Madrid,Paris,1
mcq,Which planet is known as the Red Planet?,Venus,Mars,Jupiter,Saturn,Mars,1
enum,What is the chemical symbol for gold?,,,,,Au,1
enum,Who wrote Romeo and Juliet?,,,,,William Shakespeare,2
```

## Tips for Creating CSV Files

1. **Use Excel or Google Sheets:**
   - Create your quiz in Excel/Sheets
   - Save as CSV (Comma Separated Values)
   - Make sure to use commas, not semicolons

2. **Check Your Formatting:**
   - No extra spaces before/after commas
   - Quotes around text with commas inside: `"Paris, France"`
   - Match case exactly for correct answers

3. **Common Mistakes to Avoid:**
   - ❌ Wrong type: `multiple choice` instead of `mcq`
   - ❌ Correct answer doesn't match options exactly
   - ❌ Missing quiz title in first row
   - ❌ Missing header row (row 2)
   - ❌ Using semicolons instead of commas

4. **Testing:**
   - Start with a small quiz (2-3 questions)
   - Import and check if it works
   - Then create larger quizzes

## Importing Your CSV

1. Log in as a **Host**
2. Go to **Import Quiz** page
3. Select your CSV file
4. Click **Import Quiz**
5. The quiz will be created (initially inactive)
6. Go to the quiz to activate it and make any edits

## Need Help?

If your import fails:
- Check that your CSV uses commas (`,`) as separators
- Verify the first row has the quiz title
- Make sure MCQ correct answers match options exactly
- Check that all required fields are filled

Good luck creating your quizzes! 🎓

