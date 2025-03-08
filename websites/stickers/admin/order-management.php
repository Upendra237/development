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
    // Update order status
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? '');
        
        if ($orderId > 0 && in_array($status, ['new', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            $result = dbExecute(
                'UPDATE orders SET order_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
                [$status, $orderId]
            );
            
            if ($result) {
                setFlashMessage('success', 'Order status updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update order status.');
            }
        } else {
            setFlashMessage('error', 'Invalid input.');
        }
    }
    
    // Update payment status
    else if (isset($_POST['action']) && $_POST['action'] === 'update_payment') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = sanitizeInput($_POST['payment_status'] ?? '');
        
        if ($orderId > 0 && in_array($status, ['pending', 'paid'])) {
            $result = dbExecute(
                'UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
                [$status, $orderId]
            );
            
            if ($result) {
                setFlashMessage('success', 'Payment status updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update payment status.');
            }
        } else {
            setFlashMessage('error', 'Invalid input.');
        }
    }
    
    // Redirect to maintain clean URLs
    $redirectUrl = 'order-management.php';
    if (isset($_POST['order_id'])) {
        $redirectUrl .= '?view=' . (int)$_POST['order_id'];
    }
    redirect($redirectUrl);
}

// View specific order
if (isset($_GET['view'])) {
    $orderId = (int) $_GET['view'];
    
    // Get order details
    $order = dbQuery('
        SELECT * FROM orders WHERE id = ?
    ', [$orderId]);
    
    if (empty($order)) {
        setFlashMessage('error', 'Order not found.');
        redirect('order-management.php');
    }
    
    $order = $order[0];
    
    // Get order items
    $orderItems = dbQuery('
        SELECT 
            oi.id,
            oi.quantity,
            oi.custom_design_path,
            s.id as sticker_id,
            s.name as sticker_name,
            s.category,
            s.image_path
        FROM 
            order_items oi
        LEFT JOIN 
            stickers s ON oi.sticker_id = s.id
        WHERE 
            oi.order_id = ?
    ', [$orderId]);
    
    // Include header
    include '../includes/header.php';
?>

<div class="order-detail">
    <div class="page-header">
        <h1>Order Details</h1>
        <a href="order-management.php" class="btn secondary-btn">Back to Orders</a>
    </div>
    
    <?php $flashMessage = getFlashMessage(); ?>
    <?php if ($flashMessage): ?>
    <div class="message message-<?php echo $flashMessage['type']; ?>">
        <?php echo $flashMessage['message']; ?>
    </div>
    <?php endif; ?>
    
    <div class="order-info-grid">
        <div class="order-info-card">
            <h2>Order Information</h2>
            <table class="info-table">
                <tr>
                    <th>Order Code</th>
                    <td><?php echo $order['order_code']; ?></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Last Updated</th>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['updated_at'])); ?></td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td>Rs. <?php echo $order['total_amount']; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="order-info-card">
            <h2>Customer Information</h2>
            <table class="info-table">
                <tr>
                    <th>Contact Number</th>
                    <td><?php echo $order['contact_number']; ?></td>
                </tr>
                <tr>
                    <th>Messaging App</th>
                    <td><?php echo ucfirst($order['messaging_app']); ?></td>
                </tr>
                <?php if (!empty($order['instagram_username'])): ?>
                <tr>
                    <th>Instagram</th>
                    <td><?php echo $order['instagram_username']; ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="order-info-card">
            <h2>Status Information</h2>
            <form method="post" action="order-management.php" class="status-form">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                
                <div class="form-group">
                    <label for="order-status">Order Status</label>
                    <select id="order-status" name="status" class="status-select <?php echo $order['order_status']; ?>">
                        <option value="new" <?php echo $order['order_status'] === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" class="btn primary-btn">Update Status</button>
            </form>
            
            <form method="post" action="order-management.php" class="status-form">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                
                <div class="form-group">
                    <label for="payment-status">Payment Status</label>
                    <select id="payment-status" name="payment_status" class="status-select <?php echo $order['payment_status']; ?>">
                        <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                
                <button type="submit" class="btn primary-btn">Update Payment</button>
            </form>
            
            <?php if (!empty($order['payment_screenshot'])): ?>
            <div class="payment-screenshot">
                <h3>Payment Screenshot</h3>
                <a href="../uploads/payments/<?php echo $order['payment_screenshot']; ?>" target="_blank" class="screenshot-link">
                    <img src="../uploads/payments/<?php echo $order['payment_screenshot']; ?>" alt="Payment Screenshot">
                    <span>View Full Size</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="order-items">
        <h2>Order Items</h2>
        
        <div class="items-grid">
            <?php foreach ($orderItems as $item): ?>
            <div class="item-card">
                <div class="item-image">
                    <?php if (!empty($item['custom_design_path'])): ?>
                    <img src="../uploads/custom-stickers/<?php echo $item['custom_design_path']; ?>" alt="Custom Sticker">
                    <div class="item-badge">Custom</div>
                    <?php else: ?>
                    <img src="../assets/images/stickers/<?php echo $item['category']; ?>/<?php echo $item['image_path']; ?>" 
                         alt="<?php echo $item['sticker_name']; ?>">
                    <div class="item-badge"><?php echo ucfirst($item['category']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="item-info">
                    <h3><?php echo !empty($item['sticker_name']) ? $item['sticker_name'] : 'Custom Sticker'; ?></h3>
                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
} else {
    // List all orders
    
    // Get filter parameters
    $status = sanitizeInput($_GET['status'] ?? '');
    $payment = sanitizeInput($_GET['payment'] ?? '');
    $search = sanitizeInput($_GET['search'] ?? '');
    
    // Build query
    $query = '
        SELECT 
            o.id, 
            o.order_code, 
            o.contact_number, 
            o.messaging_app,
            o.total_amount, 
            o.payment_status, 
            o.order_status, 
            o.created_at,
            COUNT(oi.id) as item_count
        FROM 
            orders o
        LEFT JOIN 
            order_items oi ON o.id = oi.order_id
        WHERE 1=1
    ';
    
    $params = [];
    
    if (!empty($status)) {
        $query .= ' AND o.order_status = ?';
        $params[] = $status;
    }
    
    if (!empty($payment)) {
        $query .= ' AND o.payment_status = ?';
        $params[] = $payment;
    }
    
    if (!empty($search)) {
        $query .= ' AND (o.order_code LIKE ? OR o.contact_number LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $query .= '
        GROUP BY 
            o.id
        ORDER BY 
            o.created_at DESC
    ';
    
    // Get orders
    $orders = dbQuery($query, $params);
    
    // Count orders by status
    $statusCounts = [
        'new' => dbQuery('SELECT COUNT(*) as count FROM orders WHERE order_status = ?', ['new'])[0]['count'],
        'processing' => dbQuery('SELECT COUNT(*) as count FROM orders WHERE order_status = ?', ['processing'])[0]['count'],
        'shipped' => dbQuery('SELECT COUNT(*) as count FROM orders WHERE order_status = ?', ['shipped'])[0]['count'],
        'delivered' => dbQuery('SELECT COUNT(*) as count FROM orders WHERE order_status = ?', ['delivered'])[0]['count'],
        'cancelled' => dbQuery('SELECT COUNT(*) as count FROM orders WHERE order_status = ?', ['cancelled'])[0]['count'],
        'pending_payment' => dbQuery('SELECT COUNT(*) as count FROM orders WHERE payment_status = ?', ['pending'])[0]['count']
    ];
    
    // Include header
    include '../includes/header.php';
?>

<div class="order-management">
    <div class="page-header">
        <h1>Order Management</h1>
    </div>
    
    <?php $flashMessage = getFlashMessage(); ?>
    <?php if ($flashMessage): ?>
    <div class="message message-<?php echo $flashMessage['type']; ?>">
        <?php echo $flashMessage['message']; ?>
    </div>
    <?php endif; ?>
    
    <div class="filters">
        <div class="filter-pills">
            <a href="order-management.php" class="filter-pill <?php echo empty($status) && empty($payment) ? 'active' : ''; ?>">
                All Orders
            </a>
            <a href="order-management.php?status=new" class="filter-pill <?php echo $status === 'new' ? 'active' : ''; ?>">
                New (<?php echo $statusCounts['new']; ?>)
            </a>
            <a href="order-management.php?status=processing" class="filter-pill <?php echo $status === 'processing' ? 'active' : ''; ?>">
                Processing (<?php echo $statusCounts['processing']; ?>)
            </a>
            <a href="order-management.php?status=shipped" class="filter-pill <?php echo $status === 'shipped' ? 'active' : ''; ?>">
                Shipped (<?php echo $statusCounts['shipped']; ?>)
            </a>
            <a href="order-management.php?payment=pending" class="filter-pill <?php echo $payment === 'pending' ? 'active' : ''; ?>">
                Pending Payment (<?php echo $statusCounts['pending_payment']; ?>)
            </a>
        </div>
        
        <form class="search-form" method="get" action="order-management.php">
            <input type="text" name="search" placeholder="Search by order code or phone..." value="<?php echo $search; ?>">
            <button type="submit" class="btn secondary-btn">Search</button>
        </form>
    </div>
    
    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <p>No orders found matching your criteria.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order Code</th>
                    <th>Contact</th>
                    <th>App</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order['order_code']; ?></td>
                    <td><?php echo $order['contact_number']; ?></td>
                    <td><?php echo ucfirst($order['messaging_app']); ?></td>
                    <td><?php echo $order['item_count']; ?></td>
                    <td>Rs. <?php echo $order['total_amount']; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                    <td>
                        <a href="order-management.php?view=<?php echo $order['id']; ?>" class="btn small-btn">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
}

// Include footer
include 'includes/footer.php';
?>