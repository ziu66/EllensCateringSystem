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
    
    <!-- CRITICAL: This MUST be FIRST before any other CSS -->
    <style>
        /* Nuclear option - inline critical CSS that loads first */
        html {
            overflow-y: scroll !important;
            overflow-x: hidden !important;
        }
        body {
            overflow-y: scroll !important;
            overflow-x: hidden !important;
        }
        body.modal-open {
            overflow-y: scroll !important;
            overflow-x: hidden !important;
            padding-right: 0 !important;
        }
    </style>
    
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
                    <a class="nav-link" href="#" data-page="payments">
                        <i class="bi bi-credit-card"></i>
                        <span>Payments</span>
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

    <!-- Edit Quotation Price Modal -->
    <div class="modal fade" id="editQuotationPriceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Edit Quotation Pricing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editQuotationPriceID">
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Base Price (₱)</strong></label>
                        <input type="number" id="editQuotationBasePrice" class="form-control" disabled placeholder="Base price">
                        <small class="text-muted">Base price from package/menu selection (read-only)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Special Request Price (₱)</strong></label>
                        <input type="number" 
                               id="editQuotationSpecialRequestPrice" 
                               class="form-control" 
                               step="0.01" 
                               min="0" 
                               placeholder="Enter special request price"
                               onchange="updateQuotationPriceDisplay()"
                               oninput="updateQuotationPriceDisplay()">
                        <small class="text-muted">Additional cost for customer's special requests</small>
                    </div>
                    
                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total Price:</strong>
                            <h5 class="mb-0" id="editQuotationTotalPrice">₱0.00</h5>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" onclick="updateQuotationPrice()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Special Requests Manager Modal -->
    <div class="modal fade" id="specialRequestsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Manage Special Requests</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="specialRequestsQuotationID">
                    
                    <!-- Pricing Summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="alert alert-secondary mb-0">
                                <small class="text-muted d-block">Base Price</small>
                                <h5 class="mb-0" id="basePrice">₱0.00</h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <small class="text-muted d-block">Special Requests Total</small>
                                <h5 class="mb-0" id="specialRequestsTotal">₱0.00</h5>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <label class="form-label"><strong>Special Request Items</strong></label>
                    <div id="specialRequestsItemsList" class="mb-3">
                        <!-- Items will be added here dynamically -->
                    </div>
                    
                    <button type="button" class="btn btn-outline-dark w-100" onclick="addSpecialRequestItem()">
                        <i class="bi bi-plus me-2"></i>Add Item
                    </button>
                    
                    <div class="alert alert-light mt-3 p-2">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Enter special request items with their prices. The total will be added to the base price.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" onclick="saveSpecialRequests()">Save & Update Price</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* --- Prevent page shift when showing modals / panels ---
   Inserted AFTER bootstrap.bundle so `bootstrap` is defined.
   This script:
   1) clears inline padding-right/margin-right that Bootstrap sets
   2) removes modal-open class if set
   3) patches bootstrap.Modal.show to reset spacing after show
   4) observes style/class changes to auto-reset
*/
(function() {
    function resetBodySpacing() {
        try {
            // Use setProperty so it can apply with a priority similar to !important
            document.body.style.setProperty('padding-right', '0px', 'important');
            document.body.style.setProperty('margin-right', '0px', 'important');
        } catch (e) {
            // fallback
            document.body.style.paddingRight = '0px';
            document.body.style.marginRight  = '0px';
        }
        document.body.classList.remove('modal-open');
    }

    // Initial reset in case something already set inline styles
    resetBodySpacing();

    // If Bootstrap exists, monkey-patch Modal.show to call reset shortly after opening
    if (window.bootstrap && bootstrap.Modal && bootstrap.Modal.prototype) {
        const origShow = bootstrap.Modal.prototype.show;
        bootstrap.Modal.prototype.show = function() {
            origShow.call(this);                 // run native behavior
            setTimeout(resetBodySpacing, 10);   // then reset spacing
        };
    }

    // Observe inline style changes on body and reset if needed
    const styleObserver = new MutationObserver(mutations => {
        for (const m of mutations) {
            if (m.type === 'attributes' && m.attributeName === 'style') {
                const pr = document.body.style.getPropertyValue('padding-right');
                const mr = document.body.style.getPropertyValue('margin-right');
                if ((pr && pr !== '0px' && pr !== '0') || (mr && mr !== '0px' && mr !== '0')) {
                    resetBodySpacing();
                }
            }
        }
    });
    styleObserver.observe(document.body, { attributes: true, attributeFilter: ['style'] });

    // Observe class changes (to remove modal-open if added)
    const classObserver = new MutationObserver(mutations => {
        for (const m of mutations) {
            if (m.type === 'attributes' && m.attributeName === 'class') {
                if (document.body.classList.contains('modal-open')) {
                    document.body.classList.remove('modal-open');
                    resetBodySpacing();
                }
            }
        }
    });
    classObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });

})();
</script>

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


<!-- Add this HTML to your dashboard.php, BEFORE the closing </body> tag -->

<!-- Slide-in Panel Overlay -->
<div class="slide-panel-overlay" id="agreementPanelOverlay" onclick="closeAgreementPanel()"></div>

<!-- Slide-in Panel -->
<div class="slide-panel" id="agreementPanel">
    <div class="slide-panel-header">
        <h5><i class="bi bi-file-text"></i> Agreement Details</h5>
        <button class="slide-panel-close" onclick="closeAgreementPanel()">&times;</button>
    </div>
    
    <div class="slide-panel-body" id="agreementPanelContent">
        <!-- Agreement content will be loaded here -->
        <div class="text-center py-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    
    <div class="slide-panel-footer">
        <button class="btn btn-secondary" onclick="closeAgreementPanel()">
            <i class="bi bi-x-circle me-2"></i>Close
        </button>
        <button class="btn btn-info" onclick="printAgreement()">
            <i class="bi bi-printer me-2"></i>Print
        </button>
        <button class="btn btn-primary" onclick="downloadAgreementPDF()">
            <i class="bi bi-download me-2"></i>Download PDF
        </button>
    </div>
