/**
 * Quiz App - Main JavaScript
 */

// DOM Elements
const tagsContainer = document.getElementById('tags-container');
const tagForm = document.getElementById('tag-form');
const showMoreTagsBtn = document.getElementById('show-more-tags');

// Load tags when the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Fetch tags from API
    fetchTags();
    
    // Setup form validation
    setupFormValidation();
    
    // Setup show more tags button
    setupShowMoreTags();
    
    // Add smooth animations
    setupAnimations();
});

/**
 * Fetch tags from the API
 */
function fetchTags() {
    // Create XHR request
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'api/get_tags.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    // Handle response
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.tags && Array.isArray(response.tags)) {
                    renderTags(response.tags);
                }
            } catch (e) {
                console.error('Error parsing tags:', e);
            }
        } else {
            console.error('Error fetching tags:', this.status);
        }
    };
    
    // Handle error
    xhr.onerror = function() {
        console.error('Request error');
    };
    
    // Send request
    xhr.send();
}

/**
 * Render tags in the container
 */
function renderTags(tags) {
    // Clear container
    tagsContainer.innerHTML = '';
    
    if (tags.length === 0) {
        tagsContainer.innerHTML = '<p>No tags available</p>';
        showMoreTagsBtn.style.display = 'none';
        return;
    }
    
    // Create tag elements
    tags.forEach(tag => {
        const tagItem = document.createElement('div');
        tagItem.className = 'tag-item';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'tag-checkbox';
        checkbox.id = `tag-${tag}`;
        checkbox.name = 'tags[]';
        checkbox.value = tag;
        
        const label = document.createElement('label');
        label.className = 'tag-label';
        label.setAttribute('for', `tag-${tag}`);
        label.textContent = tag;
        
        tagItem.appendChild(checkbox);
        tagItem.appendChild(label);
        tagsContainer.appendChild(tagItem);
    });
    
    // Hide show more button if there are fewer tags than would need to be shown
    if (tags.length <= 6) { // Approximate number for two rows
        showMoreTagsBtn.style.display = 'none';
    }
}

/**
 * Setup show more tags button
 */
function setupShowMoreTags() {
    if (showMoreTagsBtn) {
        showMoreTagsBtn.addEventListener('click', function() {
            if (tagsContainer.classList.contains('expanded')) {
                // Collapse
                tagsContainer.classList.remove('expanded');
                this.textContent = 'Show More Tags';
            } else {
                // Expand
                tagsContainer.classList.add('expanded');
                this.textContent = 'Show Less Tags';
            }
        });
    }
}

/**
 * Form validation
 */
function setupFormValidation() {
    tagForm.addEventListener('submit', function(e) {
        // Get all checked tags
        const checkedTags = document.querySelectorAll('input[name="tags[]"]:checked');
        
        // Validate at least one tag is selected
        if (checkedTags.length === 0) {
            e.preventDefault();
            alert('Please select at least one topic to start the quiz');
            return false;
        }
        
        // Validate username
        const username = document.getElementById('username');
        if (username && username.value.trim() === '') {
            e.preventDefault();
            alert('Please enter your name');
            username.focus();
            return false;
        }
        
        // Add animation to indicate form submission
        document.querySelector('.quiz-setup').classList.add('fade-in');
        
        return true;
    });
}

/**
 * Setup animations
 */
function setupAnimations() {
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
    
    // Add hover animation to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('mouseover', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });
} 