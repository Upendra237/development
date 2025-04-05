/**
 * Question Management - JavaScript functionality
 * 
 * This file handles the client-side functionality for managing quiz questions:
 * - Opening and populating the edit question modal
 * - Handling question editing and form submission
 * - Confirming question deletion
 * 
 * Works with manage_quizes.php and requires dashboard.js for shared functions
 */

// Modal reference and setup
const questionModal = document.getElementById('editModal');
const closeModalButton = document.querySelector('.close');

// Set up close button functionality
if (closeModalButton) {
    closeModalButton.addEventListener('click', function() {
        closeModal(questionModal);
    });
}

/**
 * Open the edit modal for a question
 * 
 * Populates the edit form with question data and displays the modal
 * 
 * @param {Object} question - The question data object containing id, question text, options, etc.
 */
function openEditModal(question) {
    // Populate form fields
    document.getElementById('edit_question_id').value = question.id;
    document.getElementById('edit_question').value = question.question;
    document.getElementById('edit_explanation').value = question.explanation || '';
    
    // Set options and mark the correct answer
    for (let i = 0; i < 4; i++) {
        document.getElementById('edit_option_' + i).value = question.options[i] || '';
        document.getElementById('edit_correct_' + i).checked = (i === question.correct);
    }
    
    // Uncheck all tags first
    document.querySelectorAll('#edit_tags_selector input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    
    // Check the relevant tags
    question.tags.forEach(tag => {
        const checkbox = document.getElementById('edit_tag_' + tag);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    // Show modal
    showModal(questionModal);
}

/**
 * Close the edit modal
 * 
 * Wrapper function for the closeModal function from dashboard.js
 */
function closeEditModal() {
    closeModal(questionModal);
}

/**
 * Confirm before deleting a question
 * 
 * Shows a confirmation dialog to prevent accidental deletions
 * 
 * @returns {boolean} - True if user confirmed deletion, false otherwise
 */
function confirmQuestionDelete() {
    return confirmAction('Are you sure you want to delete this question?');
}

// Add event listeners for delete forms when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Find all forms with action and onsubmit attributes (likely delete forms)
    const deleteForms = document.querySelectorAll('form[action][onsubmit]');
    
    // Replace the inline onsubmit handler with our function
    deleteForms.forEach(form => {
        if (form.getAttribute('onsubmit').includes('confirm')) {
            form.onsubmit = function() {
                return confirmQuestionDelete();
            };
        }
    });
}); 