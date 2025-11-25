<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../login_dashboard.php");
    exit();
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminId = $_SESSION['admin_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0"><i class="bi bi-grid-fill me-2"></i>Catering Admin</h4>
            <small class="text-white-50" id="userEmail"><?= htmlspecialchars($adminName) ?></small>
        </div>

        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-page="dashboard">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-page="bookings">
                        <i class="bi bi-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-page="quotations">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Quotations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-page="menus">
                        <i class="bi bi-book"></i>
                        <span>Menus</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-page="clients">
                        <i class="bi bi-people"></i>
                        <span>Clients</span>
                    </a>
                </li>
                
<li class="nav-item">
    <a class="nav-link" href="#" data-page="services">
        <i class="bi bi-star"></i>
        <span>Services</span>
    </a>
</li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-page="sales">
                        <i class="bi bi-cash-coin"></i>
                        <span>Sales & Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-page="reports">
                        <i class="bi bi-graph-up"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-page="notifications">
                        <i class="bi bi-bell"></i>
                        <span>Notifications</span>
                        <span class="badge bg-light text-dark ms-auto">3</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="mt-auto p-3 border-top border-secondary">
            <button class="btn btn-outline-light w-100" onclick="logout()">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <button class="btn btn-dark d-md-none me-2" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h2 class="mb-0 d-inline" id="pageTitle">Dashboard</h2>
            </div>
            <div>
                <button class="btn btn-outline-dark me-2">
                    <i class="bi bi-gear"></i>
                </button>
                <button class="btn btn-dark">
                    <i class="bi bi-bell"></i>
                </button>
            </div>
        </div>

        <!-- Page Content Container -->
        <div id="pageContent" class="page-content">
            <!-- Content will be loaded here dynamically -->
        </div>
    </div>


    <!-- Add Menu Modal -->
    <div class="modal fade" id="addMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Add Menu Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addMenuForm">
                    <div class="mb-3">
                        <label class="form-label">Dish Name *</label>
                        <input type="text" class="form-control" name="dish_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category" required>
                            <option value="beef">Beef</option>
                            <option value="pork">Pork</option>
                            <option value="chicken">Chicken</option>
                            <option value="pancit">Pancit</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="url" class="form-control" name="image_url" placeholder="https://example.com/image.jpg">
                        <small class="text-muted">Optional: Enter image URL</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price (Small/Base) *</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" name="menu_price" step="0.01" min="0" required>
                        </div>
                        <small class="text-muted">
                            • Medium will be 1.4x base price<br>
                            • Large will be 1.95x base price
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark" onclick="submitMenu()">Add Menu</button>
            </div>
        </div>
    </div>
</div>

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Add New Client</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addClientForm">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="contact_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" onclick="submitClient()">Add Client</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Add Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addServiceForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Service Name *</label>
                            <input type="text" class="form-control" name="service_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Service Type *</label>
                            <select class="form-select" name="service_type" required>
                                <option value="wedding">Wedding</option>
                                <option value="birthday">Birthday</option>
                                <option value="christening">Christening</option>
                                <option value="house_blessing">House Blessing</option>
                                <option value="team_building">Team Building</option>
                                <option value="reunion">Reunion</option>
                                <option value="corporate">Corporate Event</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price Per Person (₱) *</label>
                            <input type="number" class="form-control" name="price_per_person" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Guests *</label>
                            <input type="number" class="form-control" name="minimum_guests" min="1" value="30" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Icon Class (Bootstrap Icons)</label>
                        <input type="text" class="form-control" name="icon_class" value="bi-star-fill" placeholder="e.g., bi-heart-fill">
                        <small class="text-muted">Visit <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a> for icon names</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Inclusions</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="inclusionInput" placeholder="Enter inclusion">
                            <button type="button" class="btn btn-dark" onclick="addInclusion()">
                                <i class="bi bi-plus"></i> Add
                            </button>
                        </div>
                        <div id="inclusionsList"></div>
                        <input type="hidden" id="inclusionsData" name="inclusions">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" class="form-control" name="display_order" value="0" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_popular" id="isPopularService">
                                <label class="form-check-label" for="isPopularService">
                                    Mark as Popular
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveService" checked>
                                <label class="form-check-label" for="isActiveService">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark" onclick="submitService()">Add Service</button>
            </div>
        </div>
    </div>
</div>

    <!-- View/Edit Booking Modal -->
    <div class="modal fade" id="viewEditBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="viewEditModalTitle">Booking Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewEditBookingContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="app.js"></script>
    <script>
        // Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('../api/auth/logout.php', {
            method: 'POST',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            window.location.href = '../../login_dashboard.php';
        })
        .catch(error => {
            console.error('Logout error:', error);
            window.location.href = '../../login_dashboard.php';
        });
    }
}

<script>
// Price preview calculator for menu form
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.querySelector('#addMenuForm input[name="menu_price"]');
    
    if (priceInput) {
        priceInput.addEventListener('input', function() {
            const basePrice = parseFloat(this.value) || 0;
            document.getElementById('priceSmall').textContent = basePrice.toFixed(2);
            document.getElementById('priceMedium').textContent = (basePrice * 1.4).toFixed(2);
            document.getElementById('priceLarge').textContent = (basePrice * 1.95).toFixed(2);
        });
    }
});
</script>
    </script>
</body>

</html>