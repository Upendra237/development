<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is authenticated
if (!isAdminAuthenticated()) {
    redirect('login.php');
}

// Get order statistics
$totalOrders = dbQuery('SELECT COUNT(*) as count FROM orders')[0]['count'];
$pendingOrders = dbQuery('SELECT COUNT(*) as count FROM orders WHERE order_status = ?', ['new'])[0]['count'];
$processingOrders = dbQuery('SELECT COUNT(*) as count FROM orders WHERE order_status = ?', ['processing'])[0]['count'];
$pendingPayments = dbQuery('SELECT COUNT(*) as count FROM orders WHERE payment_status = ?', ['pending'])[0]['count'];

// Get recent orders
$recentOrders = dbQuery('
    SELECT 
        o.id, 
        o.order_code, 
        o.contact_number, 
        o.total_amount, 
        o.payment_status, 
        o.order_status, 
        o.created_at,
        COUNT(oi.id) as item_count
    FROM 
        orders o
    LEFT JOIN 
        order_items oi ON o.id = oi.order_id
    GROUP BY 
        o.id
    ORDER BY 
        o.created_at DESC
    LIMIT 5
');

// Include header
include '../includes/header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo $_SESSION['admin_username']; ?>!</p>
    </div>
    
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalOrders; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $pendingOrders; ?></div>
            <div class="stat-label">New Orders</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $processingOrders; ?></div>
            <div class="stat-label">Processing</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $pendingPayments; ?></div>
            <div class="stat-label">Pending Payments</div>
        </div>
    </div>
    
    <div class="recent-orders">
        <div class="card-header">
            <h2>Recent Orders</h2>
            <a href="order-management.php" class="btn secondary-btn">View All Orders</a>
        </div>
        
        <?php if (empty($recentOrders)): ?>
        <div class="empty-state">
            <p>No orders found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Code</th>
                        <th>Contact</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?php echo $order['order_code']; ?></td>
                        <td><?php echo $order['contact_number']; ?></td>
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
                            <a href="order-management.php?view=<?php echo $order['id']; ?>" class="action-link">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-footer">
        <div class="card">
            <h3>Quick Links</h3>
            <div class="quick-links">
                <a href="sticker-management.php" class="quick-link">
                    <span class="icon">🏷️</span>
                    <span class="text">Manage Stickers</span>
                </a>
                <a href="order-management.php" class="quick-link">
                    <span class="icon">📦</span>
                    <span class="text">Manage Orders</span>
                </a>
                <a href="logout.php" class="quick-link">
                    <span class="icon">🚪</span>
                    <span class="text">Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>