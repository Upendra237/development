/**
 * Quiz App - Quiz Page JavaScript
 */

// DOM Elements
const quizForm = document.getElementById('quiz-form');
const quizContainer = document.getElementById('quiz-container');
const questionCountSelect = document.getElementById('num-questions');
let questionCards;
let nextButtons;
let prevButtons;
let currentQuestion = 0;
let totalQuestions = 0;
let timeLimit = null;
let timer = null;
let timeRemaining = 0;
let answeredQuestions = [];

// Add segmented progress bar to quiz container
function addSegmentedProgressBar() {
    const progressBar = document.createElement('div');
    progressBar.className = 'quiz-progress';
    
    // Create segment for each question
    for (let i = 0; i < totalQuestions; i++) {
        const segment = document.createElement('div');
        segment.className = 'progress-segment';
        segment.dataset.number = (i + 1);
        segment.addEventListener('click', () => showQuestion(i));
        progressBar.appendChild(segment);
    }
    
    quizContainer.insertBefore(progressBar, quizContainer.firstChild);
    
    // Set first segment as active
    updateProgressSegments();
}

// Update progress segments
function updateProgressSegments() {
    const segments = document.querySelectorAll('.progress-segment');
    
    segments.forEach((segment, index) => {
        // Remove all classes first
        segment.classList.remove('active', 'completed');
        
        // Add appropriate class
        if (index === currentQuestion) {
            segment.classList.add('active');
        } else if (answeredQuestions.includes(index)) {
            segment.classList.add('completed');
        }
    });
}

// Add timer to the page
function addTimer(minutes) {
    const timerDiv = document.createElement('div');
    timerDiv.className = 'quiz-timer';
    timerDiv.innerHTML = `<i class="timer-icon">⏱</i> <span id="timer-display">00:00</span>`;
    
    // Add timer to the quiz container instead of body
    quizContainer.appendChild(timerDiv);
    
    // Convert minutes to seconds
    timeRemaining = minutes * 60;
    
    // Update timer display
    updateTimerDisplay();
    
    // Start the timer
    timer = setInterval(() => {
        timeRemaining--;
        updateTimerDisplay();
        
        // Check if time is up
        if (timeRemaining <= 0) {
            clearInterval(timer);
            alert('Time is up! Your answers will be submitted now.');
            quizForm.submit();
        }
        
        // Add warning class when less than 1 minute remaining
        if (timeRemaining <= 60) {
            timerDiv.classList.add('warning');
        }
    }, 1000);
}

// Update timer display
function updateTimerDisplay() {
    const minutes = Math.floor(timeRemaining / 60);
    const seconds = timeRemaining % 60;
    
    // Format with leading zeros
    const displayMinutes = minutes.toString().padStart(2, '0');
    const displaySeconds = seconds.toString().padStart(2, '0');
    
    document.getElementById('timer-display').textContent = `${displayMinutes}:${displaySeconds}`;
}

// Load when the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Get all question cards
    questionCards = document.querySelectorAll('.question-card');
    totalQuestions = questionCards.length;
    
    // Initialize answered questions array
    answeredQuestions = new Array(totalQuestions).fill(false);
    
    // Add segmented progress bar
    addSegmentedProgressBar();
    
    // Get navigation buttons
    nextButtons = document.querySelectorAll('.next-btn');
    prevButtons = document.querySelectorAll('.prev-btn');
    
    // Setup navigation
    setupNavigation();
    
    // Setup form validation
    setupFormValidation();
    
    // Add animations
    setupAnimations();
    
    // Initialize timer if time limit is set
    if (typeof quizData !== 'undefined' && quizData.timeLimit) {
        addTimer(quizData.timeLimit);
    }
    
    // Track answered questions
    trackAnsweredQuestions();
});

/**
 * Track which questions have been answered
 */