</div>

<script>

// In the script section, replace the openAgreementPanel function with this:

// Open Agreement Panel
function openAgreementPanel(bookingId) {
    const panel   = document.getElementById('agreementPanel');
    const overlay = document.getElementById('agreementPanelOverlay');
    const content = document.getElementById('agreementPanelContent');

    // Show overlay and panel
    overlay.classList.add('active');
    panel.classList.add('active');

    // CRITICAL: Keep scrollbar visible, don't hide overflow
    try {
        document.body.style.setProperty('padding-right', '0px', 'important');
        document.body.style.setProperty('margin-right', '0px', 'important');
        document.body.style.setProperty('overflow', 'scroll', 'important'); // Force scroll, not hidden
        document.body.style.setProperty('overflow-y', 'scroll', 'important');
        document.body.style.setProperty('overflow-x', 'hidden', 'important');
    } catch (e) {
        document.body.style.paddingRight = '0px';
        document.body.style.marginRight  = '0px';
        document.body.style.overflow = 'scroll';
        document.body.style.overflowY = 'scroll';
        document.body.style.overflowX = 'hidden';
    }
    document.body.classList.remove('modal-open');

    // Show loading + load content
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading agreement details...</p>
        </div>
    `;
    loadAgreementContent(bookingId);
}

// Close Agreement Panel
function closeAgreementPanel() {
    const panel   = document.getElementById('agreementPanel');
    const overlay = document.getElementById('agreementPanelOverlay');

    overlay.classList.remove('active');
    panel.classList.remove('active');

    // CRITICAL: Keep scrollbar visible
    try {
        document.body.style.setProperty('padding-right', '0px', 'important');
        document.body.style.setProperty('margin-right', '0px', 'important');
        document.body.style.setProperty('overflow', 'scroll', 'important');
        document.body.style.setProperty('overflow-y', 'scroll', 'important');
        document.body.style.setProperty('overflow-x', 'hidden', 'important');
    } catch (e) {
        document.body.style.paddingRight = '0px';
        document.body.style.marginRight  = '0px';
        document.body.style.overflow = 'scroll';
        document.body.style.overflowY = 'scroll';
        document.body.style.overflowX = 'hidden';
    }
    document.body.classList.remove('modal-open');
}



// Load Agreement Content
function loadAgreementContent(bookingId) {
    const content = document.getElementById('agreementPanelContent');
    
    fetch(`../../web/api/agreements/index.php?action=admin_get_agreement&booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.agreement) {
                const agreement = data.agreement;
                
                content.innerHTML = `
                    <div class="agreement-content">
                        <h1>CATERING AGREEMENT</h1>
                        
                        <p>
                            This Catering Agreement (hereinafter referred to as the <strong>"Agreement"</strong>) is entered into on 
                            <strong>${agreement.EffectiveDate || 'N/A'}</strong>, (the <strong>"Effective date"</strong>), by and between 
                            <strong>${agreement.ClientName || 'N/A'}</strong> (hereinafter referred to as <strong>"Client"</strong>) and 
                            <strong>Elma M. Barcelon</strong>, with an address of 
                            <strong>${agreement.Address || 'N/A'}</strong> (hereinafter referred to as the <strong>"Caterer"</strong>) 
                            collectively referred to as the <strong>"Parties"</strong>, both of whom agree to be bound by this agreement.
                        </p>
                        
                        <p>
                            The <strong>Caterer</strong> guarantees that all food will be prepared, stored, and served in compliance 
                            with all applicable health and safety regulations to prevent contamination or foodborne illness caused by 
                            the food provided. <strong>The Caterer agrees to assume full responsibility and indemnify the Client for 
                            any resulting medical cost or liabilities.</strong>
                        </p>
                        
                        <h4>EVENT DATE AND LOCATION</h4>
                        <p>The event will occur on <strong>${agreement.EventDate || 'N/A'}</strong>. It will be located at <strong>${agreement.Location || 'N/A'}</strong>.</p>
                        
                        <h4>EVENT DETAILS</h4>
                        <table>
                            <tr>
                                <td>Event Type:</td>
                                <td>${agreement.EventType || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td>Number of Guests:</td>
                                <td>${agreement.NumberOfGuests || '0'}</td>
                            </tr>
                            <tr>
                                <td>Special Requests:</td>
                                <td>${agreement.SpecialRequests || 'none'}</td>
                            </tr>
                        </table>
                        
                        <h4>OVERALL TOTAL</h4>
                        <div class="alert alert-success text-center">
                            <h3 class="mb-0">₱${parseFloat(agreement.TotalAmount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h3>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Failed to load agreement details. Please try again.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading agreement:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error loading agreement: ${error.message}
                </div>
            `;
        });
}

// Print Agreement
function printAgreement() {
    const content = document.getElementById('agreementPanelContent').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Agreement</title>
            <style>
                body { font-family: 'Times New Roman', Times, serif; padding: 40px; }
                h1 { text-align: center; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                table td { padding: 10px; border: 1px solid #000; }
            </style>
        </head>
        <body>${content}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Download PDF (placeholder - you'll need to implement actual PDF generation)
function downloadAgreementPDF() {
    alert('PDF download functionality - implement with jsPDF or similar library');
}

// Close panel with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAgreementPanel();
    }
});
</script>

</body>
</html>