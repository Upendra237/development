/**
 * Quiz Selection Page JavaScript
 * 
 * This file handles the quiz selection interface:
 * - Tag selection and filtering
 * - Preset handling
 * - Form validation
 * - Animations and UI interactions
 */

// DOM Elements
const tagsContainer = document.getElementById('tags-container');
const showMoreTagsBtn = document.getElementById('show-more-tags');
const presetRadios = document.querySelectorAll('input[name="preset"]');
const customOptions = document.getElementById('custom-options');
const tagCheckboxes = document.querySelectorAll('.tag-checkbox');
const questionCountSelect = document.getElementById('num-questions');
const quizOptionsForm = document.getElementById('quiz-options-form');
const presetDetailsContainer = document.getElementById('preset-details');
const startQuizBtn = document.querySelector('.btn-primary');

// Preset data from server
let presets = {};

// Initialize when the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Setup presets handling
    setupPresets();
    
    // Setup show more tags button
    setupShowMoreTags();
    
    // Setup form validation
    setupFormValidation();
    
    // Add animations
    setupAnimations();
    
    // Check for error parameters
    checkForErrors();
    
    // Fetch presets data
    fetchPresets();
});

/**
 * Fetch presets data from server
 * Gets detailed information about available quiz presets
 */
function fetchPresets() {
    fetch('../includes/get_presets.php')
        .then(response => response.json())
        .then(data => {
            presets = data;
        })
        .catch(error => {
            console.error('Error fetching presets:', error);
        });
}

/**
 * Check for error parameters in URL
 * Displays appropriate error messages based on URL parameters
 */
function checkForErrors() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error) {
        let errorMessage = '';
        if (error === 'no_tags') {
            errorMessage = 'Please select at least one topic to start the quiz.';
        } else if (error === 'no_questions') {
            errorMessage = 'No questions found for the selected topics. Please try different topics.';
        }
        
        if (errorMessage) {
            showError(errorMessage);
        }
    }
}

/**
 * Show error message
 * Creates and displays a temporarily visible error message
 * 
 * @param {string} message - The error message to display
 */
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    const selectionContainer = document.querySelector('.selection-container');
    selectionContainer.insertBefore(errorDiv, selectionContainer.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.opacity = '0';
        setTimeout(() => {
            errorDiv.remove();
        }, 500);
    }, 5000);
}

/**
 * Setup presets functionality
 * Handles preset selection and UI updates
 */
function setupPresets() {
    presetRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const presetValue = this.value;
            
            if (presetValue === 'custom') {
                // Enable custom options
                enableCustomOptions(true);
                hidePresetDetails();
                updateButtonText('Start Quiz');
            } else {
                // Disable custom options and set preset values
                enableCustomOptions(false);
                showPresetDetails(presetValue);
                updateButtonText('Start Quiz with Preset');
                
                // Make sure the preset value will be submitted
                console.log('Selected preset:', presetValue);
                
                // Debug - Log all radio buttons
                document.querySelectorAll('input[name="preset"]').forEach(r => {
                    console.log(r.id, r.value, r.checked);
                });
            }
        });
    });
    
    // Initialize with currently selected preset
    const selectedPreset = document.querySelector('input[name="preset"]:checked');
    if (selectedPreset && selectedPreset.value !== 'custom') {
        enableCustomOptions(false);
        showPresetDetails(selectedPreset.value);
        updateButtonText('Start Quiz with Preset');
    }
}

/**
 * Update button text based on selection
 * 
 * @param {string} text - The new button text
 */
function updateButtonText(text) {
    if (startQuizBtn) {
        startQuizBtn.textContent = text;
    }
}

/**
 * Show preset details
 * Displays information about the selected preset
 * 
 * @param {string} presetKey - The key of the selected preset
 */
function showPresetDetails(presetKey) {
    const preset = document.querySelector(`#preset-${presetKey}`);
    if (!preset) return;

    // Build details HTML
    let detailsHTML = '<div class="preset-details-content">';
    
    // Try to get preset name
    const presetLabel = document.querySelector(`label[for="preset-${presetKey}"]`);
    const presetName = presetLabel ? presetLabel.textContent : '';
    
    // Add basic info
    detailsHTML += `<p><strong>${presetName}</strong> preset selected</p>`;
    
    // Handle Random preset
    if (presetKey === 'random') {
        detailsHTML += `<p>Difficulty: Mixed</p>`;
        detailsHTML += `<p>Questions: Random (5-20)</p>`;
        detailsHTML += `<p>Random selection of topics and questions for a surprise quiz experience!</p>`;
        
        // Add some sample tags
        detailsHTML += '<p>Possible topics:</p>';
        detailsHTML += '<div class="tags-list">';
        const allTopics = ['computer', 'electronics', 'programming', 'hardware', 'communication'];
        allTopics.forEach(tag => {
            detailsHTML += `<span class="tag-pill">${tag}</span>`;
        });
        detailsHTML += '</div>';
    }
    // If we have additional data from API
    else if (presets[presetKey]) {
        const presetData = presets[presetKey];
        
        if (presetData.difficulty) {
            detailsHTML += `<p>Difficulty: ${presetData.difficulty}</p>`;
        }
        
        if (presetData.num_questions) {
            detailsHTML += `<p>Questions: ${presetData.num_questions}</p>`;
        }
        
        if (presetData.description) {
            detailsHTML += `<p>${presetData.description}</p>`;
        }
        
        if (presetData.tags && presetData.tags.length > 0) {
            detailsHTML += '<p>Topics:</p>';
            detailsHTML += '<div class="tags-list">';
            presetData.tags.forEach(tag => {
                detailsHTML += `<span class="tag-pill">${tag}</span>`;
            });
            detailsHTML += '</div>';
        }
    }
    
    detailsHTML += '</div>';
    
    // Set content and show
    presetDetailsContainer.innerHTML = detailsHTML;
    presetDetailsContainer.classList.add('visible');
}

