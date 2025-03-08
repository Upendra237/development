<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is authenticated
if (!isAdminAuthenticated()) {
    redirect('login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new sticker
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        
        // Validate inputs
        if (empty($name) || empty($category) || !in_array($category, ['large', 'medium', 'small'])) {
            setFlashMessage('error', 'Invalid input. Please check all fields.');
        } else if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            setFlashMessage('error', 'Please upload a valid image.');
        } else {
            // Upload image
            $uploadPath = __DIR__ . '/../assets/images/stickers/' . $category;
            $filename = uploadImage($_FILES['image'], $uploadPath);
            
            if ($filename) {
                // Insert into database
                $result = dbExecute(
                    'INSERT INTO stickers (name, image_path, category) VALUES (?, ?, ?)',
                    [$name, $filename, $category]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Sticker added successfully.');
                } else {
                    setFlashMessage('error', 'Failed to add sticker. Please try again.');
                }
            } else {
                setFlashMessage('error', 'Failed to upload image. Please check file type and size.');
            }
        }
    }
    
    // Toggle sticker status
    else if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0);
        
        if ($id > 0) {
            $result = dbExecute(
                'UPDATE stickers SET active = ? WHERE id = ?',
                [$active, $id]
            );
            
            if ($result) {
                echo json_encode(['success' => true]);
                exit;
            }
        }
        
        echo json_encode(['success' => false]);
        exit;
    }
    
    // Delete sticker
    else if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id > 0) {
            // Get sticker info first to delete image
            $sticker = dbQuery('SELECT image_path, category FROM stickers WHERE id = ?', [$id]);
            
            if (!empty($sticker)) {
                $imagePath = __DIR__ . '/../assets/images/stickers/' . $sticker[0]['category'] . '/' . $sticker[0]['image_path'];
                
                // Delete from database
                $result = dbExecute('DELETE FROM stickers WHERE id = ?', [$id]);
                
                if ($result) {
                    // Delete image file
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    
                    setFlashMessage('success', 'Sticker deleted successfully.');
                } else {
                    setFlashMessage('error', 'Failed to delete sticker.');
                }
            } else {
                setFlashMessage('error', 'Sticker not found.');
            }
        } else {
            setFlashMessage('error', 'Invalid sticker ID.');
        }
    }
    
    // Other actions require a full-page hreload, but toggle should return JSON
    if (!(isset($_POST['action']) && $_POST['action'] === 'toggle')) {
        redirect('sticker-management.php');
    }
}

