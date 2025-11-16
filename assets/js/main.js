/**
 * Main JavaScript File
 * Provides client-side enhancements for LSQuiz
 */

// Confirm destructive actions
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete confirmations
    const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const confirmMessage = form.getAttribute('onsubmit');
            if (confirmMessage && !confirm(confirmMessage.match(/confirm\('([^']+)'\)/)?.[1] || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });
    
    // Add client-side validation hints
    const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('invalid', function(e) {
            if (!input.value.trim()) {
                input.setCustomValidity('This field is required.');
            }
        });
        
        input.addEventListener('input', function() {
            input.setCustomValidity('');
        });
    });
    
    // Auto-dismiss flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.flash');
    flashMessages.forEach(flash => {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.5s';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 500);
        }, 5000);
    });
});

// Helper function for dynamic option fields (if needed in future)
function addOptionField(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const optionCount = container.querySelectorAll('input[type="text"]').length;
    const newOption = document.createElement('div');
    newOption.style.display = 'flex';
    newOption.style.alignItems = 'center';
    newOption.style.marginBottom = '0.5rem';
    newOption.innerHTML = `
        <input type="radio" name="correct_option" value="${optionCount + 1}">
        <input type="text" name="option_${optionCount + 1}" placeholder="Option ${optionCount + 1}" style="margin-left: 0.5rem; flex: 1;">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-small btn-danger" style="margin-left: 0.5rem;">Remove</button>
    `;
    container.appendChild(newOption);
}