/**
 * Hide preset details
 * Hides the preset details container
 */
function hidePresetDetails() {
    presetDetailsContainer.classList.remove('visible');
}

/**
 * Enable or disable custom options
 * 
 * @param {boolean} enable - Whether to enable or disable the custom options
 */
function enableCustomOptions(enable) {
    const inputs = customOptions.querySelectorAll('input, select, button');
    inputs.forEach(input => {
        input.disabled = !enable;
    });
    
    if (enable) {
        customOptions.classList.remove('disabled');
    } else {
        customOptions.classList.add('disabled');
    }
}

/**
 * Setup show more tags button
 * Toggles the visibility of all tags
 */
function setupShowMoreTags() {
    if (showMoreTagsBtn) {
        showMoreTagsBtn.addEventListener('click', function() {
            if (tagsContainer.classList.contains('expanded')) {
                // Collapse
                tagsContainer.classList.remove('expanded');
                this.textContent = 'More';
                this.classList.remove('expanded');
            } else {
                // Expand
                tagsContainer.classList.add('expanded');
                this.textContent = 'Less';
                this.classList.add('expanded');
            }
        });
    }
}

/**
 * Setup form validation
 * Validates form inputs before submission and prevents submission if validation fails
 */
function setupFormValidation() {
    quizOptionsForm.addEventListener('submit', function(e) {
        // Debug - Log form data before submission
        logFormData();
        
        // Get the selected preset
        const selectedPreset = document.querySelector('input[name="preset"]:checked');
        
        // If custom preset is selected or no preset is selected, validate tags
        if (selectedPreset && selectedPreset.value === 'custom') {
            // Check if we have at least one tag selected for custom preset
            const selectedTags = document.querySelectorAll('input[name="tags[]"]:checked');
            const selectedTagsCount = selectedTags.length;
            
            if (selectedTagsCount === 0) {
                e.preventDefault();
                showError('Please select at least one topic');
                return false;
            }
        } else if (selectedPreset) {
            // A non-custom preset is selected, no need to validate tags
            console.log('Non-custom preset selected:', selectedPreset.value);
        }

        // Additional validation for random selection
        const numQuestionsSelect = document.getElementById('num-questions');
        if (numQuestionsSelect.value === 'random') {
            console.log('Random question count selected');
            // No need to change the value - the server will generate a random number
        }

        // Add animation to indicate form submission
        document.querySelector('.selection-container').classList.add('fade-out');
        
        // Show loading indicator
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.textContent = 'Preparing your quiz...';
        document.body.appendChild(loadingIndicator);
        
        return true;
    });
}

/**
 * Helper function to log all form data for debugging
 */
function logFormData() {
    console.log('Form submission data:');
    
    // Log selected preset
    const selectedPreset = document.querySelector('input[name="preset"]:checked');
    console.log('Selected preset:', selectedPreset ? selectedPreset.value : 'none');
    
    // Log selected tags
    const selectedTags = document.querySelectorAll('input[name="tags[]"]:checked');
    const tagValues = Array.from(selectedTags).map(tag => tag.value);
    console.log('Selected tags:', tagValues);
    
    // Log other form values
    console.log('Question count:', document.getElementById('num-questions').value);
    console.log('Time limit:', document.getElementById('time-limit').value);
}

/**
 * Setup animations
 * Adds various UI animations and interactive elements
 */
function setupAnimations() {
    // Add fade-in effect to the selection container
    const selectionContainer = document.querySelector('.selection-container');
    if (selectionContainer) {
        selectionContainer.classList.add('fade-in');
    }
    
    // Add animation to tag items when they are clicked
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('input[name="tags[]"]')) {
            const label = e.target.nextElementSibling;
            
            // Add a quick animation
            label.style.animation = 'none';
            setTimeout(() => {
                label.style.animation = 'pulse 0.5s ease';
            }, 10);
        }
    });
    
    // Add animation for preset selection
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('input[name="preset"]')) {
            const label = e.target.nextElementSibling;
            
            // Add a quick animation
            label.style.animation = 'none';
            setTimeout(() => {
                label.style.animation = 'pulse 0.5s ease';
            }, 10);
            
            // Scroll to preset details
            if (e.target.value !== 'custom') {
                setTimeout(() => {
                    presetDetailsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 300);
            }
        }
    });
    
    // Count selected tags and show counter
    const updateTagCounter = () => {
        const selectedTags = document.querySelectorAll('input[name="tags[]"]:checked');
        const count = selectedTags.length;
        
        // Format text based on number of selections
        let counterText = '';
        if (count === 0) {
            counterText = ''; // No counter when none selected
        } else if (count === 1) {
            counterText = '1 selected';
        } else {
            counterText = `${count} selected`;
        }
        
        const existingCounter = document.querySelector('.selected-count');
        if (count > 0) {
            if (existingCounter) {
                existingCounter.textContent = counterText;
                existingCounter.style.display = 'inline-block';
            } else {
                const counter = document.createElement('span');
                counter.className = 'selected-count';
                counter.textContent = counterText;
                const tagsSection = document.querySelector('.tags-section h3');
                tagsSection.appendChild(counter);
            }
        } else if (existingCounter) {
            existingCounter.style.display = 'none';
        }
    };
    
    // Update tag counter when tags change
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('input[name="tags[]"]')) {
            updateTagCounter();
        }
    });
} 