// Get filter parameters
$category = sanitizeInput($_GET['category'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$status = isset($_GET['status']) ? (int) $_GET['status'] : -1;

// Build query
$query = 'SELECT id, name, image_path, category, active FROM stickers WHERE 1=1';
$params = [];

if (!empty($category)) {
    $query .= ' AND category = ?';
    $params[] = $category;
}

if (!empty($search)) {
    $query .= ' AND name LIKE ?';
    $params[] = "%$search%";
}

if ($status !== -1) {
    $query .= ' AND active = ?';
    $params[] = $status;
}

$query .= ' ORDER BY id DESC';

// Get stickers
$stickers = dbQuery($query, $params);

// Count stickers by category
$categoryCounts = [
    'large' => dbQuery('SELECT COUNT(*) as count FROM stickers WHERE category = ?', ['large'])[0]['count'],
    'medium' => dbQuery('SELECT COUNT(*) as count FROM stickers WHERE category = ?', ['medium'])[0]['count'],
    'small' => dbQuery('SELECT COUNT(*) as count FROM stickers WHERE category = ?', ['small'])[0]['count']
];

// Include header
include '../includes/header.php';
?>

<div class="sticker-management">
    <div class="page-header">
        <h1>Sticker Management</h1>
        <button class="btn primary-btn" id="add-sticker-btn">Add New Sticker</button>
    </div>
    
    <div class="filters">
        <div class="filter-pills">
            <a href="sticker-management.php" class="filter-pill <?php echo empty($category) && $status === -1 && empty($search) ? 'active' : ''; ?>">
                All Stickers
            </a>
            <a href="sticker-management.php?category=large" class="filter-pill <?php echo $category === 'large' ? 'active' : ''; ?>">
                Large (<?php echo $categoryCounts['large']; ?>)
            </a>
            <a href="sticker-management.php?category=medium" class="filter-pill <?php echo $category === 'medium' ? 'active' : ''; ?>">
                Medium (<?php echo $categoryCounts['medium']; ?>)
            </a>
            <a href="sticker-management.php?category=small" class="filter-pill <?php echo $category === 'small' ? 'active' : ''; ?>">
                Small (<?php echo $categoryCounts['small']; ?>)
            </a>
            <a href="sticker-management.php?status=1" class="filter-pill <?php echo $status === 1 ? 'active' : ''; ?>">
                Active
            </a>
            <a href="sticker-management.php?status=0" class="filter-pill <?php echo $status === 0 ? 'active' : ''; ?>">
                Inactive
            </a>
        </div>
        
        <form class="search-form" method="get" action="sticker-management.php">
            <input type="text" name="search" placeholder="Search stickers..." value="<?php echo $search; ?>">
            <button type="submit" class="btn secondary-btn">Search</button>
        </form>
    </div>
    
    <?php $flashMessage = getFlashMessage(); ?>
    <?php if ($flashMessage): ?>
    <div class="message message-<?php echo $flashMessage['type']; ?>">
        <?php echo $flashMessage['message']; ?>
    </div>
    <?php endif; ?>
    
    <div class="stickers-grid">
        <?php if (empty($stickers)): ?>
        <div class="empty-state">
            <p>No stickers found. Add some stickers to get started.</p>
        </div>
        <?php else: ?>
        <?php foreach ($stickers as $sticker): ?>
        <div class="sticker-card" data-id="<?php echo $sticker['id']; ?>">
            <div class="sticker-image">
                <img src="../assets/images/stickers/<?php echo $sticker['category']; ?>/<?php echo $sticker['image_path']; ?>" 
                     alt="<?php echo $sticker['name']; ?>">
                <div class="category-badge"><?php echo ucfirst($sticker['category']); ?></div>
            </div>
            <div class="sticker-info">
                <h3><?php echo $sticker['name']; ?></h3>
                <div class="sticker-actions">
                    <label class="toggle-switch">
                        <input type="checkbox" class="toggle-status" <?php echo $sticker['active'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                        <span class="toggle-label"><?php echo $sticker['active'] ? 'Active' : 'Inactive'; ?></span>
                    </label>
                    <button class="btn delete-btn" data-id="<?php echo $sticker['id']; ?>">Delete</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Sticker Modal -->
<div class="modal" id="add-sticker-modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Add New Sticker</h2>
        
        <form method="post" action="sticker-management.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="sticker-name">Sticker Name *</label>
                <input type="text" id="sticker-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="sticker-category">Category *</label>
                <select id="sticker-category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="large">Large</option>
                    <option value="medium">Medium</option>
                    <option value="small">Small</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="sticker-image">Sticker Image *</label>
                <input type="file" id="sticker-image" name="image" accept="image/*" required>
                <div class="form-hint">JPG, PNG formats accepted. Max size: 2MB.</div>
            </div>
            
            <div class="image-preview-container">
                <h4>Preview</h4>
                <div id="image-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn secondary-btn" id="cancel-add">Cancel</button>
                <button type="submit" class="btn primary-btn">Add Sticker</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="delete-modal">
    <div class="modal-content">
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this sticker? This action cannot be undone.</p>
        
        <form method="post" action="sticker-management.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-sticker-id" value="">
            
            <div class="form-actions">
                <button type="button" class="btn secondary-btn" id="cancel-delete">Cancel</button>
                <button type="submit" class="btn delete-btn">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const addModal = document.getElementById('add-sticker-modal');
    const deleteModal = document.getElementById('delete-modal');
    const addBtn = document.getElementById('add-sticker-btn');
    const cancelAddBtn = document.getElementById('cancel-add');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const closeModalBtn = document.querySelector('.close-modal');
    
    // Add sticker modal
    addBtn.addEventListener('click', function() {
        addModal.style.display = 'block';
    });
    
    cancelAddBtn.addEventListener('click', function() {
        addModal.style.display = 'none';
    });
    
    closeModalBtn.addEventListener('click', function() {
        addModal.style.display = 'none';
    });
    
    // Image preview
    const imageInput = document.getElementById('sticker-image');
    const imagePreview = document.getElementById('image-preview');
    
    imageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Toggle sticker status
    const toggleSwitches = document.querySelectorAll('.toggle-status');
    
    toggleSwitches.forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const stickerId = this.closest('.sticker-card').dataset.id;
            const active = this.checked ? 1 : 0;
            const toggleLabel = this.nextElementSibling.nextElementSibling;
            
            // Update UI immediately for better UX
            toggleLabel.textContent = active ? 'Active' : 'Inactive';
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('id', stickerId);
            formData.append('active', active);
            
            fetch('sticker-management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert UI if failed
                    this.checked = !this.checked;
                    toggleLabel.textContent = this.checked ? 'Active' : 'Inactive';
                    alert('Failed to update sticker status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert UI on error
                this.checked = !this.checked;
                toggleLabel.textContent = this.checked ? 'Active' : 'Inactive';
                alert('An error occurred. Please try again.');
            });
        });
    });
    
    // Delete sticker
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteStickerId = document.getElementById('delete-sticker-id');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const stickerId = this.dataset.id;
            deleteStickerId.value = stickerId;
            deleteModal.style.display = 'block';
        });
    });
    
    cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === addModal) {
            addModal.style.display = 'none';
        } else if (event.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>