/**
 * Dashboard - Core JavaScript Functionality
 * 
 * This file provides common dashboard functionality used across all admin pages.
 * It includes modal handling, confirmation dialogs, and other shared utilities.
 * 
 * @author Quiz App Developer
 * @version 1.0
 */

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            closeModal(modal);
        }
    });
});

/**
 * Close a modal dialog
 * 
 * @param {HTMLElement} modal - The modal element to close
 */
function closeModal(modal) {
    modal.style.display = 'none';
}

/**
 * Show a modal dialog
 * 
 * @param {HTMLElement} modal - The modal element to show
 */
function showModal(modal) {
    modal.style.display = 'block';
}

/**
 * Confirm an action with the user
 * 
 * Provides a standardized way to get user confirmation before 
 * performing potentially destructive actions like deletions.
 * 
 * @param {string} message - The confirmation message to display
 * @returns {boolean} - True if user confirmed, false otherwise
 */
function confirmAction(message) {
    return confirm(message);
} 