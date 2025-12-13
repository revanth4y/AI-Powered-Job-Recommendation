<?php
session_start();

// Simple gate: require company login
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'company') {
    http_response_code(302);
    header('Location: /job/pages/assignment-login-company.php');
    exit;
}

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($assignment_id <= 0) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><body><p>Missing or invalid assignment id.</p></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Assignment Questions</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/assignments.css" />
    <style>
        .container { max-width: 900px; margin: 24px auto; padding: 16px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-top: 16px; }
        .question-item { border-bottom: 1px solid #eee; padding: 12px 0; }
        .question-item:last-child { border-bottom: none; }
        .options { margin-top: 8px; }
        .options li { margin-left: 18px; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e5e7eb; color: #111827; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-grid .full { grid-column: 1 / -1; }
        .error { color: #b91c1c; font-size: 0.9em; }
        .success { color: #166534; font-size: 0.9em; }
    </style>
    <script>
        const assignmentId = <?php echo json_encode($assignment_id, JSON_UNESCAPED_SLASHES); ?>;

        async function loadQuestions() {
            const container = document.getElementById('questions-list');
            container.innerHTML = '<p>Loading questions...</p>';
            try {
                const res = await fetch(`/job/api/jobs/assignment_questions.php?assignment_id=${assignmentId}`, { credentials: 'same-origin' });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load questions');
                const questions = data.questions || [];
                if (questions.length === 0) {
                    container.innerHTML = '<p>No questions yet. Add your first question below.</p>';
                    return;
                }
                let html = '';
                for (const q of questions) {
                    html += `<div class="question-item">
                        <div><strong>#${q.order_index ?? 0}</strong> ${escapeHtml(q.question_text)} <small>(${q.question_type})</small></div>
                        ${q.options && q.options.length ? `<div class="options"><strong>Options:</strong><ul>${q.options.map(o => `<li>${escapeHtml(o.option_text)}</li>`).join('')}</ul></div>` : ''}
                    </div>`;
                }
                container.innerHTML = html;
            } catch (err) {
                console.error('Error loading questions', err);
                container.innerHTML = `<p class="error">Error loading questions: ${err.message}</p>`;
            }
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function onTypeChange(sel) {
            const optionsBox = document.getElementById('options-box');
            optionsBox.style.display = sel.value === 'multiple_choice' ? 'block' : 'none';
        }

        async function submitQuestion(e) {
            e.preventDefault();
            const msg = document.getElementById('submit-msg');
            msg.textContent = '';
            try {
                const fd = new FormData(document.getElementById('question-form'));
                fd.append('assignment_id', String(assignmentId));
                const res = await fetch('/job/api/jobs/assignment_questions.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (res.ok && data.success) {
                    msg.className = 'success';
                    msg.textContent = 'Question added';
                    (document.getElementById('question_text')).value = '';
                    (document.getElementById('order_index')).value = '';
                    (document.getElementById('question_type')).value = 'text';
                    (document.getElementById('options')).value = '';
                    onTypeChange(document.getElementById('question_type'));
                    loadQuestions();
                } else {
                    msg.className = 'error';
                    msg.textContent = data.message || `Failed (${res.status})`;
                }
            } catch (err) {
                console.error('Create question error', err);
                msg.className = 'error';
                msg.textContent = err.message;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadQuestions();
            onTypeChange(document.getElementById('question_type'));
        });
    </script>
    </head>
<body>
    <div class="container">
        <div class="header">
            <h2>Assignment #<?php echo htmlspecialchars((string)$assignment_id); ?> — Manage Questions</h2>
            <a class="btn btn-secondary" href="../dashboard/company.php">Back to Dashboard</a>
        </div>

        <div class="card">
            <h3>Existing Questions</h3>
            <div id="questions-list"></div>
        </div>

        <div class="card">
            <h3>Add a Question</h3>
            <form id="question-form" onsubmit="submitQuestion(event)">
                <div class="form-grid">
                    <div class="full">
                        <label>Question Text *</label>
                        <textarea id="question_text" name="question_text" class="form-control" rows="3" required placeholder="Enter your question..."></textarea>
                    </div>
                    <div>
                        <label>Question Type *</label>
                        <select id="question_type" name="question_type" class="form-control" onchange="onTypeChange(this)">
                            <option value="text">Text Answer</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="file_upload">File Upload</option>
                        </select>
                    </div>
                    <div>
                        <label>Order Index</label>
                        <input type="number" id="order_index" name="order_index" class="form-control" placeholder="0" />
                    </div>
                </div>
                <div id="options-box" class="full" style="display:none; margin-top:8px;">
                    <label>Options (one per line)</label>
                    <textarea id="options" name="options" class="form-control" rows="4" placeholder="Option A\nOption B\nOption C"></textarea>
                </div>
                <div style="margin-top:12px; display:flex; gap:8px;">
                    <button class="btn btn-primary" type="submit">Add Question</button>
                    <span id="submit-msg"></span>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

