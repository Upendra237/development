/**
 * main.js
 * JavaScript functionality for sticker ordering website
 */

document.addEventListener('DOMContentLoaded', function() {
    // State management
    const state = {
        stickers: {
            regular: [], // [{id, category, name, quantity}]
            custom: []   // [{name, imagePath}]
        },
        contact: {
            mobile: '',
            messagingApp: '',
            instagram: ''
        },
        payment: {
            method: 'now',
            screenshot: null
        },
        orderSummary: {
            regularCount: 0,
            customCount: 0,
            freeStickers: 0,
            totalPrice: 0,
            orderCode: ''
        }
    };

    // Configuration variables
    const config = {
        basePrice: 10,               // Rs. per sticker
        minOrderQuantity: 10,        // Minimum stickers required
        bulkDiscountThreshold: 12,   // Buy X stickers
        bulkDiscountFree: 2,         // Get Y free
        customRatio: {
            regular: 9,              // For every X regular stickers
            custom: 3                // Can create Y custom stickers
        }
    };

    // DOM Elements
    const elements = {
        // Progress steps
        progressSteps: document.querySelectorAll('.progress-step'),
        
        // Step sections
        stickerSelection: document.getElementById('sticker-selection'),
        contactDetails: document.getElementById('contact-details'),
        paymentSection: document.getElementById('payment-section'),
        confirmation: document.getElementById('confirmation'),
        
        // Sticker selection elements
        stickers: document.querySelectorAll('.sticker:not(.sticker-custom)'),
        createCustom: document.getElementById('create-custom'),
        customAvailable: document.getElementById('custom-available').querySelector('span'),
        regularCounter: document.getElementById('regular-counter'),
        customCounter: document.getElementById('custom-counter'),
        totalCounter: document.getElementById('total-counter'),
        freeStickers: document.getElementById('free-stickers'),
        totalPrice: document.getElementById('total-price'),
        proceedButton: document.getElementById('proceed-button'),
        
        // Contact form
        contactForm: document.getElementById('contact-form'),
        backToSelection: document.getElementById('back-to-selection'),
        
        // Payment form
        paymentForm: document.getElementById('payment-form'),
        paymentMethod: document.querySelectorAll('input[name="payment_method"]'),
        paymentNowSection: document.getElementById('payment-now-section'),
        paymentScreenshot: document.getElementById('payment-screenshot'),
        backToContact: document.getElementById('back-to-contact'),
        
        // Order summary elements
        summaryStickers: document.getElementById('summary-stickers'),
        summaryTotalStickers: document.getElementById('summary-total-stickers'),
        summaryFreeStickers: document.getElementById('summary-free-stickers'),
        summaryTotalPrice: document.getElementById('summary-total-price'),
        
        // Confirmation elements
        orderCode: document.getElementById('order-code'),
        confTotalPrice: document.getElementById('conf-total-price'),
        paymentStatus: document.getElementById('payment-status'),
        newOrder: document.getElementById('new-order'),
        
        // Custom sticker modal
        customModal: document.getElementById('custom-modal'),
        customForm: document.getElementById('custom-form'),
        customName: document.getElementById('custom-name'),
        customImage: document.getElementById('custom-image'),
        previewContainer: document.getElementById('preview-container'),
        closeModal: document.querySelector('.close-modal'),
        cancelCustom: document.querySelector('.cancel-custom')
    };

    // Initialize
    init();

    /**
     * Initialize the application
     */
    function init() {
        // Set up event listeners
        setupEventListeners();
        
        // Initialize counters and calculations
        updateCounters();
        updateCustomAvailable();
    }

    /**
     * Set up all event listeners
     */
    function setupEventListeners() {
        // Sticker quantity buttons
        elements.stickers.forEach(sticker => {
            const increaseBtn = sticker.querySelector('.increase');
            const decreaseBtn = sticker.querySelector('.decrease');
            
            increaseBtn.addEventListener('click', () => {
                changeQuantity(sticker, 1);
            });
            
            decreaseBtn.addEventListener('click', () => {
                changeQuantity(sticker, -1);
            });
        });
        
        // Custom sticker creation
        elements.createCustom.addEventListener('click', openCustomModal);
        elements.closeModal.addEventListener('click', closeCustomModal);
        elements.cancelCustom.addEventListener('click', closeCustomModal);
        
        // Custom sticker form
        elements.customForm.addEventListener('submit', handleCustomStickerSubmit);
        elements.customImage.addEventListener('change', previewCustomImage);
        
        // Navigation between steps
        elements.proceedButton.addEventListener('click', () => goToStep(2));
        elements.backToSelection.addEventListener('click', () => goToStep(1));
        elements.backToContact.addEventListener('click', () => goToStep(2));
        elements.newOrder.addEventListener('click', resetOrder);
        
        // Form submissions
        elements.contactForm.addEventListener('submit', handleContactSubmit);
        elements.paymentForm.addEventListener('submit', handlePaymentSubmit);
        
        // Payment method toggle
        elements.paymentMethod.forEach(radio => {
            radio.addEventListener('change', togglePaymentMethod);
        });
    }

    /**
     * Change sticker quantity and update UI
     * 
     * @param {HTMLElement} sticker The sticker element
     * @param {Number} change Amount to change (1 or -1)
     */
    function changeQuantity(sticker, change) {
        const quantityElement = sticker.querySelector('.quantity');
        const decreaseBtn = sticker.querySelector('.decrease');
        let quantity = parseInt(quantityElement.textContent);
        
        // Update quantity
        quantity += change;
        
        // Ensure quantity is not negative
        if (quantity < 0) quantity = 0;
        
        // Update UI
        quantityElement.textContent = quantity;
        decreaseBtn.disabled = quantity === 0;
        
        // Toggle selected class
        if (quantity > 0) {
            sticker.classList.add('selected');
        } else {
            sticker.classList.remove('selected');
        }
        
        // Update state
        updateStickerState(sticker, quantity);
        
        // Update counters and calculations
        updateCounters();
        updateCustomAvailable();
        checkMinimumOrder();
    }
    
    /**
     * Update the sticker state based on selection
     * 
     * @param {HTMLElement} sticker The sticker element
     * @param {Number} quantity The new quantity
     */
    function updateStickerState(sticker, quantity) {
        const stickerId = sticker.dataset.id;
        const category = sticker.dataset.category;
        const name = sticker.querySelector('.sticker-name').textContent;
        
        // Find sticker in state
        const existingIndex = state.stickers.regular.findIndex(s => s.id === stickerId);
        
        if (quantity > 0) {
            // Add or update sticker
            if (existingIndex !== -1) {
                state.stickers.regular[existingIndex].quantity = quantity;
            } else {
                state.stickers.regular.push({
                    id: stickerId,
                    category: category,
                    name: name,
                    quantity: quantity
                });
            }
        } else {
            // Remove sticker if quantity is 0
            if (existingIndex !== -1) {
                state.stickers.regular.splice(existingIndex, 1);
            }
        }
    }
    
    /**
     * Update counter displays
     */
    function updateCounters() {
        // Calculate totals
        const regularCount = state.stickers.regular.reduce((total, sticker) => {
            return total + sticker.quantity;
        }, 0);
        
        const customCount = state.stickers.custom.length;
        const totalCount = regularCount + customCount;
        
        // Calculate free stickers (bulk discount)
        const discountSets = Math.floor(totalCount / config.bulkDiscountThreshold);
        const freeStickers = discountSets * config.bulkDiscountFree;
        
        // Calculate total price
        const paidStickers = totalCount - freeStickers;
        const totalPrice = paidStickers * config.basePrice;
        
        // Update state
        state.orderSummary.regularCount = regularCount;
        state.orderSummary.customCount = customCount;
        state.orderSummary.freeStickers = freeStickers;
        state.orderSummary.totalPrice = totalPrice;
        
        // Update UI
        elements.regularCounter.textContent = `${regularCount} Regular`;
        elements.customCounter.textContent = `${customCount} Custom`;
        elements.totalCounter.textContent = `${totalCount} Total`;
        elements.freeStickers.textContent = `Free stickers: ${freeStickers}`;
        elements.totalPrice.textContent = `Total: Rs. ${totalPrice}`;
    }
    
    /**
     * Update available custom stickers count
     */
    function updateCustomAvailable() {
        const regularCount = state.stickers.regular.reduce((total, sticker) => {
            return total + sticker.quantity;
        }, 0);
        
        const customUsed = state.stickers.custom.length;
        const customAllowed = Math.floor(regularCount / config.customRatio.regular) * config.customRatio.custom;
        const customAvailable = Math.max(0, customAllowed - customUsed);
        
        // Update UI
        elements.customAvailable.textContent = customAvailable;
        elements.createCustom.classList.toggle('disabled', customAvailable === 0);
    }
    
    /**
     * Check if minimum order requirement is met
     */
    function checkMinimumOrder() {
        const totalCount = state.orderSummary.regularCount + state.orderSummary.customCount;
        const isMinimumMet = totalCount >= config.minOrderQuantity;
        
        // Enable/disable proceed button
        elements.proceedButton.disabled = !isMinimumMet;
    }
    
    /**
     * Open custom sticker modal
     */
    function openCustomModal() {
        const customAvailable = parseInt(elements.customAvailable.textContent);
        
        if (customAvailable <= 0) {
            alert('Order more regular stickers to unlock custom designs!');
            return;
        }
        
        elements.customModal.style.display = 'block';
        elements.customForm.reset();
        elements.previewContainer.innerHTML = '';
    }
    
    /**
     * Close custom sticker modal
     */
    function closeCustomModal() {
        elements.customModal.style.display = 'none';
    }
    
    /**
     * Preview custom sticker image
     */
    function previewCustomImage() {
        const file = elements.customImage.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                elements.previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            
            reader.readAsDataURL(file);
        }
    }
    
    /**
     * Handle custom sticker form submission
     * 
     * @param {Event} e Form submit event
     */
    function handleCustomStickerSubmit(e) {
        e.preventDefault();
        
        const customName = elements.customName.value.trim();
        const customImage = elements.customImage.files[0];
        
        if (!customName || !customImage) {
            alert('Please fill all fields');
            return;
        }
        
        // In a real implementation, we would upload the image to the server
        // and get back a URL. For now, we'll use a placeholder
        const tempImageUrl = URL.createObjectURL(customImage);
        
        // Add custom sticker to state
        state.stickers.custom.push({
            name: customName,
            imagePath: tempImageUrl
        });
        
        // Update UI
        updateCounters();
        updateCustomAvailable();
        checkMinimumOrder();
        
        // Add visual feedback
        const customCounterEl = elements.customCounter;
        customCounterEl.classList.add('highlight');
        setTimeout(() => customCounterEl.classList.remove('highlight'), 1000);
        
        // Close modal
        closeCustomModal();
    }
    
    /**
     * Handle contact form submission
     * 
     * @param {Event} e Form submit event
     */
    function handleContactSubmit(e) {
        e.preventDefault();
        
        // Get form data
        const contactNumber = e.target.elements.contact_number.value.trim();
        const messagingApp = e.target.elements.messaging_app.value;
        const instagram = e.target.elements.instagram_username.value.trim();
        
        // Basic validation
        if (!contactNumber || !messagingApp) {
            alert('Please fill all required fields');
            return;
        }
        
        // Update state
        state.contact.mobile = contactNumber;
        state.contact.messagingApp = messagingApp;
        state.contact.instagram = instagram;
        
        // Update order summary
        updateOrderSummary();
        
        // Go to payment step
        goToStep(3);
    }
    
    /**
     * Update order summary in payment step
     */
    function updateOrderSummary() {
        // Clear summary stickers
        elements.summaryStickers.innerHTML = '';
        
        // Add regular stickers
        state.stickers.regular.forEach(sticker => {
            const stickerEl = document.createElement('div');
            stickerEl.className = 'summary-sticker';
            stickerEl.textContent = `${sticker.quantity}x ${sticker.name}`;
            elements.summaryStickers.appendChild(stickerEl);
        });
        
        // Add custom stickers
        state.stickers.custom.forEach((sticker, index) => {
            const stickerEl = document.createElement('div');
            stickerEl.className = 'summary-sticker';
            stickerEl.textContent = `1x Custom: ${sticker.name}`;
            elements.summaryStickers.appendChild(stickerEl);
        });
        
        // Update totals
        elements.summaryTotalStickers.textContent = state.orderSummary.regularCount + state.orderSummary.customCount;
        elements.summaryFreeStickers.textContent = state.orderSummary.freeStickers;
        elements.summaryTotalPrice.textContent = state.orderSummary.totalPrice;
    }
    
    /**
     * Toggle payment method sections
     */
    function togglePaymentMethod() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (paymentMethod === 'now') {
            elements.paymentNowSection.style.display = 'block';
            elements.paymentScreenshot.required = true;
        } else {
            elements.paymentNowSection.style.display = 'none';
            elements.paymentScreenshot.required = false;
        }
        
        // Update state
        state.payment.method = paymentMethod;
    }
    
    /**
     * Handle payment form submission
     * 
     * @param {Event} e Form submit event
     */
    function handlePaymentSubmit(e) {
        e.preventDefault();
        
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // Check if screenshot is required and provided
        if (paymentMethod === 'now' && !elements.paymentScreenshot.files[0]) {
            alert('Please upload payment screenshot');
            return;
        }
        
        // Update state
        state.payment.method = paymentMethod;
        state.payment.screenshot = paymentMethod === 'now' ? elements.paymentScreenshot.files[0] : null;
        
        // In a real implementation, here we would submit the order to the server
        // and get back an order code. For demo purposes, we'll generate one
        state.orderSummary.orderCode = generateOrderCode();
        
        // Update confirmation screen
        updateConfirmation();
        
        // Go to confirmation step
        goToStep(4);
    }
    
    /**
     * Update confirmation screen with order details
     */
    function updateConfirmation() {
        elements.orderCode.textContent = state.orderSummary.orderCode;
        elements.confTotalPrice.textContent = state.orderSummary.totalPrice;
        elements.paymentStatus.textContent = state.payment.method === 'now' ? 'Paid' : 'Pending';
        elements.paymentStatus.className = state.payment.method === 'now' ? 'success' : 'warning';
    }
    
    /**
     * Generate a random order code
     * 
     * @return {String} Order code
     */
    function generateOrderCode() {
        const prefix = 'STK';
        const timestamp = new Date().getTime().toString().substr(-6);
        const random = Math.floor(1000 + Math.random() * 9000);
        return `${prefix}${timestamp}${random}`;
    }
    
    /**
     * Navigate to a specific step
     * 
     * @param {Number} step Step number (1-4)
     */
    function goToStep(step) {
        // Hide all steps
        document.querySelectorAll('.order-step').forEach(el => {
            el.classList.remove('active');
        });
        
        // Update progress bar
        elements.progressSteps.forEach(el => {
            const stepNum = parseInt(el.dataset.step);
            el.classList.toggle('active', stepNum === step);
            el.classList.toggle('completed', stepNum < step);
        });
        
        // Show current step
        switch (step) {
            case 1:
                elements.stickerSelection.classList.add('active');
                break;
            case 2:
                elements.contactDetails.classList.add('active');
                break;
            case 3:
                elements.paymentSection.classList.add('active');
                break;
            case 4:
                elements.confirmation.classList.add('active');
                break;
        }
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    /**
     * Reset the order and start over
     */
    function resetOrder() {
        // Reset state
        state.stickers.regular = [];
        state.stickers.custom = [];
        state.contact = { mobile: '', messagingApp: '', instagram: '' };
        state.payment = { method: 'now', screenshot: null };
        state.orderSummary = { regularCount: 0, customCount: 0, freeStickers: 0, totalPrice: 0, orderCode: '' };
        
        // Reset UI
        elements.stickers.forEach(sticker => {
            sticker.classList.remove('selected');
            sticker.querySelector('.quantity').textContent = '0';
            sticker.querySelector('.decrease').disabled = true;
        });
        
        elements.contactForm.reset();
        elements.paymentForm.reset();
        
        // Update calculations
        updateCounters();
        updateCustomAvailable();
        checkMinimumOrder();
        
        // Go to first step
        goToStep(1);
    }