function trackAnsweredQuestions() {
    document.addEventListener('change', function(e) {
        if (e.target && e.target.type === 'radio') {
            // Find which question was answered
            const questionCard = e.target.closest('.question-card');
            if (questionCard) {
                const questionIndex = Array.from(questionCards).indexOf(questionCard);
                answeredQuestions[questionIndex] = true;
                updateProgressSegments();
                
                // Get the selected label and add a highlight class
                const selectedLabel = e.target.nextElementSibling;
                
                // Auto-navigate to next question after selection if not on the last question
                if (questionIndex < totalQuestions - 1) {
                    // Add a visual feedback delay to make it feel more responsive
                    // First animate the selection
                    selectedLabel.style.animation = 'none';
                    selectedLabel.offsetHeight; // Trigger reflow
                    selectedLabel.style.animation = 'select-pulse 0.4s ease-out';
                    
                    // Then navigate after a small delay
                    setTimeout(() => {
                        showQuestion(questionIndex + 1);
                    }, 450); // Shorter delay that matches the animation
                }
            }
        }
    });
}

/**
 * Setup navigation between questions
 */
function setupNavigation() {
    // Next buttons
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const nextQuestion = parseInt(this.dataset.question);
            showQuestion(nextQuestion);
        });
    });
    
    // Previous buttons
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            const prevQuestion = parseInt(this.dataset.question);
            showQuestion(prevQuestion);
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowRight' && currentQuestion < totalQuestions - 1) {
            showQuestion(currentQuestion + 1);
        } else if (e.key === 'ArrowLeft' && currentQuestion > 0) {
            showQuestion(currentQuestion - 1);
        }
    });
}

/**
 * Show a specific question
 */
function showQuestion(index) {
    // Only proceed if it's a different question
    if (index === currentQuestion) return;
    
    // Hide current question with animation
    questionCards[currentQuestion].classList.remove('fade-in');
    questionCards[currentQuestion].classList.add('fade-out');
    
    // Update current question early for better UX
    const previousQuestion = currentQuestion;
    currentQuestion = index;
    
    // Update progress segments immediately
    updateProgressSegments();
    
    setTimeout(() => {
        // Hide all questions
        questionCards.forEach(card => {
            card.style.display = 'none';
            card.classList.remove('fade-out');
        });
        
        // Show the target question
        questionCards[index].style.display = 'block';
        questionCards[index].classList.add('fade-in');
    }, 250); // Slightly shorter transition time
}

/**
 * Form validation
 */
function setupFormValidation() {
    quizForm.addEventListener('submit', function(e) {
        // Get all questions
        const questions = document.querySelectorAll('.question-card');
        let allAnswered = true;
        let firstUnanswered = null;
        
        // Check if all questions are answered
        questions.forEach((question, index) => {
            const questionId = question.id.replace('question-', '');
            const questionInputs = question.querySelectorAll('input[type="radio"]:checked');
            
            if (questionInputs.length === 0) {
                allAnswered = false;
                if (firstUnanswered === null) {
                    firstUnanswered = index;
                }
            }
        });
        
        // If not all questions are answered, show an alert and navigate to the first unanswered question
        if (!allAnswered) {
            e.preventDefault();
            
            if (confirm('You have not answered all questions. Do you want to continue anyway?')) {
                // Allow form submission if user confirms
                e.target.setAttribute('data-bypass-validation', 'true');
                setTimeout(() => {
                    // Add loading indicator
                    addLoadingIndicator();
                    // Submit the form
                    e.target.submit();
                }, 100);
                return false;
            } else {
                showQuestion(firstUnanswered);
                return false;
            }
        }
        
        // Stop the timer if it's running
        if (timer) {
            clearInterval(timer);
        }
        
        // Add loading indicator
        addLoadingIndicator();
        
        return true;
    });
}

/**
 * Add loading indicator when submitting the form
 */
function addLoadingIndicator() {
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.textContent = 'Submitting your answers...';
    document.body.appendChild(loadingIndicator);
}

/**
 * Setup animations
 */
function setupAnimations() {
    // Add animation when selecting an option
    document.addEventListener('change', function(e) {
        if (e.target && e.target.type === 'radio') {
            const label = e.target.nextElementSibling;
            
            // Add a quick animation
            label.style.animation = 'none';
            setTimeout(() => {
                label.style.animation = 'pulse 0.5s ease';
            }, 10);
        }
    });
    
    // Add animation for navigation buttons
    document.querySelectorAll('.question-nav button').forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
} 