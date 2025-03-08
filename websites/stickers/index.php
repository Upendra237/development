<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get sticker categories and data
$categories = ['large', 'medium', 'small', 'custom'];
$stickers = [];

foreach ($categories as $category) {
    $stickers[$category] = dbQuery(
        "SELECT id, name, image_path FROM stickers WHERE category = ? AND active = 1 ORDER BY id DESC",
        [$category]
    );
}

// Get site settings
$minOrderQuantity = (int) getSetting('min_order_quantity', 10);
$basePrice = (float) getSetting('base_price', 10);
$customRatio = getSetting('custom_sticker_ratio', '3:9');
list($customRatio, $regularRatio) = explode(':', $customRatio);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Stickers | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <meta name="description" content="Order custom and pre-designed stickers with easy browsing and bulk discounts.">
</head>
<body>
    <header>
        <div class="container">
            <h1>Sticker Shop</h1>
            <p class="tagline">Select your favorite designs or create custom stickers</p>
        </div>
    </header>

    <main id="app" class="container">
        <!-- Order progress indicator -->
        <div class="progress-bar">
            <div class="progress-step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-name">Select Stickers</span>
            </div>
            <div class="progress-step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-name">Contact Details</span>
            </div>
            <div class="progress-step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-name">Payment</span>
            </div>
            <div class="progress-step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-name">Confirmation</span>
            </div>
        </div>

        <!-- Flash messages display -->
        <?php $flashMessage = getFlashMessage(); ?>
        <?php if ($flashMessage): ?>
        <div class="message message-<?php echo $flashMessage['type']; ?>">
            <?php echo $flashMessage['message']; ?>
        </div>
        <?php endif; ?>

        <!-- Step 1: Sticker Selection -->
        <section id="sticker-selection" class="order-step active">
            <div class="order-info">
                <div class="selection-rules">
                    <h3>Selection Rules</h3>
                    <ul>
                        <li>Minimum order: <?php echo $minOrderQuantity; ?> stickers</li>
                        <li>For every <?php echo $regularRatio; ?> regular stickers, you can create <?php echo $customRatio; ?> custom stickers</li>
                        <li>Bulk discount: For every 12 stickers, you get 2 free (pay for 10, get 12)</li>
                    </ul>
                </div>
                <div class="selected-summary">
                    <h3>Your Selection</h3>
                    <div class="counter-panel">
                        <div class="counter" id="regular-counter">0 Regular</div>
                        <div class="counter" id="custom-counter">0 Custom</div>
                        <div class="counter" id="total-counter">0 Total</div>
                    </div>
                    <div class="price-panel">
                        <div class="price-detail">Base price: Rs. <?php echo $basePrice; ?> per sticker</div>
                        <div class="price-detail" id="free-stickers">Free stickers: 0</div>
                        <div class="total-price" id="total-price">Total: Rs. 0</div>
                    </div>
                </div>
                <button id="proceed-button" class="btn primary-btn" disabled>
                    Proceed to Contact Details
                </button>
            </div>

            <div class="sticker-categories">
                <?php foreach ($categories as $category): ?>
                <div class="category">
                    <h2><?php echo ucfirst($category); ?> Stickers</h2>
                    <div class="sticker-scroll">
                        <?php if ($category === 'custom'): ?>
                        <div class="sticker sticker-custom" id="create-custom">
                            <div class="sticker-image">
                                <img src="assets/images/ui/add-custom.svg" alt="Create Custom Sticker">
                            </div>
                            <div class="sticker-name">Create Custom</div>
                            <div class="custom-available" id="custom-available">
                                Available: <span>0</span>
                            </div>
                        </div>
                        <?php else: ?>
                            <?php foreach ($stickers[$category] as $sticker): ?>
                            <div class="sticker" data-id="<?php echo $sticker['id']; ?>" data-category="<?php echo $category; ?>">
                                <div class="sticker-image">
                                    <img src="assets/images/stickers/<?php echo $category; ?>/<?php echo $sticker['image_path']; ?>" 
                                         alt="<?php echo $sticker['name']; ?>">
                                    <div class="checkmark"></div>
                                </div>
                                <div class="sticker-name"><?php echo $sticker['name']; ?></div>
                                <div class="sticker-controls">
                                    <button class="quantity-btn decrease" disabled>-</button>
                                    <span class="quantity">0</span>
                                    <button class="quantity-btn increase">+</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Step 2: Contact Details -->
        <section id="contact-details" class="order-step">
            <h2>Contact Details</h2>
            <form id="contact-form">
                <div class="form-group">
                    <label for="contact-number">Mobile Number *</label>
                    <input type="tel" id="contact-number" name="contact_number" required 
                           placeholder="98XXXXXXXX" pattern="^(98|97)\d{8}$">
                    <div class="form-hint">Please enter a valid Nepali mobile number</div>
                </div>
                
                <div class="form-group">
                    <label>Messaging App *</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="messaging_app" value="viber" required>
                            <span>Viber</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="messaging_app" value="whatsapp" required>
                            <span>WhatsApp</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="instagram">Instagram Username (optional)</label>
                    <input type="text" id="instagram" name="instagram_username" 
                           placeholder="Your Instagram handle">
                </div>
                
                <div class="button-group">
                    <button type="button" id="back-to-selection" class="btn secondary-btn">Back</button>
                    <button type="submit" class="btn primary-btn">Continue to Payment</button>
                </div>
            </form>
        </section>

        <!-- Step 3: Payment -->
        <section id="payment-section" class="order-step">
            <h2>Payment Options</h2>
            
            <div class="payment-summary">
                <h3>Order Summary</h3>
                <div id="summary-stickers"></div>
                <div class="summary-price">
                    <p>Total stickers: <span id="summary-total-stickers">0</span></p>
                    <p>Free stickers: <span id="summary-free-stickers">0</span></p>
                    <p class="summary-total">Total amount: Rs. <span id="summary-total-price">0</span></p>
                </div>
            </div>
            
            <form id="payment-form">
                <div class="form-group">
                    <label>Payment Method *</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="payment_method" value="now" required checked>
                            <span>Pay Now</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="payment_method" value="later" required>
                            <span>Pay Later</span>
                        </label>
                    </div>
                </div>
                
                <div id="payment-now-section">
                    <div class="payment-instructions">
                        <h4>Payment Instructions</h4>
                        <p>Please transfer the amount to the following account and upload the screenshot:</p>
                        <ul>
                            <li>eSewa: 9801234567</li>
                            <li>Khalti: 9801234567</li>
                        </ul>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment-screenshot">Payment Screenshot *</label>
                        <input type="file" id="payment-screenshot" name="payment_screenshot" accept="image/*">
                        <div class="form-hint">Upload a screenshot of your payment</div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" id="back-to-contact" class="btn secondary-btn">Back</button>
                    <button type="submit" class="btn primary-btn">Complete Order</button>
                </div>
            </form>
        </section>

        <!-- Step 4: Confirmation -->
        <section id="confirmation" class="order-step">
            <div class="confirmation-content">
                <div class="success-icon">✓</div>
                <h2>Thank You for Your Order!</h2>
                <p>Your order has been received successfully.</p>
                <div class="order-info">
                    <p>Order Code: <span id="order-code"></span></p>
                    <p>Total Amount: Rs. <span id="conf-total-price"></span></p>
                    <p>Payment Status: <span id="payment-status"></span></p>
                </div>
                <p>We will contact you soon via your provided contact details.</p>
                <button id="new-order" class="btn primary-btn">Place Another Order</button>
            </div>
        </section>

        <!-- Custom Sticker Modal -->
        <div id="custom-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Create Custom Sticker</h2>
                <p>Upload your design for a custom sticker. It should be a clear image with good resolution.</p>
                
                <form id="custom-form">
                    <div class="form-group">
                        <label for="custom-name">Sticker Name *</label>
                        <input type="text" id="custom-name" name="custom_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="custom-image">Upload Image *</label>
                        <input type="file" id="custom-image" name="custom_image" accept="image/*" required>
                        <div class="form-hint">JPG, PNG formats accepted. Max size: 2MB.</div>
                    </div>
                    
                    <div class="custom-preview">
                        <h4>Preview</h4>
                        <div id="preview-container"></div>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn secondary-btn cancel-custom">Cancel</button>
                        <button type="submit" class="btn primary-btn">Add Custom Sticker</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Load JavaScript at the end for better performance -->
    <script src="assets/js/main.js"></script>
</body>
</html>