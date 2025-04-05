/**
 * Common management functionality for the quiz application
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
 * @param {HTMLElement} modal - The modal element to close
 */
function closeModal(modal) {
    modal.style.display = 'none';
}

/**
 * Show a modal dialog
 * @param {HTMLElement} modal - The modal element to show
 */
function showModal(modal) {
    modal.style.display = 'block';
}

/**
 * Confirm an action with the user
 * @param {string} message - The confirmation message to display
 * @returns {boolean} - Whether the user confirmed the action
 */
function confirmAction(message) {
    return confirm(message);
} 