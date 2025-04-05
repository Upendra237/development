/**
 * Preset Management - JavaScript functionality
 * 
 * This file handles the client-side functionality for managing quiz presets:
 * - Opening and populating the edit preset modal
 * - Handling preset editing and form submission
 * - Confirming preset deletion
 * 
 * Works with manage_presets.php and requires dashboard.js for shared functions
 */

// Modal reference and setup
const presetModal = document.getElementById('editModal');
const closeModalButton = document.querySelector('.close');

// Set up close button functionality
if (closeModalButton) {
    closeModalButton.addEventListener('click', function() {
        closeModal(presetModal);
    });
}

/**
 * Open the edit modal for a preset
 * 
 * Populates the edit form with the preset data and displays the modal
 * 
 * @param {Object} preset - The preset data object containing id, name, description, etc.
 */
function openEditModal(preset) {
    // Populate form fields
    document.getElementById('edit_preset_id').value = preset.id;
    document.getElementById('edit_preset_name').value = preset.name;
    document.getElementById('edit_preset_description').value = preset.description || '';
    
    // Set number of questions
    document.getElementById('edit_num_questions').value = preset.num_questions;
    
    // Set time limit
    document.getElementById('edit_time_limit').value = preset.time_limit || 'none';
    
    // Uncheck all tags first
    document.querySelectorAll('#edit_tags_selector input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    
    // Check the relevant tags
    preset.tags.forEach(tag => {
        const checkbox = document.getElementById('edit_tag_' + tag);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    // Show modal
    showModal(presetModal);
}

/**
 * Close the edit modal
 * 
 * Wrapper function for the closeModal function from dashboard.js
 */
function closeEditModal() {
    closeModal(presetModal);
}

/**
 * Confirm before deleting a preset
 * 
 * Shows a confirmation dialog to prevent accidental deletions
 * 
 * @returns {boolean} - True if user confirmed deletion, false otherwise
 */
function confirmPresetDelete() {
    return confirmAction('Are you sure you want to delete this preset?');
}

// Add event listeners for delete forms when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Find all forms with action and onsubmit attributes (likely delete forms)
    const deleteForms = document.querySelectorAll('form[action][onsubmit]');
    
    // Replace the inline onsubmit handler with our function
    deleteForms.forEach(form => {
        if (form.getAttribute('onsubmit').includes('confirm')) {
            form.onsubmit = function() {
                return confirmPresetDelete();
            };
        }
    });
}); 