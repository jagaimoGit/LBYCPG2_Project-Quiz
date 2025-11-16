<?php
/**
 * Create/Edit Quiz Page
 * Host-only page for creating, editing, and managing quiz questions
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';

$page_title = 'Create/Edit Quiz - LSQuiz';
require_host();

$current_user = current_user();
$quiz = null;
$quiz_id = $_GET['quiz_id'] ?? null;
$clone_quiz_id = $_GET['clone_quiz_id'] ?? null;
$errors = [];
$success = '';

// Handle quiz cloning
if ($clone_quiz_id) {
    $original_quiz = QuizModel::getById($clone_quiz_id);
    if ($original_quiz && QuizModel::isOwner($clone_quiz_id, $current_user['id'])) {
        $new_access_code = generate_access_code();
        $new_quiz_id = QuizModel::duplicate($clone_quiz_id, $current_user['id'], $new_access_code);
        if ($new_quiz_id) {
            set_flash('success', 'Quiz duplicated successfully!');
            header('Location: create_quiz.php?quiz_id=' . $new_quiz_id);
            exit;
        } else {
            set_flash('error', 'Failed to duplicate quiz.');
        }
    } else {
        set_flash('error', 'Quiz not found or access denied.');
    }
    header('Location: index.php');
    exit;
}

// Load existing quiz if editing
if ($quiz_id) {
    $quiz = QuizModel::getById($quiz_id);
    if (!$quiz || !QuizModel::isOwner($quiz_id, $current_user['id'])) {
        set_flash('error', 'Quiz not found or access denied.');
        header('Location: index.php');
        exit;
    }
}

// Handle quiz details form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quiz'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $theme = 'light'; // Only light theme available
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $is_collaborative = isset($_POST['is_collaborative']);
    $is_active = isset($_POST['is_active']);
    
    if (!require_field($title)) {
        $errors[] = 'Title is required.';
    }
    
    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
        $difficulty = 'medium';
    }
    
    if (empty($errors)) {
        if ($quiz_id && $quiz) {
            // Update existing quiz
            if (QuizModel::update($quiz_id, $title, $description, $theme, $difficulty, $is_collaborative, $is_active)) {
                $success = 'Quiz updated successfully!';
                $quiz = QuizModel::getById($quiz_id); // Reload
            } else {
                $errors[] = 'Failed to update quiz.';
            }
        } else {
            // Create new quiz
            $access_code = generate_access_code();
            $new_quiz_id = QuizModel::create($current_user['id'], $title, $description, $access_code, $theme, $difficulty, $is_collaborative, $is_active);
            if ($new_quiz_id) {
                set_flash('success', 'Quiz created successfully!');
                header('Location: create_quiz.php?quiz_id=' . $new_quiz_id);
                exit;
            } else {
                $errors[] = 'Failed to create quiz.';
            }
        }
    }
}

// Handle question deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question']) && $quiz_id) {
    $question_id = (int)($_POST['question_id'] ?? 0);
    if ($question_id && QuestionModel::delete($question_id)) {
        $success = 'Question deleted successfully!';
    }
}

// Handle question approval (for collaborative quizzes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_question']) && $quiz_id) {
    $question_id = (int)($_POST['question_id'] ?? 0);
    if ($question_id && QuestionModel::approve($question_id)) {
        $success = 'Question approved successfully!';
    }
}

// Handle question update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_question']) && $quiz_id) {
    $question_id = (int)($_POST['question_id'] ?? 0);
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = $_POST['question_type'] ?? 'mcq';
    $points = (int)($_POST['points'] ?? 1);
    
    if (!require_field($question_text)) {
        $errors[] = 'Question text is required.';
    }
    
    if ($question_type === 'mcq') {
        $options = [];
        $correct_option = '';
        for ($i = 1; $i <= 4; $i++) {
            $option = trim($_POST["option_$i"] ?? '');
            if ($option) {
                $options[] = $option;
                if (isset($_POST['correct_option']) && $_POST['correct_option'] == $i) {
                    $correct_option = $option;
                }
            }
        }
        
        if (count($options) < 2) {
            $errors[] = 'At least 2 options are required for MCQ.';
        } elseif (empty($correct_option)) {
            $errors[] = 'Please select a correct option.';
        } else {
            $options_json = json_encode($options);
            if (QuestionModel::update($question_id, $question_text, $options_json, $correct_option, $points, $question_type)) {
                $success = 'Question updated successfully!';
                // Reload page to show updated question
                header('Location: create_quiz.php?quiz_id=' . $quiz_id);
                exit;
            } else {
                $errors[] = 'Failed to update question.';
            }
        }
    } elseif ($question_type === 'enum' || $question_type === 'identification') {
        $correct_answer = trim($_POST['correct_answer'] ?? '');
        if (empty($correct_answer)) {
            $correct_answer = trim($_POST['identification_answer'] ?? '');
        }
        if (!require_field($correct_answer)) {
            $errors[] = 'Correct answer is required.';
        } else {
            if (QuestionModel::update($question_id, $question_text, null, $correct_answer, $points, $question_type)) {
                $success = 'Question updated successfully!';
                // Reload page to show updated question
                header('Location: create_quiz.php?quiz_id=' . $quiz_id);
                exit;
            } else {
                $errors[] = 'Failed to update question.';
            }
        }
    }
}

// Handle question form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_question']) && $quiz_id) {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = $_POST['question_type'] ?? 'mcq';
    $points = (int)($_POST['points'] ?? 1);
    
    if (!require_field($question_text)) {
        $errors[] = 'Question text is required.';
    }
    
    if ($question_type === 'mcq') {
        $options = [];
        $correct_option = '';
        for ($i = 1; $i <= 4; $i++) {
            $option = trim($_POST["option_$i"] ?? '');
            if ($option) {
                $options[] = $option;
                if (isset($_POST['correct_option']) && $_POST['correct_option'] == $i) {
                    $correct_option = $option;
                }
            }
        }
        
        if (count($options) < 2) {
            $errors[] = 'At least 2 options are required for MCQ.';
        } elseif (empty($correct_option)) {
            $errors[] = 'Please select a correct option.';
        } else {
            $options_json = json_encode($options);
            if (QuestionModel::create($quiz_id, $current_user['id'], 'mcq', $question_text, $options_json, $correct_option, $points, true)) {
                $success = 'Question added successfully!';
            } else {
                $errors[] = 'Failed to add question.';
            }
        }
    } elseif ($question_type === 'enum') {
        $correct_answer = trim($_POST['correct_answer'] ?? '');
        if (!require_field($correct_answer)) {
            $errors[] = 'Correct answer is required for enumeration.';
        } else {
            if (QuestionModel::create($quiz_id, $current_user['id'], 'enum', $question_text, null, $correct_answer, $points, true)) {
                $success = 'Question added successfully!';
            } else {
                $errors[] = 'Failed to add question.';
            }
        }
    } elseif ($question_type === 'identification') {
        $correct_answer = trim($_POST['identification_answer'] ?? '');
        if (!require_field($correct_answer)) {
            $errors[] = 'Correct answer is required for identification.';
        } else {
            if (QuestionModel::create($quiz_id, $current_user['id'], 'identification', $question_text, null, $correct_answer, $points, true)) {
                $success = 'Question added successfully!';
            } else {
                $errors[] = 'Failed to add question.';
            }
        }
    }
}

// Load questions for this quiz
$questions = [];
$pending_questions = [];

if ($quiz_id) {
    $questions = QuestionModel::getByQuiz($quiz_id, false);
    $pending_questions = array_filter($questions, function($q) {
        return $q['is_approved'] == 0;
    });
    $questions = array_filter($questions, function($q) {
        return $q['is_approved'] == 1;
    });
}
?>
<div class="container">
    <h1><?php echo $quiz ? 'Edit Quiz' : 'Create New Quiz'; ?></h1>
    
    <?php if (!empty($errors)): ?>
        <div class="flash flash-error">
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="flash flash-success"><?php echo e($success); ?></div>
    <?php endif; ?>
    
    <!-- Quiz Details Form -->
    <div class="card">
        <div class="card-header">
            <h2>Quiz Details</h2>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="save_quiz" value="1">
            
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required value="<?php echo e($quiz['title'] ?? $_POST['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo e($quiz['description'] ?? $_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="difficulty">Difficulty *</label>
                <select id="difficulty" name="difficulty" required>
                    <option value="easy" <?php echo (($quiz['difficulty'] ?? $_POST['difficulty'] ?? 'medium') === 'easy') ? 'selected' : ''; ?>>Easy</option>
                    <option value="medium" <?php echo (($quiz['difficulty'] ?? $_POST['difficulty'] ?? 'medium') === 'medium') ? 'selected' : ''; ?>>Medium</option>
                    <option value="hard" <?php echo (($quiz['difficulty'] ?? $_POST['difficulty'] ?? 'medium') === 'hard') ? 'selected' : ''; ?>>Hard</option>
                </select>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="is_collaborative" value="1" <?php echo (($quiz['is_collaborative'] ?? 0) || isset($_POST['is_collaborative'])) ? 'checked' : ''; ?>>
                        Allow participants to add questions (Collaborative)
                    </label>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo (($quiz['is_active'] ?? 1) || isset($_POST['is_active'])) ? 'checked' : ''; ?>>
                        Active (visible to participants)
                    </label>
                </div>
            </div>
            
            <?php if ($quiz && $quiz['access_code']): ?>
                <div class="form-group">
                    <p><strong>Access Code:</strong> <?php echo e($quiz['access_code']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><?php echo $quiz ? 'Update Quiz' : 'Create Quiz'; ?></button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php if ($quiz_id): ?>
        <!-- Question Management -->
        <div class="card mt-3">
            <div class="card-header">
                <h2>Questions</h2>
            </div>
            
            <!-- Pending Questions (for collaborative quizzes) -->
            <?php if (!empty($pending_questions)): ?>
                <h3>Pending Approval</h3>
                <?php foreach ($pending_questions as $q): ?>
                    <div class="question-item">
                        <p><strong><?php echo e($q['question_text']); ?></strong></p>
                        <p>Type: <?php echo strtoupper($q['type']); ?></p>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this question?');">
                            <input type="hidden" name="approve_question" value="1">
                            <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                            <button type="submit" class="btn btn-small btn-success">Approve</button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this question?');">
                            <input type="hidden" name="delete_question" value="1">
                            <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                            <button type="submit" class="btn btn-small btn-danger">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Existing Questions -->
            <?php if (!empty($questions)): ?>
                <h3>Approved Questions</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Type</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td><?php echo e($q['question_text']); ?></td>
                                <td><?php echo strtoupper($q['type']); ?></td>
                                <td><?php echo $q['points']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-small btn-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($q), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this question?');">
                                        <input type="hidden" name="delete_question" value="1">
                                        <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Add New Question Form -->
                <h3>Add New Question</h3>
                <form method="POST" action="">
                    <input type="hidden" name="save_question" value="1">
                    
                    <div class="form-group">
                        <label for="question_text">Question Text *</label>
                        <textarea id="question_text" name="question_text" required><?php echo e($_POST['question_text'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_type">Question Type *</label>
                        <select id="question_type" name="question_type" required onchange="toggleQuestionType()">
                            <option value="mcq">Multiple Choice (MCQ)</option>
                            <option value="enum">Enumeration</option>
                            <option value="identification">Identification</option>
                        </select>
                    </div>
                    
                    <div id="mcq_options">
                        <div class="form-group">
                            <label>Options *</label>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <input type="radio" name="correct_option" value="<?php echo $i; ?>" required>
                                    <input type="text" name="option_<?php echo $i; ?>" placeholder="Option <?php echo $i; ?>" style="margin-left: 0.5rem; flex: 1;">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div id="enum_options" style="display: none;">
                        <div class="form-group">
                            <label for="correct_answer">Correct Answer *</label>
                            <input type="text" id="correct_answer" name="correct_answer" placeholder="Enter correct answer">
                        </div>
                    </div>
                    
                    <div id="identification_options" style="display: none;">
                        <div class="form-group">
                            <label for="identification_answer">Correct Answer *</label>
                            <input type="text" id="identification_answer" name="identification_answer" placeholder="Enter correct answer">
                            <small style="color: #666;">Identification questions accept text input answers (case-insensitive)</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="points">Points</label>
                        <input type="number" id="points" name="points" value="1" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Add Question</button>
                    </div>
                </form>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Question Modal -->
<div id="editQuestionModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeEditModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Question</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="" id="editQuestionForm">
            <input type="hidden" name="update_question" value="1">
            <input type="hidden" name="question_id" id="modal_question_id">
            
            <div class="form-group">
                <label for="modal_question_text">Question Text *</label>
                <textarea id="modal_question_text" name="question_text" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="modal_question_type">Question Type *</label>
                <select id="modal_question_type" name="question_type" required onchange="toggleModalQuestionType()">
                    <option value="mcq">Multiple Choice (MCQ)</option>
                    <option value="enum">Enumeration</option>
                    <option value="identification">Identification</option>
                </select>
            </div>
            
            <div id="modal_mcq_options" style="display: none;">
                <div class="form-group">
                    <label>Options *</label>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <input type="radio" name="correct_option" value="<?php echo $i; ?>" id="modal_option_radio_<?php echo $i; ?>">
                            <input type="text" name="option_<?php echo $i; ?>" id="modal_option_<?php echo $i; ?>" placeholder="Option <?php echo $i; ?>" style="margin-left: 0.5rem; flex: 1;">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div id="modal_enum_options" style="display: none;">
                <div class="form-group">
                    <label for="modal_correct_answer">Correct Answer *</label>
                    <input type="text" id="modal_correct_answer" name="correct_answer" placeholder="Enter correct answer">
                </div>
            </div>
            
            <div id="modal_identification_options" style="display: none;">
                <div class="form-group">
                    <label for="modal_identification_answer">Correct Answer *</label>
                    <input type="text" id="modal_identification_answer" name="identification_answer" placeholder="Enter correct answer">
                    <small style="color: #666;">Identification questions accept text input answers (case-insensitive)</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="modal_points">Points</label>
                <input type="number" id="modal_points" name="points" min="1" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Question</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleQuestionType() {
    const type = document.getElementById('question_type').value;
    const mcqDiv = document.getElementById('mcq_options');
    const enumDiv = document.getElementById('enum_options');
    const identDiv = document.getElementById('identification_options');
    
    // Hide all first
    mcqDiv.style.display = 'none';
    enumDiv.style.display = 'none';
    identDiv.style.display = 'none';
    
    // Remove all required attributes
    const correctAnswer = document.getElementById('correct_answer');
    const identAnswer = document.getElementById('identification_answer');
    if (correctAnswer) correctAnswer.removeAttribute('required');
    if (identAnswer) identAnswer.removeAttribute('required');
    document.querySelectorAll('input[name^="option_"]').forEach(input => {
        input.removeAttribute('required');
    });
    document.querySelectorAll('input[name="correct_option"]').forEach(radio => {
        radio.removeAttribute('required');
    });
    
    if (type === 'mcq') {
        mcqDiv.style.display = 'block';
        document.querySelectorAll('#mcq_options input[name^="option_"]').forEach(input => {
            input.setAttribute('required', 'required');
        });
    } else if (type === 'enum') {
        enumDiv.style.display = 'block';
        if (correctAnswer) correctAnswer.setAttribute('required', 'required');
    } else if (type === 'identification') {
        identDiv.style.display = 'block';
        if (identAnswer) identAnswer.setAttribute('required', 'required');
    }
}

function openEditModal(question) {
    // Populate form fields
    document.getElementById('modal_question_id').value = question.id;
    document.getElementById('modal_question_text').value = question.question_text;
    document.getElementById('modal_question_type').value = question.type;
    document.getElementById('modal_points').value = question.points;
    
    // Clear previous values
    for (let i = 1; i <= 4; i++) {
        document.getElementById('modal_option_' + i).value = '';
        document.getElementById('modal_option_radio_' + i).checked = false;
        document.getElementById('modal_option_' + i).removeAttribute('required');
    }
    document.getElementById('modal_correct_answer').value = '';
    document.getElementById('modal_identification_answer').value = '';
    document.getElementById('modal_correct_answer').removeAttribute('required');
    document.getElementById('modal_identification_answer').removeAttribute('required');
    
    // Populate based on question type
    if (question.type === 'mcq' && question.options_json) {
        try {
            const options = JSON.parse(question.options_json);
            options.forEach((opt, index) => {
                const i = index + 1;
                document.getElementById('modal_option_' + i).value = opt;
                if (opt === question.correct_answer) {
                    document.getElementById('modal_option_radio_' + i).checked = true;
                }
            });
        } catch (e) {
            console.error('Error parsing options:', e);
        }
    } else if (question.type === 'enum') {
        document.getElementById('modal_correct_answer').value = question.correct_answer;
    } else if (question.type === 'identification') {
        document.getElementById('modal_identification_answer').value = question.correct_answer;
    }
    
    // Show appropriate fields
    toggleModalQuestionType();
    
    // Show modal
    document.getElementById('editQuestionModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editQuestionModal').style.display = 'none';
}

function toggleModalQuestionType() {
    const type = document.getElementById('modal_question_type').value;
    const mcqDiv = document.getElementById('modal_mcq_options');
    const enumDiv = document.getElementById('modal_enum_options');
    const identDiv = document.getElementById('modal_identification_options');
    
    // Hide all first
    mcqDiv.style.display = 'none';
    enumDiv.style.display = 'none';
    identDiv.style.display = 'none';
    
    // Remove all required attributes
    const correctAnswer = document.getElementById('modal_correct_answer');
    const identAnswer = document.getElementById('modal_identification_answer');
    if (correctAnswer) correctAnswer.removeAttribute('required');
    if (identAnswer) identAnswer.removeAttribute('required');
    for (let i = 1; i <= 4; i++) {
        document.getElementById('modal_option_' + i).removeAttribute('required');
        document.getElementById('modal_option_radio_' + i).removeAttribute('required');
    }
    
    if (type === 'mcq') {
        mcqDiv.style.display = 'block';
        // Make all option inputs required for MCQ
        for (let i = 1; i <= 4; i++) {
            const input = document.getElementById('modal_option_' + i);
            input.setAttribute('required', 'required');
        }
    } else if (type === 'enum') {
        enumDiv.style.display = 'block';
        if (correctAnswer) correctAnswer.setAttribute('required', 'required');
    } else if (type === 'identification') {
        identDiv.style.display = 'block';
        if (identAnswer) identAnswer.setAttribute('required', 'required');
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
